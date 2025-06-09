<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Include database connection
require_once "../connect/db.php";

// Include Composer autoloader for Guzzle and Symfony Cache
require_once "../vendor/autoload.php";

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;

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

// Initialize Symfony Cache
$cache = new FilesystemAdapter(
    'trx_wallet_cache',
    3600, // Default TTL: 1 hour
    '../cache'
);

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users2 WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Check if user has a TRON wallet
$stmt = $pdo->prepare("SELECT * FROM trxbalance WHERE user_id = ?");
$stmt->execute([$user_id]);
$wallet = $stmt->fetch();

$error = "";
$success = "";

// Include wallet generator
require_once "../components/wallet_generator.php";

// Handle wallet creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_wallet'])) {
    if (!$wallet) {
        try {
            // Generate new wallet using TronWalletGenerator
            $walletResult = TronWalletGenerator::generateWallet();
            
            if ($walletResult['success']) {
                $tronAddress = $walletResult['address'];
                $privateKeyHex = $walletResult['private_key'];
                $publicKey = $walletResult['public_key'];

                // Insert generated address into database
                $stmt = $pdo->prepare("INSERT INTO trxbalance (user_id, private_key, address, username, balance, status, public_key) VALUES (?, ?, ?, ?, 0.00, 'Unpaid', ?)");
                
                if ($stmt->execute([$user_id, $privateKeyHex, $tronAddress, $username, $publicKey])) {
                    $success = "Wallet created successfully!";
                    // Refresh wallet data
                    $stmt = $pdo->prepare("SELECT * FROM trxbalance WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $wallet = $stmt->fetch();
                } else {
                    $error = "Failed to create wallet. Please try again.";
                }
            } else {
                $error = "Error creating wallet: " . $walletResult['error'];
            }
        } catch (Exception $e) {
            $error = "Error creating wallet: " . $e->getMessage();
        }
    }
}

/**
 * Get TRX price with caching using Guzzle HTTP and Symfony Cache
 */
function getTRXPriceWithCache($httpClient, $cache) {
    try {
        // Create cache key
        $cacheKey = 'trx_price_usd';
        
        // Try to get from cache first
        $cachedPrice = $cache->getItem($cacheKey);
        
        if ($cachedPrice->isHit()) {
            return $cachedPrice->get();
        }
        
        // If not in cache, fetch from API
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
                        $cachedPrice->expiresAfter(300); // 5 minutes
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
        $expiredCache = $cache->getItem('trx_price_fallback');
        if ($expiredCache->isHit()) {
            return $expiredCache->get();
        }
        
    } catch (Exception $e) {
        error_log("TRX Price Cache Error: " . $e->getMessage());
    }
    
    return 0.20; // Ultimate fallback price
}

/**
 * Get blockchain balance with caching
 */
function getBlockchainBalanceWithCache($address, $httpClient, $cache) {
    try {
        $cacheKey = 'blockchain_balance_' . md5($address);
        $cachedBalance = $cache->getItem($cacheKey);
        
        if ($cachedBalance->isHit()) {
            return $cachedBalance->get();
        }
        
        // Fetch from TronGrid API
        $response = $httpClient->get("https://api.trongrid.io/v1/accounts/{$address}", [
            'headers' => [
                'TRON-PRO-API-KEY' => '3022fab4-cd87-48c5-b5d1-65fb3e588f67'
            ]
        ]);
        
        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody()->getContents(), true);
            $balance = 0;
            
            if (isset($data['data'][0]['balance'])) {
                $balance = $data['data'][0]['balance'] / 1000000; // Convert from SUN to TRX
            }
            
            // Cache balance for 30 seconds
            $cachedBalance->set($balance);
            $cachedBalance->expiresAfter(30);
            $cache->save($cachedBalance);
            
            return $balance;
        }
        
    } catch (Exception $e) {
        error_log("Blockchain Balance Error: " . $e->getMessage());
    }
    
    return 0;
}

/**
 * Get market data with caching
 */
function getMarketDataWithCache($httpClient, $cache) {
    try {
        $cacheKey = 'trx_market_data';
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
                'price' => $data['market_data']['current_price']['usd'] ?? 0.20,
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
        'price' => 0.20,
        'price_change_24h' => 0,
        'market_cap' => 0,
        'volume_24h' => 0
    ];
}

// Get TRX price and market data
$trx_price = getTRXPriceWithCache($httpClient, $cache);
$market_data = getMarketDataWithCache($httpClient, $cache);

// Get recent transactions with caching
function getRecentTransactionsWithCache($pdo, $user_id, $cache, $limit = 10) {
    try {
        $cacheKey = "user_transactions_{$user_id}_{$limit}";
        $cachedTransactions = $cache->getItem($cacheKey);
        
        if ($cachedTransactions->isHit()) {
            return $cachedTransactions->get();
        }
        
        $stmt = $pdo->prepare("
            SELECT amount, status, timestamp, tx_hash, transaction_type
            FROM trxhistory 
            WHERE user_id = ? 
            ORDER BY timestamp DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Cache for 1 minute
        $cachedTransactions->set($transactions);
        $cachedTransactions->expiresAfter(60);
        $cache->save($cachedTransactions);
        
        return $transactions;
        
    } catch (Exception $e) {
        error_log("Transaction Cache Error: " . $e->getMessage());
        return [];
    }
}

$recent_transactions = getRecentTransactionsWithCache($pdo, $user_id, $cache);

// Helper function to format time ago
function getTimeAgo($timestamp) {
    $now = time();
    $diff = $now - strtotime($timestamp);
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}

// Get wallet statistics with caching
function getWalletStatsWithCache($pdo, $user_id, $cache) {
    try {
        $cacheKey = "wallet_stats_{$user_id}";
        $cachedStats = $cache->getItem($cacheKey);
        
        if ($cachedStats->isHit()) {
            return $cachedStats->get();
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as transaction_count,
                COALESCE(SUM(CASE WHEN status = 'send' THEN amount ELSE 0 END), 0) as total_sent,
                COALESCE(SUM(CASE WHEN status = 'receive' THEN amount ELSE 0 END), 0) as total_received
            FROM trxhistory 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Cache for 5 minutes
        $cachedStats->set($stats);
        $cachedStats->expiresAfter(300);
        $cache->save($cachedStats);
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Wallet Stats Cache Error: " . $e->getMessage());
        return [
            'total_sent' => 0,
            'total_received' => 0,
            'transaction_count' => 0
        ];
    }
}

$stats = $wallet ? getWalletStatsWithCache($pdo, $user_id, $cache) : [
    'total_sent' => 0,
    'total_received' => 0,
    'transaction_count' => 0
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRX Wallet Dashboard</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="app">
        <!-- Alerts -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="content">
            <?php if ($wallet): ?>
                <!-- Balance Section -->
                <div class="balance-section">
                    <div class="balance-label">Total TRX Balance</div>
                    <div id="balance-display" class="balance-amount">
                        <?php echo number_format($wallet['balance'], 2); ?>
                    </div>
                    <div id="balance-usd" class="balance-usd">
                        $<?php echo number_format($wallet['balance'] * $trx_price, 2); ?>
                    </div>
                    <div class="price-info">
                        <span>TRX: $<?php echo number_format($trx_price, 4); ?></span>
                        <span class="price-change <?php echo $market_data['price_change_24h'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo ($market_data['price_change_24h'] >= 0 ? '+' : '') . number_format($market_data['price_change_24h'], 2); ?>%
                        </span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="actions">
                    <a href="withdraw.php" class="action-btn">
                        <svg class="action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 19V5"/>
                            <path d="m5 12 7-7 7 7"/>
                        </svg>
                        Send
                    </a>
                    <a href="deposit.php" class="action-btn">
                        <svg class="action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14"/>
                            <path d="m19 12-7 7-7-7"/>
                        </svg>
                        Receive
                    </a>
                </div>

                <!-- Recent Transactions -->
                <div class="transactions">
                    <div class="transactions-header">
                        <h3 class="transactions-title">Recent Transactions</h3>
                        <button class="refresh-btn" onclick="refreshTransactions()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                                <path d="M21 3v5h-5"/>
                                <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                                <path d="M3 21v-5h5"/>
                            </svg>
                        </button>
                    </div>
                    
                    <?php if (!empty($recent_transactions)): ?>
                        <?php foreach ($recent_transactions as $tx): ?>
                            <div class="transaction">
                                <div class="tx-left">
                                    <div class="tx-icon <?php echo $tx['status']; ?>">
                                        <?php if ($tx['status'] === 'receive'): ?>
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 5v14"/>
                                                <path d="m19 12-7 7-7-7"/>
                                            </svg>
                                        <?php else: ?>
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 19V5"/>
                                                <path d="m5 12 7-7 7 7"/>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tx-details">
                                        <div class="tx-type"><?php echo ucfirst($tx['status']); ?></div>
                                        <div class="tx-time"><?php echo getTimeAgo($tx['timestamp']); ?></div>
                                    </div>
                                </div>
                                <div class="tx-amount <?php echo $tx['status'] === 'receive' ? 'positive' : 'negative'; ?>">
                                    <?php echo $tx['status'] === 'receive' ? '+' : '-'; ?><?php echo number_format($tx['amount'], 2); ?> TRX
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 3v18h18"/>
                                <path d="M7 16l3-3 3 3 5-5"/>
                            </svg>
                            <div class="empty-text">No transactions yet</div>
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <!-- Create Wallet -->
                <div class="create-wallet">
                    <div class="create-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/>
                            <path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>
                        </svg>
                    </div>
                    <div class="create-title">Create Your TRON Wallet</div>
                    <div class="create-description">
                        Get started with your secure TRON wallet to send, receive, and manage your TRX tokens safely.
                    </div>
                    <form method="POST">
                        <button type="submit" name="create_wallet" class="create-btn">
                            Create Wallet
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <?php include '../includes/bottomnav.php'; ?>

    <script>
        let currentBalance = <?php echo $wallet ? $wallet['balance'] : 0; ?>;
        let balanceCheckInterval;
        
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($wallet): ?>
                startBalanceMonitoring();
                
                // Auto-hide alerts after 5 seconds
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 5000);
                });
            <?php endif; ?>
        });
        
        function startBalanceMonitoring() {
            balanceCheckInterval = setInterval(checkBalance, 30000); // Check every 30 seconds
        }
        
        function checkBalance() {
            fetch('../api/balance_checker.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.balance_updated) {
                        updateBalanceDisplay(data.new_balance, data.usd_value);
                        
                        if (data.new_balance > currentBalance) {
                            const balanceDisplay = document.getElementById('balance-display');
                            balanceDisplay.classList.add('balance-updated');
                            setTimeout(() => {
                                balanceDisplay.classList.remove('balance-updated');
                            }, 1000);
                        }
                        
                        currentBalance = data.new_balance;
                    }
                })
                .catch(error => {
                    console.error('Balance check error:', error);
                });
        }
        
        function updateBalanceDisplay(balance, usdValue) {
            document.getElementById('balance-display').textContent = balance.toFixed(2);
            document.getElementById('balance-usd').textContent = '$' + usdValue.toFixed(2);
        }
        
        function refreshTransactions() {
            location.reload();
        }
        
        // Cleanup intervals when page unloads
        window.addEventListener('beforeunload', function() {
            if (balanceCheckInterval) clearInterval(balanceCheckInterval);
        });
        
        // Cache warming on page visibility
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden && <?php echo $wallet ? 'true' : 'false'; ?>) {
                checkBalance();
            }
        });
    </script>
</body>
</html>