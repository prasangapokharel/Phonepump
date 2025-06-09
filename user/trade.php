<?php
session_start();
require_once "../connect/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Include Composer autoloader for Guzzle and Symfony Cache
require_once "../vendor/autoload.php";

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Initialize Guzzle HTTP Client
$httpClient = new Client([
    'timeout' => 10,
    'connect_timeout' => 5,
    'headers' => [
        'User-Agent' => 'TRX-Wallet/1.0',
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
    ]
]);

// Initialize Cache with fallback
try {
    $cache = new FilesystemAdapter(
        'trx_trade_cache',
        3600, // Default TTL: 1 hour
        '../cache'
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

// Get user wallet
$stmt = $pdo->prepare("SELECT * FROM trxbalance WHERE user_id = ?");
$stmt->execute([$user_id]);
$wallet = $stmt->fetch();

// Get company settings with caching
function getCompanySettingsWithCache($pdo, $cache) {
    try {
        $cacheKey = 'company_settings';
        $cachedSettings = $cache->getItem($cacheKey);
        
        if ($cachedSettings->isHit()) {
            return $cachedSettings->get();
        }
        
        $stmt = $pdo->prepare("SELECT setting_name, setting_value FROM company_settings");
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_name']] = $row['setting_value'];
        }
        
        // Cache for 1 hour
        $cachedSettings->set($settings);
        $cachedSettings->expiresAfter(3600);
        $cache->save($cachedSettings);
        
        return $settings;
        
    } catch (Exception $e) {
        error_log("Company Settings Cache Error: " . $e->getMessage());
        // Fallback to direct database query
        $stmt = $pdo->prepare("SELECT setting_name, setting_value FROM company_settings");
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_name']] = $row['setting_value'];
        }
        return $settings;
    }
}

$settings = getCompanySettingsWithCache($pdo, $cache);
$trading_fee = floatval($settings['trading_fee_trx'] ?? 10);
$company_wallet = $settings['company_wallet_address'] ?? 'TCompanyWallet123';

$error = "";
$success = "";

/**
 * Get TRX price with caching using Guzzle HTTP
 */
function getTRXPriceWithCache($httpClient, $cache) {
    try {
        $cacheKey = 'trx_price_usd_trade';
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
            ],
            [
                'url' => 'https://api.coinbase.com/v2/exchange-rates?currency=TRX',
                'headers' => [],
                'parser' => function($data) {
                    return isset($data['data']['rates']['USD']) ? floatval($data['data']['rates']['USD']) : null;
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
        
        // If all APIs fail, return cached value if exists (even if expired)
        $expiredCache = $cache->getItem('trx_price_fallback_trade');
        if ($expiredCache->isHit()) {
            return $expiredCache->get();
        }
        
    } catch (Exception $e) {
        error_log("TRX Price Cache Error: " . $e->getMessage());
    }
    
    return 0.067; // Ultimate fallback price
}

/**
 * Get market data with caching
 */
function getMarketDataWithCache($httpClient, $cache) {
    try {
        $cacheKey = 'trx_market_data_trade';
        $cachedData = $cache->getItem($cacheKey);
        
        if ($cachedData->isHit()) {
            return $cachedData->get();
        }
        
        $response = $httpClient->get('https://api.coingecko.com/api/v3/coins/tron', [
            'query' => [
                'localization' => 'false',
                'tickers' => 'false',
                'market_data' => 'true',
                'community_data' => 'false',
                'developer_data' => 'false',
                'sparkline' => 'false'
            ]
        ]);
        
        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody()->getContents(), true);
            
            $marketData = [
                'price' => $data['market_data']['current_price']['usd'] ?? 0.067,
                'price_change_24h' => $data['market_data']['price_change_percentage_24h'] ?? 0,
                'market_cap' => $data['market_data']['market_cap']['usd'] ?? 0,
                'volume_24h' => $data['market_data']['total_volume']['usd'] ?? 0
            ];
            
            // Cache for 10 minutes
            $cachedData->set($marketData);
            $cachedData->expiresAfter(600);
            $cache->save($cachedData);
            
            return $marketData;
        }
        
    } catch (Exception $e) {
        error_log("Market Data Error: " . $e->getMessage());
    }
    
    return [
        'price' => 0.067,
        'price_change_24h' => 0,
        'market_cap' => 0,
        'volume_24h' => 0
    ];
}

// Get TRX price and market data
$trx_price = getTRXPriceWithCache($httpClient, $cache);
$market_data = getMarketDataWithCache($httpClient, $cache);

// Handle buy/sell transactions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trade_action'])) {
    $token_id = intval($_POST['token_id']);
    $action = $_POST['trade_action']; // 'buy' or 'sell'
    $amount = floatval($_POST['amount']);
    
    if ($amount <= 0) {
        $error = "Invalid amount.";
    } else {
        try {
            // Get token and bonding curve info with caching
            $cacheKey = "token_data_{$token_id}";
            $cachedToken = $cache->getItem($cacheKey);
            
            if (!$cachedToken->isHit()) {
                $stmt = $pdo->prepare("
                    SELECT t.*, bc.*, 
                           (SELECT COUNT(*) FROM token_transactions WHERE token_id = t.id AND transaction_type = 'buy' AND user_id != t.creator_id) as external_buys
                    FROM tokens t 
                    JOIN bonding_curves bc ON t.id = bc.token_id 
                    WHERE t.id = ?
                ");
                $stmt->execute([$token_id]);
                $token = $stmt->fetch();
                
                if ($token) {
                    // Cache for 30 seconds
                    $cachedToken->set($token);
                    $cachedToken->expiresAfter(30);
                    $cache->save($cachedToken);
                }
            } else {
                $token = $cachedToken->get();
            }
            
            if (!$token) {
                $error = "Token not found.";
            } else {
                if ($action === 'buy') {
                    // Calculate price based on bonding curve
                    $price_per_token = $token['current_price'];
                    $total_cost = $amount * $price_per_token;
                    $total_with_fee = $total_cost + $trading_fee;
                    
                    if ($wallet['balance'] < $total_with_fee) {
                        $error = "Insufficient TRX balance. You need " . number_format($total_with_fee, 4) . " TRX (including " . $trading_fee . " TRX trading fee).";
                    } else {
                        // Process buy transaction
                        $pdo->beginTransaction();
                        
                        // Update user TRX balance
                        $stmt = $pdo->prepare("UPDATE trxbalance SET balance = balance - ? WHERE user_id = ?");
                        $stmt->execute([$total_with_fee, $user_id]);
                        
                        // Update token reserves
                        $stmt = $pdo->prepare("
                            UPDATE bonding_curves SET 
                                real_trx_reserves = real_trx_reserves + ?,
                                tokens_available = tokens_available - ?,
                                tokens_sold = tokens_sold + ?,
                                current_progress = LEAST(100.0, (real_trx_reserves + ?) / graduation_threshold * 100)
                            WHERE token_id = ?
                        ");
                        $stmt->execute([$total_cost, $amount, $amount, $total_cost, $token_id]);
                        
                        // Update or insert user token balance
                        $stmt = $pdo->prepare("
                            INSERT INTO token_balances (token_id, user_id, balance, first_purchase_at, last_transaction_at, total_bought)
                            VALUES (?, ?, ?, NOW(), NOW(), ?)
                            ON DUPLICATE KEY UPDATE
                                balance = balance + VALUES(balance),
                                last_transaction_at = NOW(),
                                total_bought = total_bought + VALUES(total_bought)
                        ");
                        $stmt->execute([$token_id, $user_id, $amount, $amount]);
                        
                        // Record transaction
                        $tx_hash = 'tx_' . uniqid();
                        $stmt = $pdo->prepare("
                            INSERT INTO token_transactions (
                                token_id, user_id, transaction_hash, transaction_type,
                                trx_amount, token_amount, price_per_token, fee_amount,
                                status, created_at
                            ) VALUES (?, ?, ?, 'buy', ?, ?, ?, ?, 'confirmed', NOW())
                        ");
                        $stmt->execute([$token_id, $user_id, $tx_hash, $total_cost, $amount, $price_per_token, $trading_fee]);
                        
                        // Update token stats
                        $stmt = $pdo->prepare("
                            UPDATE tokens SET 
                                current_price = ?,
                                market_cap = ? * total_supply,
                                total_transactions = total_transactions + 1,
                                volume_total = volume_total + ?,
                                volume_24h = volume_24h + ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$price_per_token, $price_per_token, $total_cost, $total_cost, $token_id]);
                        
                        $pdo->commit();
                        
                        // Clear relevant caches
                        $cache->getItem("token_data_{$token_id}")->set(null);
                        $cache->getItem("trending_tokens_{$user_id}")->set(null);
                        $cache->getItem("user_portfolio_{$user_id}")->set(null);
                        
                        $success = "Successfully bought " . number_format($amount, 2) . " tokens for " . number_format($total_cost, 4) . " TRX (+ " . $trading_fee . " TRX fee)";
                    }
                } else { // sell
                    // Get user's token balance
                    $stmt = $pdo->prepare("SELECT balance FROM token_balances WHERE token_id = ? AND user_id = ?");
                    $stmt->execute([$token_id, $user_id]);
                    $user_balance = $stmt->fetchColumn();
                    
                    if ($user_balance < $amount) {
                        $error = "Insufficient token balance.";
                    } else {
                        $price_per_token = $token['current_price'];
                        $trx_received = $amount * $price_per_token;
                        $total_after_fee = $trx_received - $trading_fee;
                        
                        if ($total_after_fee <= 0) {
                            $error = "Transaction amount too small to cover trading fee.";
                        } else {
                            // Process sell transaction
                            $pdo->beginTransaction();
                            
                            // Update user TRX balance
                            $stmt = $pdo->prepare("UPDATE trxbalance SET balance = balance + ? WHERE user_id = ?");
                            $stmt->execute([$total_after_fee, $user_id]);
                            
                            // Update token reserves
                            $stmt = $pdo->prepare("
                                UPDATE bonding_curves SET 
                                    real_trx_reserves = real_trx_reserves - ?,
                                    tokens_available = tokens_available + ?,
                                    tokens_sold = tokens_sold - ?,
                                    current_progress = LEAST(100.0, (real_trx_reserves - ?) / graduation_threshold * 100)
                                WHERE token_id = ?
                            ");
                            $stmt->execute([$trx_received, $amount, $amount, $trx_received, $token_id]);
                            
                            // Update user token balance
                            $stmt = $pdo->prepare("
                                UPDATE token_balances SET 
                                    balance = balance - ?,
                                    last_transaction_at = NOW(),
                                    total_sold = total_sold + ?
                                WHERE token_id = ? AND user_id = ?
                            ");
                            $stmt->execute([$amount, $amount, $token_id, $user_id]);
                            
                            // Record transaction
                            $tx_hash = 'tx_' . uniqid();
                            $stmt = $pdo->prepare("
                                INSERT INTO token_transactions (
                                    token_id, user_id, transaction_hash, transaction_type,
                                    trx_amount, token_amount, price_per_token, fee_amount,
                                    status, created_at
                                ) VALUES (?, ?, ?, 'sell', ?, ?, ?, ?, 'confirmed', NOW())
                            ");
                            $stmt->execute([$token_id, $user_id, $tx_hash, $trx_received, $amount, $price_per_token, $trading_fee]);
                            
                            $pdo->commit();
                            
                            // Clear relevant caches
                            $cache->getItem("token_data_{$token_id}")->set(null);
                            $cache->getItem("trending_tokens_{$user_id}")->set(null);
                            $cache->getItem("user_portfolio_{$user_id}")->set(null);
                            
                            $success = "Successfully sold " . number_format($amount, 2) . " tokens for " . number_format($total_after_fee, 4) . " TRX (after " . $trading_fee . " TRX fee)";
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Transaction failed: " . $e->getMessage();
        }
    }
}

// Get trending tokens with 24h price change and caching
function getTrendingTokensWithCache($pdo, $user_id, $cache) {
    try {
        $cacheKey = "trending_tokens_{$user_id}";
        $cachedTokens = $cache->getItem($cacheKey);
        
        if ($cachedTokens->isHit()) {
            return $cachedTokens->get();
        }
        
        $stmt = $pdo->prepare("
            SELECT t.*, bc.current_progress, bc.real_trx_reserves, bc.tokens_sold, bc.tokens_available,
                   COALESCE(tb.balance, 0) as user_balance,
                   (SELECT COUNT(*) FROM token_transactions WHERE token_id = t.id AND transaction_type = 'buy' AND user_id != t.creator_id) as external_buys,
                   COALESCE(
                       (SELECT AVG(price_per_token) 
                        FROM token_transactions 
                        WHERE token_id = t.id 
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                       ), t.current_price
                   ) as price_24h_ago,
                   CASE 
                       WHEN COALESCE(
                           (SELECT AVG(price_per_token) 
                            FROM token_transactions 
                            WHERE token_id = t.id 
                            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                           ), t.current_price
                       ) > 0 
                       THEN ((t.current_price - COALESCE(
                           (SELECT AVG(price_per_token) 
                            FROM token_transactions 
                            WHERE token_id = t.id 
                            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                           ), t.current_price
                       )) / COALESCE(
                           (SELECT AVG(price_per_token) 
                            FROM token_transactions 
                            WHERE token_id = t.id 
                            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                           ), t.current_price
                       )) * 100
                       ELSE 0
                   END as price_change_24h
            FROM tokens t 
            LEFT JOIN bonding_curves bc ON t.id = bc.token_id 
            LEFT JOIN token_balances tb ON t.id = tb.token_id AND tb.user_id = ?
            WHERE t.status = 'active' 
            ORDER BY t.volume_24h DESC, t.market_cap DESC 
            LIMIT 20
        ");
        $stmt->execute([$user_id]);
        $tokens = $stmt->fetchAll();
        
        // Cache for 2 minutes
        $cachedTokens->set($tokens);
        $cachedTokens->expiresAfter(120);
        $cache->save($cachedTokens);
        
        return $tokens;
        
    } catch (Exception $e) {
        error_log("Trending Tokens Cache Error: " . $e->getMessage());
        // Fallback to direct query
        $stmt = $pdo->prepare("
            SELECT t.*, bc.current_progress, bc.real_trx_reserves, bc.tokens_sold, bc.tokens_available,
                   COALESCE(tb.balance, 0) as user_balance,
                   0 as external_buys, t.current_price as price_24h_ago, 0 as price_change_24h
            FROM tokens t 
            LEFT JOIN bonding_curves bc ON t.id = bc.token_id 
            LEFT JOIN token_balances tb ON t.id = tb.token_id AND tb.user_id = ?
            WHERE t.status = 'active' 
            ORDER BY t.volume_24h DESC, t.market_cap DESC 
            LIMIT 20
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
}

// Get user's portfolio with caching
function getUserPortfolioWithCache($pdo, $user_id, $cache) {
    try {
        $cacheKey = "user_portfolio_{$user_id}";
        $cachedPortfolio = $cache->getItem($cacheKey);
        
        if ($cachedPortfolio->isHit()) {
            return $cachedPortfolio->get();
        }
        
        $stmt = $pdo->prepare("
            SELECT tb.*, t.name, t.symbol, t.current_price, t.image_url, t.creator_id, t.initial_buy_amount,
                   (tb.balance * t.current_price) as current_value,
                   (SELECT COUNT(*) FROM token_transactions WHERE token_id = t.id AND transaction_type = 'buy' AND user_id != t.creator_id) as external_buys
            FROM token_balances tb
            JOIN tokens t ON tb.token_id = t.id
            WHERE tb.user_id = ? AND tb.balance > 0
            ORDER BY current_value DESC
        ");
        $stmt->execute([$user_id]);
        $portfolio = $stmt->fetchAll();
        
        // Cache for 1 minute
        $cachedPortfolio->set($portfolio);
        $cachedPortfolio->expiresAfter(60);
        $cache->save($cachedPortfolio);
        
        return $portfolio;
        
    } catch (Exception $e) {
        error_log("Portfolio Cache Error: " . $e->getMessage());
        // Fallback to direct query
        $stmt = $pdo->prepare("
            SELECT tb.*, t.name, t.symbol, t.current_price, t.image_url, t.creator_id, t.initial_buy_amount,
                   (tb.balance * t.current_price) as current_value, 0 as external_buys
            FROM token_balances tb
            JOIN tokens t ON tb.token_id = t.id
            WHERE tb.user_id = ? AND tb.balance > 0
            ORDER BY current_value DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
}

$tokens = getTrendingTokensWithCache($pdo, $user_id, $cache);
$portfolio = getUserPortfolioWithCache($pdo, $user_id, $cache);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trade Tokens</title>
    <link rel="stylesheet" href="../assets/css/trade.css">

</head>
<body>
    <div class="app">
        <div class="content">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (!$wallet): ?>
                <div class="alert alert-warning">
                    Please create a wallet first to start trading.
                    <a href="../dashboard.php" style="color: #ffc107; text-decoration: underline;">Create Wallet</a>
                </div>
            <?php else: ?>
                <div class="balance-display">
                    <div class="balance-amount"><?php echo number_format($wallet['balance'], 2); ?> TRX</div>
                    <div class="balance-label">Available Balance</div>
                </div>

                <div class="market-info">
                    <div>
                        <div class="market-price">TRX: $<?php echo number_format($trx_price, 4); ?></div>
                    </div>
                    <div class="market-change <?php echo $market_data['price_change_24h'] >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo ($market_data['price_change_24h'] >= 0 ? '+' : '') . number_format($market_data['price_change_24h'], 2); ?>%
                    </div>
                </div>

                <div class="trade-tabs">
                    <button class="tab-btn active" onclick="switchTab('market')">Market</button>
                    <button class="tab-btn" onclick="switchTab('portfolio')">Portfolio</button>
                </div>

                <div id="market-tab" class="tab-content active">
                    <h2 class="section-title">Trending Tokens</h2>
                    <div class="token-list">
                        <?php foreach ($tokens as $token): ?>
                            <div class="token-item" onclick="window.location.href='order.php?token_id=<?php echo $token['id']; ?>'">
                                <div class="token-icon">
                                    <?php if ($token['image_url']): ?>
                                        <img src="../<?php echo htmlspecialchars($token['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($token['name']); ?>">
                                    <?php else: ?>
                                        <?php echo substr($token['symbol'], 0, 2); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="token-info">
                                    <div class="token-name">
                                        <?php echo htmlspecialchars($token['name']); ?>
                                        <?php if ($token['user_balance'] > 0): ?>
                                            <span class="user-balance-badge"><?php echo number_format($token['user_balance'], 0); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="token-symbol"><?php echo htmlspecialchars($token['symbol']); ?></div>
                                    <div class="bonding-progress">Bonding Curve <?php echo number_format($token['current_progress'], 1); ?>%</div>
                                </div>
                                <div class="token-price-info">
                                    <div class="token-price"><?php echo number_format($token['current_price'], 6); ?> TRX</div>
                                    <div class="price-change <?php 
                                        if ($token['price_change_24h'] > 0) echo 'positive';
                                        elseif ($token['price_change_24h'] < 0) echo 'negative';
                                        else echo 'neutral';
                                    ?>">
                                        <?php 
                                        if ($token['price_change_24h'] > 0) echo '+';
                                        echo number_format($token['price_change_24h'], 2); 
                                        ?>%
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="portfolio-tab" class="tab-content">
                    <h2 class="section-title">Your Portfolio</h2>
                    <?php if (empty($portfolio)): ?>
                        <div class="empty-state">
                            <div class="empty-text">No tokens in your portfolio yet</div>
                            <button onclick="switchTab('market')" class="start-trading-btn">Start Trading</button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($portfolio as $holding): ?>
                            <div class="portfolio-item" onclick="window.location.href='order.php?token_id=<?php echo $holding['token_id']; ?>'">
                                <div class="portfolio-header">
                                    <div class="token-icon">
                                        <?php if ($holding['image_url']): ?>
                                            <img src="../<?php echo htmlspecialchars($holding['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($holding['name']); ?>">
                                        <?php else: ?>
                                            <?php echo substr($holding['symbol'], 0, 2); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="token-info">
                                        <div class="token-name"><?php echo htmlspecialchars($holding['name']); ?></div>
                                        <div class="token-symbol"><?php echo htmlspecialchars($holding['symbol']); ?></div>
                                    </div>
                                </div>

                                <div class="portfolio-stats">
                                    <div>
                                        <div class="stat-value"><?php echo number_format($holding['balance'], 2); ?></div>
                                        <div class="stat-label">Balance</div>
                                    </div>
                                    <div>
                                        <div class="stat-value"><?php echo number_format($holding['current_price'], 6); ?></div>
                                        <div class="stat-label">Price (TRX)</div>
                                    </div>
                                    <div>
                                        <div class="stat-value"><?php echo number_format($holding['current_value'], 4); ?></div>
                                        <div class="stat-label">Value (TRX)</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php include '../includes/bottomnav.php'; ?>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            document.querySelectorAll('.tab-btn').forEach(btn => {
                if (btn.textContent.toLowerCase() === tabName) {
                    btn.classList.add('active');
                }
            });
        }

        // Auto-refresh token prices every 2 minutes (cache-friendly)
        setInterval(function() {
            // Only refresh if user is active
            if (!document.hidden) {
                location.reload();
            }
        }, 120000); // 2 minutes

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 300);
                }, 5000);
            });
        });

        // Cache warming on page visibility
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                // Warm up cache by making a background request
                fetch(window.location.href, {
                    method: 'HEAD',
                    cache: 'no-cache'
                }).catch(() => {
                    // Ignore errors, this is just cache warming
                });
            }
        });

        // Show loading indicator during navigation
        document.addEventListener('click', function(e) {
            const tokenItem = e.target.closest('.token-item, .portfolio-item');
            if (tokenItem) {
                const loadingIndicator = document.createElement('div');
                loadingIndicator.className = 'loading-indicator';
                tokenItem.appendChild(loadingIndicator);
            }
        });
    </script>
</body>
</html>