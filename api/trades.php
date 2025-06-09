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

require_once "../connect/db.php";
require_once "../vendor/autoload.php";

use Symfony\Component\Cache\Adapter\FilesystemAdapter;

// Simple shared hosting compatible cache
function initializeCache() {
    try {
        // Use filesystem cache - compatible with shared hosting
        return new FilesystemAdapter('trades_api', 60, '../cache');
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

// Optimized database connection with shared hosting compatibility
function getOptimizedPDO() {
    global $pdo;
    
    // Reuse existing connection if available
    if (isset($pdo) && $pdo instanceof PDO) {
        return $pdo;
    }
    
    try {
        // Shared hosting compatible options
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Removed persistent connections as some shared hosts don't support them
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

// Input validation and sanitization
function validateInput() {
    $token_id = filter_input(INPUT_GET, 'token_id', FILTER_VALIDATE_INT);
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 50;
    $offset = filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT) ?: 0;
    
    // Security limits
    $limit = min(max($limit, 1), 100); // Between 1-100
    $offset = max($offset, 0);
    
    if (!$token_id || $token_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid token_id']);
        exit;
    }
    
    return [$token_id, $limit, $offset];
}

// High-performance trade data fetcher
function getTradeData($pdo, $cache, $token_id, $limit, $offset) {
    $cacheKey = "trades_{$token_id}_{$limit}_{$offset}";
    $cachedData = $cache->getItem($cacheKey);
    
    if ($cachedData->isHit()) {
        return $cachedData->get();
    }
    
    try {
        // Optimized query with proper indexing
        $stmt = $pdo->prepare("
            SELECT 
                tt.id,
                tt.transaction_type,
                tt.price_per_token,
                tt.token_amount,
                tt.trx_amount,
                tt.created_at,
                tt.transaction_hash,
                u.username,
                UNIX_TIMESTAMP(tt.created_at) as timestamp
            FROM token_transactions tt
            LEFT JOIN users u ON tt.user_id = u.id
            WHERE tt.token_id = ? 
                AND tt.status = 'confirmed'
            ORDER BY tt.created_at DESC, tt.id DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$token_id, $limit, $offset]);
        $trades = $stmt->fetchAll();
        
        // Get additional statistics in single query
        $statsStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_trades,
                SUM(CASE WHEN transaction_type = 'buy' THEN token_amount ELSE 0 END) as total_bought,
                SUM(CASE WHEN transaction_type = 'sell' THEN token_amount ELSE 0 END) as total_sold,
                SUM(trx_amount) as total_volume,
                AVG(price_per_token) as avg_price,
                MIN(price_per_token) as min_price,
                MAX(price_per_token) as max_price,
                (SELECT price_per_token FROM token_transactions 
                 WHERE token_id = ? AND status = 'confirmed' 
                 ORDER BY created_at DESC LIMIT 1) as current_price,
                (SELECT price_per_token FROM token_transactions 
                 WHERE token_id = ? AND status = 'confirmed' 
                 AND created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                 ORDER BY created_at DESC LIMIT 1) as price_24h_ago
            FROM token_transactions 
            WHERE token_id = ? AND status = 'confirmed'
        ");
        
        $statsStmt->execute([$token_id, $token_id, $token_id]);
        $stats = $statsStmt->fetch();
        
        // Calculate 24h change
        $priceChange24h = 0;
        if ($stats['price_24h_ago'] > 0 && $stats['current_price'] > 0) {
            $priceChange24h = (($stats['current_price'] - $stats['price_24h_ago']) / $stats['price_24h_ago']) * 100;
        }
        
        $result = [
            'success' => true,
            'trades' => $trades,
            'stats' => [
                'total_trades' => (int)$stats['total_trades'],
                'total_bought' => (float)$stats['total_bought'],
                'total_sold' => (float)$stats['total_sold'],
                'total_volume' => (float)$stats['total_volume'],
                'avg_price' => (float)$stats['avg_price'],
                'min_price' => (float)$stats['min_price'],
                'max_price' => (float)$stats['max_price'],
                'current_price' => (float)$stats['current_price'],
                'price_24h_ago' => (float)$stats['price_24h_ago'],
                'price_change_24h' => $priceChange24h
            ],
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => (int)$stats['total_trades']
            ],
            'timestamp' => time()
        ];
        
        // Cache for 30 seconds
        $cachedData->set($result);
        $cachedData->expiresAfter(30);
        $cache->save($cachedData);
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Trades API Error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Failed to fetch trade data',
            'timestamp' => time()
        ];
    }
}

// Main execution
try {
    $cache = initializeCache();
    $pdo = getOptimizedPDO();
    [$token_id, $limit, $offset] = validateInput();
    
    $result = getTradeData($pdo, $cache, $token_id, $limit, $offset);
    
    // Set appropriate cache headers
    if ($result['success']) {
        header('Cache-Control: public, max-age=30');
        header('ETag: "' . md5(json_encode($result)) . '"');
    }
    
    echo json_encode($result, JSON_NUMERIC_CHECK);
    
} catch (Exception $e) {
    error_log("Trades API Fatal Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'timestamp' => time()
    ]);
}
?>
