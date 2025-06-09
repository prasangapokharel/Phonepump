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
        return new FilesystemAdapter('chart_api', 300, '../cache');
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

// Optimized database connection
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

// Input validation
function validateInput() {
    $token_id = filter_input(INPUT_GET, 'token_id', FILTER_VALIDATE_INT);
    $interval = filter_input(INPUT_GET, 'interval', FILTER_SANITIZE_STRING) ?: '1m';
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 100;
    
    // Validate interval
    $validIntervals = ['1m', '5m', '15m', '1h', '4h', '1d'];
    if (!in_array($interval, $validIntervals)) {
        $interval = '1m';
    }
    
    // Security limits
    $limit = min(max($limit, 10), 500); // Between 10-500
    
    if (!$token_id || $token_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid token_id']);
        exit;
    }
    
    return [$token_id, $interval, $limit];
}

// Get interval configuration
function getIntervalConfig($interval) {
    $configs = [
        '1m' => ['minutes' => 1, 'hours' => 2],      // Last 2 hours
        '5m' => ['minutes' => 5, 'hours' => 12],     // Last 12 hours
        '15m' => ['minutes' => 15, 'hours' => 24],   // Last 24 hours
        '1h' => ['minutes' => 60, 'hours' => 168],   // Last 7 days
        '4h' => ['minutes' => 240, 'hours' => 720],  // Last 30 days
        '1d' => ['minutes' => 1440, 'hours' => 2160] // Last 90 days
    ];
    
    return $configs[$interval] ?? $configs['1m'];
}

// Calculate price change percentage
function calculatePriceChange($firstPrice, $lastPrice) {
    if ($firstPrice <= 0) return 0;
    return (($lastPrice - $firstPrice) / $firstPrice) * 100;
}

// High-performance chart data generator
function getChartData($pdo, $cache, $token_id, $interval, $limit) {
    $cacheKey = "chart_{$token_id}_{$interval}_{$limit}";
    $cachedData = $cache->getItem($cacheKey);
    
    // Shorter cache time for real-time updates
    $cacheDuration = [
        '1m' => 10,   // 10 seconds
        '5m' => 30,   // 30 seconds
        '15m' => 60,  // 1 minute
        '1h' => 300,  // 5 minutes
        '4h' => 600,  // 10 minutes
        '1d' => 1800  // 30 minutes
    ];
    
    if ($cachedData->isHit()) {
        return $cachedData->get();
    }
    
    try {
        $config = getIntervalConfig($interval);
        $intervalMinutes = $config['minutes'];
        $hoursBack = $config['hours'];
        
        // Try to get real OHLCV data first - shared hosting compatible SQL
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(
                    DATE_SUB(created_at, 
                        INTERVAL MOD(MINUTE(created_at), ?) MINUTE
                    ), '%Y-%m-%d %H:%i:00'
                ) as time_bucket,
                MIN(price_per_token) as low,
                MAX(price_per_token) as high,
                SUBSTRING_INDEX(GROUP_CONCAT(price_per_token ORDER BY created_at ASC), ',', 1) as open,
                SUBSTRING_INDEX(GROUP_CONCAT(price_per_token ORDER BY created_at DESC), ',', 1) as close,
                SUM(token_amount) as volume,
                COUNT(*) as trades
            FROM token_transactions 
            WHERE token_id = ? 
                AND status = 'confirmed'
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
            GROUP BY time_bucket
            ORDER BY time_bucket ASC
            LIMIT ?
        ");
        
        $stmt->execute([$intervalMinutes, $token_id, $hoursBack, $limit]);
        $realData = $stmt->fetchAll();
        
        // Get first and last price for percentage calculation
        $firstPrice = 0;
        $lastPrice = 0;
        
        // If we have real data, use it
        if (!empty($realData)) {
            $chartData = array_map(function($row) {
                return [
                    'time' => $row['time_bucket'],
                    'open' => (float)$row['open'],
                    'high' => (float)$row['high'],
                    'low' => (float)$row['low'],
                    'close' => (float)$row['close'],
                    'volume' => (float)$row['volume'],
                    'trades' => (int)$row['trades']
                ];
            }, $realData);
            
            // Get first and last price for percentage calculation
            if (count($chartData) > 0) {
                $firstPrice = $chartData[0]['open'];
                $lastPrice = $chartData[count($chartData) - 1]['close'];
            }
        } else {
            // Generate realistic sample data if no real data exists
            $chartData = generateSampleChartData($pdo, $token_id, $interval, $limit);
            
            // Get first and last price for percentage calculation
            if (count($chartData) > 0) {
                $firstPrice = $chartData[0]['open'];
                $lastPrice = $chartData[count($chartData) - 1]['close'];
            }
        }
        
        // Get current token price and 24h price for reference
        $priceStmt = $pdo->prepare("
            SELECT 
                t.current_price, t.name, t.symbol,
                (SELECT price_per_token FROM token_transactions 
                 WHERE token_id = ? AND status = 'confirmed' 
                 AND created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                 ORDER BY created_at DESC LIMIT 1) as price_24h_ago
            FROM tokens t
            WHERE t.id = ?
        ");
        $priceStmt->execute([$token_id, $token_id]);
        $tokenInfo = $priceStmt->fetch();
        
        // Calculate price change percentage
        $priceChange24h = 0;
        if (!empty($tokenInfo['price_24h_ago']) && $tokenInfo['price_24h_ago'] > 0) {
            $priceChange24h = calculatePriceChange($tokenInfo['price_24h_ago'], $tokenInfo['current_price']);
        } else {
            // Fallback to chart data if 24h price not available
            $priceChange24h = calculatePriceChange($firstPrice, $lastPrice);
        }
        
        // Get trade count for last 24 hours
        $tradeCountStmt = $pdo->prepare("
            SELECT COUNT(*) as trades_24h
            FROM token_transactions
            WHERE token_id = ?
            AND status = 'confirmed'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $tradeCountStmt->execute([$token_id]);
        $tradeCount = $tradeCountStmt->fetchColumn();
        
        $result = [
            'success' => true,
            'data' => $chartData,
            'token_info' => $tokenInfo,
            'price_change_24h' => $priceChange24h,
            'trades_24h' => (int)$tradeCount,
            'interval' => $interval,
            'total_points' => count($chartData),
            'timestamp' => time()
        ];
        
        $cachedData->set($result);
        $cachedData->expiresAfter($cacheDuration[$interval] ?? 60);
        $cache->save($cachedData);
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Chart API Error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Failed to fetch chart data',
            'timestamp' => time()
        ];
    }
}

// Generate realistic sample data when no real data exists
function generateSampleChartData($pdo, $token_id, $interval, $limit) {
    try {
        // Get base price from token
        $stmt = $pdo->prepare("SELECT current_price FROM tokens WHERE id = ?");
        $stmt->execute([$token_id]);
        $token = $stmt->fetch();
        $basePrice = $token ? (float)$token['current_price'] : 0.00001;
        
        $config = getIntervalConfig($interval);
        $intervalMinutes = $config['minutes'];
        
        $data = [];
        $currentPrice = $basePrice;
        $now = new DateTime();
        
        for ($i = $limit - 1; $i >= 0; $i--) {
            $time = clone $now;
            $time->sub(new DateInterval("PT{$i}M"));
            
            // Align to interval
            $minutes = $time->format('i');
            $alignedMinutes = floor($minutes / $intervalMinutes) * $intervalMinutes;
            $time->setTime($time->format('H'), $alignedMinutes, 0);
            
            // Generate realistic OHLCV data
            $volatility = 0.02; // 2% volatility
            $trend = (mt_rand(-100, 100) / 10000); // Small trend
            
            $open = $currentPrice;
            $change = $currentPrice * $volatility * (mt_rand(-100, 100) / 100);
            $high = $open + abs($change) + ($currentPrice * mt_rand(0, 50) / 10000);
            $low = $open - abs($change) - ($currentPrice * mt_rand(0, 50) / 10000);
            $close = $open + $change + ($currentPrice * $trend);
            
            // Ensure logical OHLC relationship
            $high = max($high, $open, $close);
            $low = min($low, $open, $close);
            $close = max($close, $low * 0.99); // Prevent negative prices
            
            $volume = mt_rand(100, 10000) / 100; // Random volume
            $trades = mt_rand(1, 20);
            
            $data[] = [
                'time' => $time->format('Y-m-d H:i:s'),
                'open' => $open,
                'high' => $high,
                'low' => $low,
                'close' => $close,
                'volume' => $volume,
                'trades' => $trades
            ];
            
            $currentPrice = $close;
        }
        
        return $data;
        
    } catch (Exception $e) {
        error_log("Sample data generation error: " . $e->getMessage());
        return [];
    }
}

// Main execution
try {
    $cache = initializeCache();
    $pdo = getOptimizedPDO();
    [$token_id, $interval, $limit] = validateInput();
    
    $result = getChartData($pdo, $cache, $token_id, $interval, $limit);
    
    // Set appropriate cache headers
    if ($result['success']) {
        $maxAge = ['1m' => 10, '5m' => 30, '15m' => 60, '1h' => 300, '4h' => 600, '1d' => 1800][$interval] ?? 60;
        header("Cache-Control: public, max-age={$maxAge}");
        header('ETag: "' . md5(json_encode($result)) . '"');
    }
    
    echo json_encode($result, JSON_NUMERIC_CHECK);
    
} catch (Exception $e) {
    error_log("Chart API Fatal Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'timestamp' => time()
    ]);
}
?>
