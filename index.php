<?php
session_start();
require_once "connect/db.php";

// Include Composer autoloader for Guzzle and Symfony Cache
require_once "vendor/autoload.php";

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

// Initialize Guzzle HTTP Client
$httpClient = new Client([
    'timeout' => 10,
    'connect_timeout' => 5,
    'headers' => [
        'User-Agent' => 'TronPump/1.0',
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
    ]
]);

// Initialize Cache
try {
    $cache = new FilesystemAdapter(
        'tronpump_cache',
        3600, // Default TTL: 1 hour
        'cache'
    );
} catch (Exception $e) {
    // Fallback cache implementation
    $cache = new class {
        private $data = [];
        
        public function getItem($key) {
            return new class($key, $this->data) {
                private $key;
                private $data;
                private $value;
                private $hit = false;
                
                public function __construct($key, &$data) {
                    $this->key = $key;
                    $this->data = &$data;
                    if (isset($data[$key]) && $data[$key]['expires'] > time()) {
                        $this->value = $data[$key]['value'];
                        $this->hit = true;
                    }
                }
                
                public function isHit() { return $this->hit; }
                public function get() { return $this->value; }
                public function set($value) { $this->value = $value; return $this; }
                public function expiresAfter($seconds) { 
                    $this->data[$this->key] = [
                        'value' => $this->value,
                        'expires' => time() + $seconds
                    ];
                    return $this;
                }
            };
        }
        
        public function save($item) { return true; }
    };
}

/**
 * Get TRX price with caching using Guzzle HTTP
 */
function getTRXPriceWithCache($httpClient, $cache) {
    try {
        $cacheKey = 'trx_price_usd_index';
        $cachedPrice = $cache->getItem($cacheKey);
        
        if ($cachedPrice->isHit()) {
            return $cachedPrice->get();
        }
        
        // Multiple API endpoints for redundancy
        $apis = [
            [
                'url' => 'https://api.api-ninjas.com/v1/cryptoprice?symbol=TRXUSDT',
                'headers' => ['X-Api-Key' => 'jRN/iU++CJrVw0zkBf9tBg==ekPzRifWfQ8jCTFe'],
                'parser' => function($data) {
                    return isset($data['price']) ? floatval($data['price']) : null;
                }
            ],
            [
                'url' => 'https://api.coingecko.com/api/v3/simple/price?ids=tron&vs_currencies=usd',
                'headers' => [],
                'parser' => function($data) {
                    return isset($data['tron']['usd']) ? floatval($data['tron']['usd']) : null;
                }
            ]
        ];
        
        foreach ($apis as $api) {
            try {
                $response = $httpClient->get($api['url'], [
                    'headers' => $api['headers'],
                    'timeout' => 8
                ]);
                
                if ($response->getStatusCode() === 200) {
                    $data = json_decode($response->getBody()->getContents(), true);
                    $price = $api['parser']($data);
                    
                    if ($price && $price > 0) {
                        // Cache the price for 5 minutes
                        $cachedPrice->set($price);
                        $cachedPrice->expiresAfter(300);
                        $cache->save($cachedPrice);
                        
                        return $price;
                    }
                }
            } catch (RequestException $e) {
                error_log("TRX Price API Error ({$api['url']}): " . $e->getMessage());
                continue;
            }
        }
        
    } catch (Exception $e) {
        error_log("TRX Price Cache Error: " . $e->getMessage());
    }
    
    return 0.067; // Ultimate fallback price
}

/**
 * Get tokens with caching
 */
function getTokensWithCache($pdo, $cache, $limit = 20, $offset = 0, $filter = '', $search = '') {
    try {
        $cacheKey = "tokens_list_{$limit}_{$offset}_{$filter}_{$search}";
        $cachedTokens = $cache->getItem($cacheKey);
        
        if ($cachedTokens->isHit()) {
            return $cachedTokens->get();
        }
        
        $whereClause = "WHERE t.status = 'active'";
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= " AND (t.name LIKE ? OR t.symbol LIKE ? OR t.description LIKE ?)";
            $searchParam = "%{$search}%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
        }
        
        $orderClause = "ORDER BY t.launch_time DESC";
        
        if ($filter === 'trending') {
            $orderClause = "ORDER BY COALESCE(volume_24h, 0) DESC";
        } elseif ($filter === 'graduated') {
            $whereClause .= " AND t.is_graduated = 1";
        } elseif ($filter === 'market_cap') {
            $orderClause = "ORDER BY t.market_cap DESC";
        }
        
        $stmt = $pdo->prepare("
            SELECT t.*, bc.current_progress, bc.graduation_threshold, bc.real_trx_reserves,
                   u.username as creator_username,
                   (SELECT COUNT(DISTINCT user_id) FROM token_balances WHERE token_id = t.id) as holder_count,
                   (SELECT SUM(trx_amount) FROM token_transactions WHERE token_id = t.id AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as volume_24h,
                   (SELECT price_per_token FROM token_transactions WHERE token_id = t.id AND status = 'confirmed' 
                    AND created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY created_at DESC LIMIT 1) as price_24h_ago
            FROM tokens t
            LEFT JOIN bonding_curves bc ON t.id = bc.token_id
            LEFT JOIN users2 u ON t.creator_id = u.id
            {$whereClause}
            {$orderClause}
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $tokens = $stmt->fetchAll();
        
        // Calculate additional data
        foreach ($tokens as &$token) {
            $price_24h_ago = floatval($token['price_24h_ago'] ?? $token['current_price']);
            if ($price_24h_ago > 0) {
                $token['price_change_24h'] = (($token['current_price'] - $price_24h_ago) / $price_24h_ago) * 100;
            } else {
                $token['price_change_24h'] = 0;
            }
            
            $token['progress_percentage'] = min(100, ($token['real_trx_reserves'] / max(1, $token['graduation_threshold'])) * 100);
            $token['volume_24h'] = $token['volume_24h'] ?? 0;
            $token['holder_count'] = $token['holder_count'] ?? 0;
        }
        
        // Cache for 2 minutes
        $cachedTokens->set($tokens);
        $cachedTokens->expiresAfter(120);
        $cache->save($cachedTokens);
        
        return $tokens;
        
    } catch (Exception $e) {
        error_log("Tokens Cache Error: " . $e->getMessage());
        return [];
    }
}

// Get TRX price
$trx_price = getTRXPriceWithCache($httpClient, $cache);

// Handle AJAX requests
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'load_tokens':
            $limit = intval($_GET['limit'] ?? 20);
            $offset = intval($_GET['offset'] ?? 0);
            $filter = $_GET['filter'] ?? '';
            $search = $_GET['search'] ?? '';
            
            $tokens = getTokensWithCache($pdo, $cache, $limit, $offset, $filter, $search);
            
            echo json_encode([
                'success' => true,
                'tokens' => $tokens,
                'trx_price' => $trx_price
            ]);
            exit;
            
        case 'get_stats':
            try {
                $cacheKey = 'platform_stats';
                $cachedStats = $cache->getItem($cacheKey);
                
                if (!$cachedStats->isHit()) {
                    $stmt = $pdo->prepare("
                        SELECT 
                            COUNT(*) as total_tokens,
                            SUM(market_cap) as total_market_cap,
                            SUM(COALESCE((SELECT SUM(trx_amount) FROM token_transactions WHERE token_id = tokens.id AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)), 0)) as total_volume_24h,
                            COUNT(DISTINCT creator_id) as total_creators
                        FROM tokens 
                        WHERE status = 'active'
                    ");
                    $stmt->execute();
                    $stats = $stmt->fetch();
                    
                    $cachedStats->set($stats);
                    $cachedStats->expiresAfter(300); // 5 minutes
                    $cache->save($cachedStats);
                } else {
                    $stats = $cachedStats->get();
                }
                
                echo json_encode([
                    'success' => true,
                    'stats' => $stats
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to load stats'
                ]);
            }
            exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

// Initial page load - get first batch of tokens
$initial_tokens = getTokensWithCache($pdo, $cache, 20, 0);

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TronPump - Premium Token Trading Platform</title>
    <meta name="description" content="Trade TRC-20 tokens with advanced bonding curve technology on TronPump">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #000;
            color: #fff;
            line-height: 1.5;
            font-weight: 400;
            overflow-x: hidden;
        }

        /* Premium Background */
        .background-gradient {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(ellipse at top, rgba(120, 119, 198, 0.1) 0%, transparent 50%),
                        radial-gradient(ellipse at bottom, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            position: relative;
            z-index: 1;
        }

        /* Hero Section */
        .hero-section {
            padding: 80px 0 60px;
            text-align: center;
            position: relative;
        }

        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 700;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #fff 0%, rgba(255,255,255,0.7) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            font-weight: 500;
            color: rgba(255,255,255,0.7);
            margin-bottom: 32px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Stats Bar */
        .stats-bar {
            background: rgba(17, 17, 17, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 48px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
            font-family: 'SF Mono', Monaco, monospace;
        }

        .stat-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: rgba(255,255,255,0.6);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Search and Filters */
        .search-section {
            margin-bottom: 40px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .search-container {
            position: relative;
            max-width: 500px;
            margin: 0 auto;
        }

        .search-input {
            width: 100%;
            background: rgba(17, 17, 17, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            padding: 16px 20px 16px 48px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: rgba(255,255,255,0.3);
            box-shadow: 0 0 0 4px rgba(255,255,255,0.1);
        }

        .search-input::placeholder {
            color: rgba(255,255,255,0.5);
            font-weight: 400;
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: rgba(255,255,255,0.5);
        }

        .filters {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: rgba(17, 17, 17, 0.6);
            border: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.7);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border-color: rgba(255,255,255,0.2);
            transform: translateY(-1px);
        }

        /* Token Grid */
        .tokens-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 48px;
        }

        .token-card {
            background: rgba(17, 17, 17, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .token-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255,255,255,0.2);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .token-image-container {
            height: 200px;
            background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .token-image {
            max-width: 80%;
            max-height: 80%;
            object-fit: contain;
            border-radius: 8px;
        }

        .token-placeholder {
            font-size: 3rem;
            font-weight: 700;
            color: rgba(255,255,255,0.3);
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .price-change {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
            backdrop-filter: blur(10px);
        }

        .price-up {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .price-down {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .price-neutral {
            background: rgba(156, 163, 175, 0.2);
            color: #9ca3af;
            border: 1px solid rgba(156, 163, 175, 0.3);
        }

        .token-content {
            padding: 24px;
        }

        .token-creator {
            color: rgba(255,255,255,0.5);
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .token-name {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 4px;
            color: #fff;
        }

        .token-symbol {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 16px;
            text-transform: uppercase;
        }

        .token-description {
            color: rgba(255,255,255,0.6);
            font-size: 14px;
            font-weight: 400;
            line-height: 1.5;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .token-progress {
            margin-bottom: 20px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 500;
        }

        .progress-label {
            color: rgba(255,255,255,0.6);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .progress-value {
            color: #fff;
            font-weight: 600;
        }

        .progress-bar {
            height: 6px;
            background: rgba(255,255,255,0.1);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #fff 0%, rgba(255,255,255,0.7) 100%);
            border-radius: 3px;
            transition: width 0.6s ease;
        }

        .token-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .token-stat {
            background: rgba(255,255,255,0.05);
            padding: 12px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value-token {
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 2px;
            font-family: 'SF Mono', Monaco, monospace;
        }

        .stat-label-token {
            color: rgba(255,255,255,0.5);
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Loading States */
        .loading-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 60px 20px;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255,255,255,0.1);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: rgba(255,255,255,0.6);
        }

        .empty-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 24px;
            color: rgba(255,255,255,0.3);
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: rgba(255,255,255,0.8);
        }

        .empty-description {
            font-size: 1rem;
            font-weight: 400;
            color: rgba(255,255,255,0.5);
        }

        /* Load More Button */
        .load-more-container {
            text-align: center;
            margin: 40px 0;
        }

        .load-more-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            padding: 12px 32px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .load-more-btn:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-1px);
        }

        .load-more-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 0 16px;
            }

            .hero-section {
                padding: 60px 0 40px;
            }

            .stats-bar {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
                padding: 20px;
            }

            .tokens-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .search-section {
                margin-bottom: 32px;
            }

            .filters {
                gap: 8px;
            }

            .filter-btn {
                padding: 8px 16px;
                font-size: 13px;
            }

            .token-content {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .stats-bar {
                grid-template-columns: 1fr;
            }

            .stat-value {
                font-size: 1.5rem;
            }

            .token-stats {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .token-card {
            animation: fadeInUp 0.6s ease forwards;
        }

        .token-card:nth-child(even) {
            animation-delay: 0.1s;
        }

        .token-card:nth-child(3n) {
            animation-delay: 0.2s;
        }
    </style>
</head>
    <?php include 'includes/header.php'; ?>

<body>
    <div class="background-gradient"></div>
    

    <div class="container">
        <!-- Hero Section -->
        <section class="hero-section">
            <h1 class="hero-title">Trade Premium Tokens</h1>
            <p class="hero-subtitle">Discover and trade the next generation of TRC-20 tokens with advanced bonding curve technology</p>
        </section>

        <!-- Stats Bar -->
        <div class="stats-bar" id="stats-bar">
            <div class="stat-item">
                <div class="stat-value" id="total-tokens">-</div>
                <div class="stat-label">Total Tokens</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" id="total-market-cap">-</div>
                <div class="stat-label">Market Cap</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" id="total-volume">-</div>
                <div class="stat-label">24h Volume</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" id="total-creators">-</div>
                <div class="stat-label">Creators</div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="search-section">
            <div class="search-container">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" id="search-input" class="search-input" placeholder="Search tokens by name, symbol, or description...">
            </div>

            <div class="filters">
                <button class="filter-btn active" data-filter="">All Tokens</button>
                <button class="filter-btn" data-filter="trending">Trending</button>
                <button class="filter-btn" data-filter="graduated">Graduated</button>
                <button class="filter-btn" data-filter="market_cap">Market Cap</button>
                <button class="filter-btn" data-filter="latest">Latest</button>
            </div>
        </div>

        <!-- Tokens Grid -->
        <div class="tokens-grid" id="tokens-grid">
            <!-- Tokens will be loaded here via AJAX -->
        </div>

        <!-- Load More Button -->
        <div class="load-more-container">
            <button class="load-more-btn" id="load-more-btn">Load More Tokens</button>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        class TronPumpApp {
            constructor() {
                this.currentFilter = '';
                this.currentSearch = '';
                this.currentOffset = 0;
                this.loading = false;
                this.hasMore = true;
                this.trxPrice = <?php echo $trx_price; ?>;
                
                this.init();
            }

            init() {
                this.loadInitialTokens();
                this.loadStats();
                this.bindEvents();
                this.startAutoRefresh();
            }

            bindEvents() {
                // Search input
                const searchInput = document.getElementById('search-input');
                let searchTimeout;
                searchInput.addEventListener('input', (e) => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        this.currentSearch = e.target.value;
                        this.resetAndLoad();
                    }, 500);
                });

                // Filter buttons
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                        e.target.classList.add('active');
                        this.currentFilter = e.target.dataset.filter;
                        this.resetAndLoad();
                    });
                });

                // Load more button
                document.getElementById('load-more-btn').addEventListener('click', () => {
                    this.loadMoreTokens();
                });

                // Token card clicks
                document.addEventListener('click', (e) => {
                    const tokenCard = e.target.closest('.token-card');
                    if (tokenCard) {
                        const tokenId = tokenCard.dataset.tokenId;
                        if (tokenId) {
                            window.location.href = `user/order.php?token_id=${tokenId}`;
                        }
                    }
                });
            }

            async loadInitialTokens() {
                const initialTokens = <?php echo json_encode($initial_tokens); ?>;
                this.renderTokens(initialTokens, false);
                this.currentOffset = initialTokens.length;
            }

            async loadStats() {
                try {
                    const response = await fetch(`?ajax=1&action=get_stats`);
                    const data = await response.json();
                    
                    if (data.success) {
                        this.updateStats(data.stats);
                    }
                } catch (error) {
                    console.error('Failed to load stats:', error);
                }
            }

            updateStats(stats) {
                document.getElementById('total-tokens').textContent = this.formatNumber(stats.total_tokens);
                document.getElementById('total-market-cap').textContent = this.formatNumber(stats.total_market_cap) + ' TRX';
                document.getElementById('total-volume').textContent = this.formatNumber(stats.total_volume_24h) + ' TRX';
                document.getElementById('total-creators').textContent = this.formatNumber(stats.total_creators);
            }

            async resetAndLoad() {
                this.currentOffset = 0;
                this.hasMore = true;
                document.getElementById('tokens-grid').innerHTML = '';
                await this.loadTokens();
            }

            async loadMoreTokens() {
                if (!this.hasMore || this.loading) return;
                await this.loadTokens();
            }

            async loadTokens() {
                if (this.loading) return;
                
                this.loading = true;
                const loadMoreBtn = document.getElementById('load-more-btn');
                loadMoreBtn.disabled = true;
                loadMoreBtn.innerHTML = '<div class="loading-spinner"></div>';

                try {
                    const params = new URLSearchParams({
                        ajax: '1',
                        action: 'load_tokens',
                        limit: '20',
                        offset: this.currentOffset.toString(),
                        filter: this.currentFilter,
                        search: this.currentSearch
                    });

                    const response = await fetch(`?${params}`);
                    const data = await response.json();

                    if (data.success) {
                        this.renderTokens(data.tokens, this.currentOffset > 0);
                        this.currentOffset += data.tokens.length;
                        this.hasMore = data.tokens.length === 20;
                        this.trxPrice = data.trx_price;
                    } else {
                        this.showError('Failed to load tokens');
                    }
                } catch (error) {
                    console.error('Failed to load tokens:', error);
                    this.showError('Network error occurred');
                } finally {
                    this.loading = false;
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.textContent = this.hasMore ? 'Load More Tokens' : 'No More Tokens';
                }
            }

            renderTokens(tokens, append = false) {
                const grid = document.getElementById('tokens-grid');
                
                if (!append) {
                    grid.innerHTML = '';
                }

                if (tokens.length === 0 && !append) {
                    grid.innerHTML = this.getEmptyState();
                    return;
                }

                tokens.forEach((token, index) => {
                    const tokenCard = this.createTokenCard(token);
                    tokenCard.style.animationDelay = `${index * 0.1}s`;
                    grid.appendChild(tokenCard);
                });
            }

            createTokenCard(token) {
                const card = document.createElement('div');
                card.className = 'token-card';
                card.dataset.tokenId = token.id;

                const priceChangeClass = token.price_change_24h > 0 ? 'price-up' : 
                                       token.price_change_24h < 0 ? 'price-down' : 'price-neutral';
                
                const priceChangeIcon = token.price_change_24h > 0 ? '↗' : 
                                       token.price_change_24h < 0 ? '↘' : '→';

                card.innerHTML = `
                    <div class="token-image-container">
                        ${token.image_url ? 
                            `<img src="${this.escapeHtml(token.image_url)}" alt="${this.escapeHtml(token.name)}" class="token-image">` :
                            `<div class="token-placeholder">${this.escapeHtml(token.symbol.substring(0, 2))}</div>`
                        }
                        <div class="price-change ${priceChangeClass}">
                            ${priceChangeIcon} ${Math.abs(token.price_change_24h).toFixed(1)}%
                        </div>
                    </div>
                    <div class="token-content">
                        <div class="token-creator">Created by ${this.escapeHtml(token.creator_username || 'Unknown')}</div>
                        <h3 class="token-name">${this.escapeHtml(token.name)}</h3>
                        <div class="token-symbol">${this.escapeHtml(token.symbol)}</div>
                        <div class="token-description">${this.escapeHtml(token.description || 'No description available.')}</div>
                        
                        <div class="token-progress">
                            <div class="progress-header">
                                <span class="progress-label">Bonding Curve</span>
                                <span class="progress-value">${token.progress_percentage.toFixed(1)}%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${token.progress_percentage}%"></div>
                            </div>
                        </div>

                        <div class="token-stats">
                            <div class="token-stat">
                                <div class="stat-value-token">${this.formatPrice(token.current_price)} TRX</div>
                                <div class="stat-label-token">Price</div>
                            </div>
                            <div class="token-stat">
                                <div class="stat-value-token">${this.formatNumber(token.market_cap)} TRX</div>
                                <div class="stat-label-token">Market Cap</div>
                            </div>
                            <div class="token-stat">
                                <div class="stat-value-token">${this.formatNumber(token.holder_count)}</div>
                                <div class="stat-label-token">Holders</div>
                            </div>
                            <div class="token-stat">
                                <div class="stat-value-token">${this.formatNumber(token.volume_24h)} TRX</div>
                                <div class="stat-label-token">24h Volume</div>
                            </div>
                        </div>
                    </div>
                `;

                return card;
            }

            getEmptyState() {
                return `
                    <div style="grid-column: 1 / -1;">
                        <div class="empty-state">
                            <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.35-4.35"/>
                            </svg>
                            <h3 class="empty-title">No tokens found</h3>
                            <p class="empty-description">Try adjusting your search or filters to find what you're looking for</p>
                        </div>
                    </div>
                `;
            }

            formatNumber(num) {
                if (num >= 1000000) {
                    return (num / 1000000).toFixed(1) + 'M';
                } else if (num >= 1000) {
                    return (num / 1000).toFixed(1) + 'K';
                }
                return num.toString();
            }

            formatPrice(price) {
                return parseFloat(price).toFixed(6);
            }

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            showError(message) {
                console.error(message);
                // Could implement toast notifications here
            }

            startAutoRefresh() {
                // Refresh stats every 5 minutes
                setInterval(() => {
                    this.loadStats();
                }, 5 * 60 * 1000);
            }
        }

        // Initialize the app when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new TronPumpApp();
        });
    </script>
</body>
</html>
