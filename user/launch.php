<?php
session_start();
require_once "../connect/db.php";
require_once "../components/wallet_generator.php";

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
        'trx_launch_cache',
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

// Get user wallet with caching
function getUserWalletWithCache($pdo, $user_id, $cache) {
    try {
        $cacheKey = "user_wallet_{$user_id}";
        $cachedWallet = $cache->getItem($cacheKey);
        
        if ($cachedWallet->isHit()) {
            return $cachedWallet->get();
        }
        
        $stmt = $pdo->prepare("SELECT * FROM trxbalance WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $wallet = $stmt->fetch();
        
        if ($wallet) {
            // Cache for 30 seconds
            $cachedWallet->set($wallet);
            $cachedWallet->expiresAfter(30);
            $cache->save($cachedWallet);
        }
        
        return $wallet;
        
    } catch (Exception $e) {
        error_log("User Wallet Cache Error: " . $e->getMessage());
        // Fallback to direct query
        $stmt = $pdo->prepare("SELECT * FROM trxbalance WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }
}

// Get company settings with caching
function getCompanySettingsWithCache($pdo, $cache) {
    try {
        $cacheKey = 'company_settings_launch';
        $cachedSettings = $cache->getItem($cacheKey);
        
        if ($cachedSettings->isHit()) {
            return $cachedSettings->get();
        }
        
        $stmt = $pdo->prepare("SELECT setting_name, setting_value FROM company_settings WHERE 1");
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
        $stmt = $pdo->prepare("SELECT setting_name, setting_value FROM company_settings WHERE 1");
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_name']] = $row['setting_value'];
        }
        return $settings;
    }
}

/**
 * Get TRX price with caching using Guzzle HTTP
 */
function getTRXPriceWithCache($httpClient, $cache) {
    try {
        $cacheKey = 'trx_price_usd_launch';
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
        $expiredCache = $cache->getItem('trx_price_fallback_launch');
        if ($expiredCache->isHit()) {
            return $expiredCache->get();
        }
        
    } catch (Exception $e) {
        error_log("TRX Price Cache Error: " . $e->getMessage());
    }
    
    return 0.067; // Ultimate fallback price
}

// Get user wallet and settings
$wallet = getUserWalletWithCache($pdo, $user_id, $cache);
$settings = getCompanySettingsWithCache($pdo, $cache);
$trx_price = getTRXPriceWithCache($httpClient, $cache);

$launch_fee = floatval($settings['launch_fee_trx'] ?? 10);
$company_wallet = $settings['company_wallet_address'] ?? 'TCompanyWallet123';

// Bonding curve constants (like SunPump)
$INITIAL_VIRTUAL_TRX = 30000; // Virtual TRX reserves
$INITIAL_VIRTUAL_TOKENS = 1073000000; // Virtual token reserves (1.073B)
$TOTAL_SUPPLY = 1000000000; // 1B total supply
$TRADEABLE_SUPPLY = $TOTAL_SUPPLY * 0.8; // 80% for trading, 20% for creator

$error = "";
$success = "";

// Function to calculate tokens from TRX using bonding curve
function calculateTokensFromTRX($trxAmount, $virtualTrx, $virtualTokens) {
    if ($trxAmount <= 0) return 0;

    // Using constant product formula: x * y = k
    // tokens_out = virtual_tokens - (virtual_trx * virtual_tokens) / (virtual_trx + trx_in)
    $k = $virtualTrx * $virtualTokens;
    $newVirtualTrx = $virtualTrx + $trxAmount;
    $newVirtualTokens = $k / $newVirtualTrx;
    $tokensOut = $virtualTokens - $newVirtualTokens;

    return $tokensOut;
}

// Function to calculate effective price per token
function calculateEffectivePrice($trxAmount, $tokensOut) {
    if ($tokensOut <= 0) return 0;
    return $trxAmount / $tokensOut;
}

// Handle token launch with initial buy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['launch_token'])) {
    $token_name = trim($_POST['token_name']);
    $token_symbol = trim($_POST['token_symbol']);
    $description = trim($_POST['description']);
    $initial_buy_amount = floatval($_POST['initial_buy_amount'] ?? 0);
    $website_url = trim($_POST['website_url']);
    $twitter_url = trim($_POST['twitter_url']);
    $telegram_url = trim($_POST['telegram_url']);

    // Calculate total cost including fees
    $total_cost_with_fee = $initial_buy_amount + $launch_fee;

    // Handle image upload
    $image_url = '';
    if (isset($_FILES['token_image']) && $_FILES['token_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/tokens/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['token_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['token_image']['tmp_name'], $upload_path)) {
            $image_url = 'uploads/tokens/' . $filename;
        }
    }

    // Validate inputs
    if (empty($token_name) || empty($token_symbol)) {
        $error = "Please fill in all required fields.";
    } elseif ($wallet['balance'] < $total_cost_with_fee) {
        $error = "Insufficient balance. You need " . number_format($total_cost_with_fee, 4) . " TRX (including " . $launch_fee . " TRX launch fee).";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Generate contract address (mock for demo)
            $contract_address = 'T' . bin2hex(random_bytes(20));
            
            // Calculate tokens for initial buy using bonding curve
            $tokens_from_initial_buy = 0;
            $initial_price = 0;
            if ($initial_buy_amount > 0) {
                $tokens_from_initial_buy = calculateTokensFromTRX($initial_buy_amount, $INITIAL_VIRTUAL_TRX, $INITIAL_VIRTUAL_TOKENS);
                $initial_price = calculateEffectivePrice($initial_buy_amount, $tokens_from_initial_buy);
            } else {
                // If no initial buy, set a minimal starting price
                $initial_price = $INITIAL_VIRTUAL_TRX / $INITIAL_VIRTUAL_TOKENS;
            }
            
            // Creator gets 20% of total supply
            $creator_tokens = $TOTAL_SUPPLY * 0.2;
            
            // Calculate market cap based on effective price
            $market_cap = $TOTAL_SUPPLY * $initial_price;
            
            // Insert token into database
            $stmt = $pdo->prepare("
                INSERT INTO tokens (
                    contract_address, creator_id, name, symbol, description, 
                    image_url, website_url, twitter_url, telegram_url,
                    total_supply, initial_liquidity, current_price, market_cap,
                    initial_buy_amount, creator_initial_tokens,
                    launch_time, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'active')
            ");
            
            if ($stmt->execute([
                $contract_address, $user_id, $token_name, $token_symbol, $description,
                $image_url, $website_url, $twitter_url, $telegram_url,
                $TOTAL_SUPPLY, $initial_buy_amount, $initial_price, $market_cap,
                $initial_buy_amount, $creator_tokens
            ])) {
                $token_id = $pdo->lastInsertId();
                
                // Create bonding curve with updated reserves
                $remaining_virtual_trx = $INITIAL_VIRTUAL_TRX + $initial_buy_amount;
                $remaining_virtual_tokens = $INITIAL_VIRTUAL_TOKENS - $tokens_from_initial_buy;
                $available_tokens = $TRADEABLE_SUPPLY - $tokens_from_initial_buy;
                
                $stmt = $pdo->prepare("
                    INSERT INTO bonding_curves (
                        token_id, initial_price, virtual_trx_reserves, 
                        virtual_token_reserves, real_token_reserves, tokens_available,
                        real_trx_reserves, tokens_sold, graduation_threshold
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 69000)
                ");
                $stmt->execute([
                    $token_id, $initial_price, $remaining_virtual_trx, 
                    $remaining_virtual_tokens, $available_tokens, $available_tokens,
                    $initial_buy_amount, $tokens_from_initial_buy
                ]);
                
                // Add creator balance (20% + initial buy tokens)
                $total_creator_tokens = $creator_tokens + $tokens_from_initial_buy;
                $stmt = $pdo->prepare("
                    INSERT INTO token_balances (
                        token_id, user_id, balance, is_creator, first_purchase_at
                    ) VALUES (?, ?, ?, 1, NOW())
                ");
                $stmt->execute([$token_id, $user_id, $total_creator_tokens]);
                
                // Deduct total cost from user balance
                $stmt = $pdo->prepare("UPDATE trxbalance SET balance = balance - ? WHERE user_id = ?");
                $stmt->execute([$total_cost_with_fee, $user_id]);
                
                // Record the initial buy transaction if amount > 0
                if ($initial_buy_amount > 0) {
                    $tx_hash = 'launch_' . uniqid();
                    $stmt = $pdo->prepare("
                        INSERT INTO token_transactions (
                            token_id, user_id, transaction_hash, transaction_type,
                            trx_amount, token_amount, price_per_token, fee_amount,
                            status, created_at
                        ) VALUES (?, ?, ?, 'initial_buy', ?, ?, ?, ?, 'confirmed', NOW())
                    ");
                    $stmt->execute([
                        $token_id, $user_id, $tx_hash, $initial_buy_amount, 
                        $tokens_from_initial_buy, $initial_price, $launch_fee
                    ]);
                }
                
                // Record launch fee transaction
                $fee_tx_hash = 'fee_' . uniqid();
                $stmt = $pdo->prepare("
                    INSERT INTO trxhistory (
                        user_id, amount, status, timestamp, tx_hash, 
                        from_address, to_address, transaction_type
                    ) VALUES (?, ?, 'confirmed', NOW(), ?, ?, ?, 'launch_fee')
                ");
                $stmt->execute([
                    $user_id, -$launch_fee, $fee_tx_hash, 
                    $wallet['address'], $company_wallet
                ]);
                
                $pdo->commit();
                
                // Clear relevant caches
                $cache->getItem("user_wallet_{$user_id}")->set(null);
                $cache->getItem("user_tokens_{$user_id}")->set(null);
                
                $success = "Token launched successfully! Contract: " . $contract_address;
                if ($initial_buy_amount > 0) {
                    $success .= "<br>Initial buy: " . number_format($tokens_from_initial_buy, 0) . " tokens for " . number_format($initial_buy_amount, 4) . " TRX";
                    $success .= "<br>Effective price: " . number_format($initial_price, 8) . " TRX per token";
                }
                $success .= "<br>Launch fee: " . $launch_fee . " TRX deducted";
                
                // Refresh wallet data
                $wallet = getUserWalletWithCache($pdo, $user_id, $cache);
            } else {
                throw new Exception("Failed to create token record");
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error launching token: " . $e->getMessage();
        }
    }
}

// Get user's launched tokens with caching
function getUserTokensWithCache($pdo, $user_id, $cache) {
    try {
        $cacheKey = "user_tokens_{$user_id}";
        $cachedTokens = $cache->getItem($cacheKey);
        
        if ($cachedTokens->isHit()) {
            return $cachedTokens->get();
        }
        
        $stmt = $pdo->prepare("
            SELECT t.*, bc.current_progress 
            FROM tokens t 
            LEFT JOIN bonding_curves bc ON t.id = bc.token_id 
            WHERE t.creator_id = ? 
            ORDER BY t.launch_time DESC
        ");
        $stmt->execute([$user_id]);
        $tokens = $stmt->fetchAll();
        
        // Cache for 2 minutes
        $cachedTokens->set($tokens);
        $cachedTokens->expiresAfter(120);
        $cache->save($cachedTokens);
        
        return $tokens;
        
    } catch (Exception $e) {
        error_log("User Tokens Cache Error: " . $e->getMessage());
        // Fallback to direct query
        $stmt = $pdo->prepare("
            SELECT t.*, 0 as current_progress 
            FROM tokens t 
            WHERE t.creator_id = ? 
            ORDER BY t.launch_time DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
}

$user_tokens = getUserTokensWithCache($pdo, $user_id, $cache);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Launch Token</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/launch.css">
</head>
<body>
    <div class="app">
        <div class="content">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (!$wallet): ?>
                <div class="alert alert-warning">
                    Please create a wallet first to launch tokens.
                    <a href="../dashboard.php" style="color: #ffc107; text-decoration: underline;">Create Wallet</a>
                </div>
            <?php else: ?>
                <div class="balance-display">
                    <div class="balance-amount"><?php echo number_format($wallet['balance'], 4); ?> TRX</div>
                    <div class="balance-label">Available Balance</div>
                    <div class="balance-usd">≈ $<?php echo number_format($wallet['balance'] * $trx_price, 2); ?> USD</div>
                </div>

                <form method="POST" enctype="multipart/form-data" id="launch-form">
                    <div class="form-group">
                        <label for="token_name">Token Name *</label>
                        <input type="text" id="token_name" name="token_name" required 
                               placeholder="e.g., My Awesome Token" maxlength="100">
                    </div>

                    <div class="form-group">
                        <label for="token_symbol">Symbol *</label>
                        <input type="text" id="token_symbol" name="token_symbol" required 
                               placeholder="e.g., MAT" maxlength="20" style="text-transform: uppercase;">
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4" 
                                  placeholder="Describe your token..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="token_image">Token Image</label>
                        <input type="file" id="token_image" name="token_image" 
                               accept="image/*">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="website_url">Website URL</label>
                            <input type="url" id="website_url" name="website_url" 
                                   placeholder="https://yourwebsite.com">
                        </div>
                        <div class="form-group">
                            <label for="twitter_url">Twitter URL</label>
                            <input type="url" id="twitter_url" name="twitter_url" 
                                   placeholder="https://twitter.com/yourtoken">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="telegram_url">Telegram URL</label>
                        <input type="url" id="telegram_url" name="telegram_url" 
                               placeholder="https://t.me/yourtoken">
                    </div>

                    <div class="bonding-curve-info">
                        <h4>Bonding Curve Details</h4>
                        <div>• Total Supply: <?php echo number_format($TOTAL_SUPPLY); ?> tokens</div>
                        <div>• Creator Allocation: 20% (<?php echo number_format($TOTAL_SUPPLY * 0.2); ?> tokens)</div>
                        <div>• Tradeable Supply: 80% (<?php echo number_format($TRADEABLE_SUPPLY); ?> tokens)</div>
                        <div>• Virtual TRX Reserves: <?php echo number_format($INITIAL_VIRTUAL_TRX); ?> TRX</div>
                        <div>• Virtual Token Reserves: <?php echo number_format($INITIAL_VIRTUAL_TOKENS); ?> tokens</div>
                        <div>• Price determined by bonding curve based on your initial buy amount</div>
                    </div>

                    <div class="initial-buy-section">
                        <div class="initial-buy-header">Initial Buy</div>
                        <div class="initial-buy-subtitle">be the first person to buy your token</div>
                        
                        <div class="balance-info">
                            <div class="balance-text">TRX Balance:</div>
                            <div class="balance-amount"><?php echo number_format($wallet['balance'], 4); ?> TRX</div>
                        </div>
                        
                        <div class="form-group">
                            <input type="number" id="initial_buy_amount" name="initial_buy_amount" 
                                   placeholder="Enter TRX amount" min="0" step="0.0001" value="0">
                            <div style="display: flex; justify-content: flex-end; margin-top: 4px;">
                                <span style="color: #999; font-size: 14px;">TRX</span>
                            </div>
                        </div>
                        
                        <div class="calculation-display" id="calculation-display" style="display: none;">
                            <div class="calc-row">
                                <span class="calc-label">You will receive:</span>
                                <span class="calc-value" id="tokens-to-receive">0</span>
                            </div>
                            <div class="calc-row">
                                <span class="calc-label">Percentage of supply:</span>
                                <span class="calc-value" id="percentage-of-supply">0%</span>
                            </div>
                            <div class="calc-row">
                                <span class="calc-label">Effective price per token:</span>
                                <span class="calc-value" id="effective-price">0 TRX</span>
                            </div>
                            <div class="calc-row">
                                <span class="calc-label">Launch fee:</span>
                                <span class="calc-value"><?php echo $launch_fee; ?> TRX</span>
                            </div>
                            <div class="calc-row">
                                <span class="calc-label">Total cost:</span>
                                <span class="calc-value" id="total-cost">0 TRX</span>
                            </div>
                        </div>
                        
                        <div id="insufficient-warning" class="insufficient-amount" style="display: none;">
                            Insufficient Amount
                        </div>
                    </div>

                    <button type="submit" name="launch_token" class="launch-btn" id="launch-btn">
                        🚀 Launch Token
                    </button>
                </form>
            <?php endif; ?>

            <?php if (!empty($user_tokens)): ?>
                <h2 class="section-title">Your Launched Tokens</h2>
                <?php foreach ($user_tokens as $token): ?>
                    <div class="token-card">
                        <div class="token-header">
                            <?php if ($token['image_url']): ?>
                                <img src="../<?php echo htmlspecialchars($token['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($token['name']); ?>" class="token-image">
                            <?php else: ?>
                                <div class="token-image">
                                    <?php echo substr($token['symbol'], 0, 2); ?>
                                </div>
                            <?php endif; ?>
                            <div class="token-info">
                                <h3><?php echo htmlspecialchars($token['name']); ?></h3>
                                <div class="token-symbol"><?php echo htmlspecialchars($token['symbol']); ?></div>
                            </div>
                        </div>
                        
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $token['current_progress']; ?>%"></div>
                        </div>
                        <div style="text-align: center; margin-top: 5px; font-size: 12px; color: #999;">
                            Bonding Curve Progress: <?php echo number_format($token['current_progress'], 2); ?>%
                        </div>

                        <div class="token-stats">
                            <div class="stat">
                                <div class="stat-value"><?php echo number_format($token['current_price'], 8); ?></div>
                                <div class="stat-label">Price (TRX)</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value"><?php echo number_format($token['market_cap'], 2); ?></div>
                                <div class="stat-label">Market Cap</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value"><?php echo $token['total_transactions']; ?></div>
                                <div class="stat-label">Transactions</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php include '../includes/bottomnav.php'; ?>
    </div>

    <script>
        const userBalance = <?php echo $wallet ? $wallet['balance'] : 0; ?>;
        const launchFee = <?php echo $launch_fee; ?>;
        const INITIAL_VIRTUAL_TRX = <?php echo $INITIAL_VIRTUAL_TRX; ?>;
        const INITIAL_VIRTUAL_TOKENS = <?php echo $INITIAL_VIRTUAL_TOKENS; ?>;
        const TOTAL_SUPPLY = <?php echo $TOTAL_SUPPLY; ?>;
        
        // Auto-uppercase symbol input
        document.getElementById('token_symbol').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });

        // Calculate tokens from TRX using bonding curve
        function calculateTokensFromTRX(trxAmount) {
            if (trxAmount <= 0) return 0;
            
            // Using constant product formula: x * y = k
            const k = INITIAL_VIRTUAL_TRX * INITIAL_VIRTUAL_TOKENS;
            const newVirtualTrx = INITIAL_VIRTUAL_TRX + trxAmount;
            const newVirtualTokens = k / newVirtualTrx;
            const tokensOut = INITIAL_VIRTUAL_TOKENS - newVirtualTokens;
            
            return tokensOut;
        }

        // Calculate initial buy details
        function updateCalculations() {
            const initialBuyAmount = parseFloat(document.getElementById('initial_buy_amount').value) || 0;
            
            const calculationDisplay = document.getElementById('calculation-display');
            const insufficientWarning = document.getElementById('insufficient-warning');
            const launchBtn = document.getElementById('launch-btn');
            
            if (initialBuyAmount > 0) {
                calculationDisplay.style.display = 'block';
                
                const tokensToReceive = calculateTokensFromTRX(initialBuyAmount);
                const percentageOfSupply = (tokensToReceive / TOTAL_SUPPLY) * 100;
                const effectivePrice = initialBuyAmount / tokensToReceive;
                const totalCost = initialBuyAmount + launchFee;
                
                document.getElementById('tokens-to-receive').textContent = 
                    new Intl.NumberFormat().format(Math.floor(tokensToReceive)) + ' tokens';
                document.getElementById('percentage-of-supply').textContent = percentageOfSupply.toFixed(2) + '%';
                document.getElementById('effective-price').textContent = effectivePrice.toFixed(8) + ' TRX';
                document.getElementById('total-cost').textContent = totalCost.toFixed(4) + ' TRX';
                
                // Check if user has sufficient balance
                if (totalCost > userBalance) {
                    insufficientWarning.style.display = 'block';
                    launchBtn.disabled = true;
                } else {
                    insufficientWarning.style.display = 'none';
                    launchBtn.disabled = false;
                }
            } else {
                calculationDisplay.style.display = 'none';
                insufficientWarning.style.display = 'none';
                
                // Check if user has enough for launch fee only
                if (launchFee > userBalance) {
                    launchBtn.disabled = true;
                } else {
                    launchBtn.disabled = false;
                }
            }
        }

        document.getElementById('initial_buy_amount').addEventListener('input', updateCalculations);
        
        // Initial calculation
        updateCalculations();

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
    </script>
</body>
</html>
