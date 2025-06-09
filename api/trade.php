<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
require_once "../connect/db.php";
require_once "../vendor/autoload.php";

use Symfony\Component\Cache\Adapter\FilesystemAdapter;

// Rate limiting for high-traffic scenarios - shared hosting compatible
class RateLimiter {
    private static $maxRequests = 100; // per minute
    private static $timeWindow = 60; // seconds
    
    public static function checkLimit($identifier) {
        $cacheDir = '../cache/rate_limits';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        
        $now = time();
        $windowStart = $now - self::$timeWindow;
        $limitFile = $cacheDir . '/' . md5($identifier) . '.limit';
        
        // Read existing requests
        $requests = [];
        if (file_exists($limitFile)) {
            $requests = unserialize(file_get_contents($limitFile));
            if (!is_array($requests)) $requests = [];
        }
        
        // Clean old requests
        $requests = array_filter($requests, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        // Check limit
        if (count($requests) >= self::$maxRequests) {
            return false;
        }
        
        // Add current request
        $requests[] = $now;
        @file_put_contents($limitFile, serialize($requests));
        
        return true;
    }
}

// Simple shared hosting compatible cache
function initializeCache() {
    try {
        // Use filesystem cache - compatible with shared hosting
        return new FilesystemAdapter('trade_api', 30, '../cache');
    } catch (Exception $e) {
        // Ultra-lightweight fallback cache for shared hosting
        return new class {
            private static $data = [];
            
            public function getItem($key) {
                return new class($key) {
                    private $key, $value, $hit = false;
                    
                    public function __construct($key) {
                        $this->key = $key;
                        $cacheFile = '../cache/simple_' . md5($key) . '.cache';
                        
                        if (file_exists($cacheFile)) {
                            $data = unserialize(file_get_contents($cacheFile));
                            if ($data && $data['expires'] > time()) {
                                $this->value = $data['value'];
                                $this->hit = true;
                            }
                        }
                    }
                    
                    public function isHit() { return $this->hit; }
                    public function get() { return $this->value; }
                    
                    public function set($value) { 
                        $this->value = $value; 
                        return $this; 
                    }
                    
                    public function expiresAfter($seconds) {
                        if (!is_dir('../cache')) {
                            @mkdir('../cache', 0755, true);
                        }
                        
                        $cacheFile = '../cache/simple_' . md5($this->key) . '.cache';
                        $data = [
                            'value' => $this->value,
                            'expires' => time() + $seconds
                        ];
                        
                        @file_put_contents($cacheFile, serialize($data));
                        return $this;
                    }
                };
            }
            
            public function save($item) { return true; }
        };
    }
}

// Optimized database connection with transaction support
function getOptimizedPDO() {
    global $pdo;
    
    if (isset($pdo) && $pdo instanceof PDO) {
        return $pdo;
    }
    
    try {
        // Shared hosting compatible options
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION sql_mode='STRICT_TRANS_TABLES'"
        ];
        
        // Use the existing connection from connect/db.php
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit;
    }
}

// Authentication check
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
    return $_SESSION['user_id'];
}

// Input validation for trade requests
function validateTradeInput() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        exit;
    }
    
    $token_id = filter_var($input['token_id'] ?? 0, FILTER_VALIDATE_INT);
    $action = filter_var($input['action'] ?? '', FILTER_SANITIZE_STRING);
    $amount = filter_var($input['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
    $order_type = filter_var($input['order_type'] ?? 'market', FILTER_SANITIZE_STRING);
    $limit_price = filter_var($input['limit_price'] ?? 0, FILTER_VALIDATE_FLOAT);
    
    // Validation
    if (!$token_id || $token_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid token_id']);
        exit;
    }
    
    if (!in_array($action, ['buy', 'sell'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit;
    }
    
    if (!$amount || $amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid amount']);
        exit;
    }
    
    if (!in_array($order_type, ['market', 'limit'])) {
        $order_type = 'market';
    }
    
    // Security limits
    $amount = min($amount, 1000000); // Max 1M tokens per trade
    
    return [$token_id, $action, $amount, $order_type, $limit_price];
}

// High-performance trade execution with proper locking
function executeTrade($pdo, $cache, $user_id, $token_id, $action, $amount, $order_type, $limit_price) {
    try {
        // Start transaction with proper isolation
        $pdo->beginTransaction();
        
        // Lock relevant rows to prevent race conditions
        $stmt = $pdo->prepare("
            SELECT t.*, bc.*, 
                   COALESCE(tb.balance, 0) as user_balance,
                   w.balance as trx_balance
            FROM tokens t 
            LEFT JOIN bonding_curves bc ON t.id = bc.token_id 
            LEFT JOIN token_balances tb ON t.id = tb.token_id AND tb.user_id = ?
            LEFT JOIN trxbalance w ON w.user_id = ?
            WHERE t.id = ?
            FOR UPDATE
        ");
        $stmt->execute([$user_id, $user_id, $token_id]);
        $data = $stmt->fetch();
        
        if (!$data) {
            throw new Exception("Token not found");
        }
        
        // Get trading fee
        $feeStmt = $pdo->prepare("SELECT setting_value FROM company_settings WHERE setting_name = 'trading_fee_trx'");
        $feeStmt->execute();
        $tradingFee = (float)($feeStmt->fetchColumn() ?: 10);
        
        // Calculate price
        $price = ($order_type === 'limit' && $limit_price > 0) ? $limit_price : (float)$data['current_price'];
        
        if ($action === 'buy') {
            $totalCost = ($amount * $price) + $tradingFee;
            
            if ($data['trx_balance'] < $totalCost) {
                throw new Exception("Insufficient TRX balance. Need " . number_format($totalCost, 4) . " TRX");
            }
            
            // Update balances
            $stmt = $pdo->prepare("UPDATE trxbalance SET balance = balance - ? WHERE user_id = ?");
            $stmt->execute([$totalCost, $user_id]);
            
            $stmt = $pdo->prepare("
                INSERT INTO token_balances (token_id, user_id, balance, first_purchase_at, last_transaction_at, total_bought)
                VALUES (?, ?, ?, NOW(), NOW(), ?)
                ON DUPLICATE KEY UPDATE
                    balance = balance + VALUES(balance),
                    last_transaction_at = NOW(),
                    total_bought = total_bought + VALUES(total_bought)
            ");
            $stmt->execute([$token_id, $user_id, $amount, $amount]);
            
        } else { // sell
            if ($data['user_balance'] < $amount) {
                throw new Exception("Insufficient token balance. Have " . number_format($data['user_balance'], 2) . " tokens");
            }
            
            $trxReceived = $amount * $price;
            $totalAfterFee = $trxReceived - $tradingFee;
            
            if ($totalAfterFee <= 0) {
                throw new Exception("Amount too small to cover trading fee");
            }
            
            // Update balances
            $stmt = $pdo->prepare("UPDATE trxbalance SET balance = balance + ? WHERE user_id = ?");
            $stmt->execute([$totalAfterFee, $user_id]);
            
            $stmt = $pdo->prepare("
                UPDATE token_balances SET 
                    balance = balance - ?,
                    last_transaction_at = NOW(),
                    total_sold = total_sold + ?
                WHERE token_id = ? AND user_id = ?
            ");
            $stmt->execute([$amount, $amount, $token_id, $user_id]);
        }
        
        // Record transaction
        $txHash = 'tx_' . uniqid() . '_' . time();
        $stmt = $pdo->prepare("
            INSERT INTO token_transactions (
                token_id, user_id, transaction_hash, transaction_type,
                trx_amount, token_amount, price_per_token, fee_amount,
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', NOW())
        ");
        
        $trxAmount = $action === 'buy' ? ($amount * $price) : ($amount * $price);
        $stmt->execute([$token_id, $user_id, $txHash, $action, $trxAmount, $amount, $price, $tradingFee]);
        
        // Update token price and market cap
        $stmt = $pdo->prepare("
            UPDATE tokens SET 
                current_price = ?,
                market_cap = ? * total_supply,
                last_trade_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$price, $price, $token_id]);
        
        // Update bonding curve
        if ($action === 'buy') {
            $stmt = $pdo->prepare("
                UPDATE bonding_curves SET 
                    virtual_trx_reserves = virtual_trx_reserves + ?,
                    virtual_token_reserves = virtual_token_reserves - ?,
                    tokens_sold = tokens_sold + ?,
                    real_trx_reserves = real_trx_reserves + ?
                WHERE token_id = ?
            ");
            $stmt->execute([$trxAmount, $amount, $amount, $trxAmount, $token_id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE bonding_curves SET 
                    virtual_trx_reserves = GREATEST(0, virtual_trx_reserves - ?),
                    virtual_token_reserves = virtual_token_reserves + ?,
                    tokens_sold = GREATEST(0, tokens_sold - ?),
                    real_trx_reserves = GREATEST(0, real_trx_reserves - ?)
                WHERE token_id = ?
            ");
            $stmt->execute([$trxAmount, $amount, $amount, $trxAmount, $token_id]);
        }
        
        $pdo->commit();
        
        // Clear relevant caches
        $cacheKeys = [
            "trades_{$token_id}_50_0",
            "chart_{$token_id}_1m_100",
            "user_wallet_{$user_id}",
            "token_data_{$token_id}"
        ];
        
        foreach ($cacheKeys as $key) {
            $cache->getItem($key)->set(null);
        }
        
        return [
            'success' => true,
            'message' => "Successfully {$action} " . number_format($amount, 2) . " tokens",
            'transaction_hash' => $txHash,
            'price' => $price,
            'amount' => $amount,
            'action' => $action,
            'timestamp' => time()
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Trade execution error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => time()
        ];
    }
}

// Get token info for quick reference
function getTokenInfo($pdo, $cache, $token_id) {
    $cacheKey = "token_info_{$token_id}";
    $cachedData = $cache->getItem($cacheKey);
    
    if ($cachedData->isHit()) {
        return $cachedData->get();
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT t.*, bc.current_progress,
                   (SELECT COUNT(*) FROM token_transactions WHERE token_id = t.id AND status = 'confirmed') as total_trades,
                   (SELECT SUM(trx_amount) FROM token_transactions WHERE token_id = t.id AND status = 'confirmed') as total_volume
            FROM tokens t 
            LEFT JOIN bonding_curves bc ON t.id = bc.token_id 
            WHERE t.id = ?
        ");
        $stmt->execute([$token_id]);
        $result = $stmt->fetch();
        
        if ($result) {
            $cachedData->set($result);
            $cachedData->expiresAfter(60); // Cache for 1 minute
            $cache->save($cachedData);
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Token info error: " . $e->getMessage());
        return null;
    }
}

// Main execution
try {
    // Rate limiting
    $identifier = $_SESSION['user_id'] ?? $_SERVER['REMOTE_ADDR'];
    if (!RateLimiter::checkLimit($identifier)) {
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => 'Rate limit exceeded']);
        exit;
    }
    
    $cache = initializeCache();
    $pdo = getOptimizedPDO();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get token info
        $token_id = filter_input(INPUT_GET, 'token_id', FILTER_VALIDATE_INT);
        if (!$token_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid token_id']);
            exit;
        }
        
        $tokenInfo = getTokenInfo($pdo, $cache, $token_id);
        if (!$tokenInfo) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Token not found']);
            exit;
        }
        
        echo json_encode(['success' => true, 'token' => $tokenInfo]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Execute trade
        $user_id = checkAuth();
        [$token_id, $action, $amount, $order_type, $limit_price] = validateTradeInput();
        
        $result = executeTrade($pdo, $cache, $user_id, $token_id, $action, $amount, $order_type, $limit_price);
        
        if ($result['success']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Trade API Fatal Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'timestamp' => time()
    ]);
}
?>
