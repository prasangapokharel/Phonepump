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
$token_id = intval($_GET['token_id'] ?? 0);

if (!$token_id) {
    header("Location: trade.php");
    exit;
}

// Initialize Guzzle HTTP Client
$httpClient = new Client([
    'timeout' => 5,
    'connect_timeout' => 3,
    'headers' => [
        'User-Agent' => 'TRX-Trading/2.0',
        'Accept' => 'application/json'
    ]
]);

// Initialize Cache
try {
    $cache = new FilesystemAdapter(
        'trading_cache',
        300, // 5 minutes TTL
        '../cache'
    );
} catch (Exception $e) {
    // Fallback cache
    $cache = new class {
        private $data = [];
        public function getItem($key) {
            return new class($key, $this->data) {
                private $key, $data, $value, $hit = false;
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
                    $this->data[$this->key] = ['value' => $this->value, 'expires' => time() + $seconds];
                    return $this;
                }
            };
        }
        public function save($item) { return true; }
    };
}

// Get token data
$stmt = $pdo->prepare("
    SELECT t.*, bc.*, 
           COALESCE(tb.balance, 0) as user_balance,
           (SELECT COUNT(*) FROM token_transactions WHERE token_id = t.id AND transaction_type = 'buy' AND user_id != t.creator_id) as external_buys,
           (SELECT price_per_token FROM token_transactions WHERE token_id = t.id AND status = 'confirmed' ORDER BY created_at DESC LIMIT 1) as latest_price
    FROM tokens t 
    LEFT JOIN bonding_curves bc ON t.id = bc.token_id 
    LEFT JOIN token_balances tb ON t.id = tb.token_id AND tb.user_id = ?
    WHERE t.id = ?
");
$stmt->execute([$user_id, $token_id]);
$token = $stmt->fetch();

if (!$token) {
    header("Location: trade.php");
    exit;
}

// Use latest transaction price if available
if (!empty($token['latest_price'])) {
    $token['current_price'] = $token['latest_price'];
}

// Get user wallet
$stmt = $pdo->prepare("SELECT * FROM trxbalance WHERE user_id = ?");
$stmt->execute([$user_id]);
$wallet = $stmt->fetch();

// Get company settings
$stmt = $pdo->prepare("SELECT setting_name, setting_value FROM company_settings");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_name']] = $row['setting_value'];
}
$tradingFee = floatval($settings['trading_fee_trx'] ?? 10);

// Get recent trades
$stmt = $pdo->prepare("
    SELECT transaction_type, price_per_token, token_amount, created_at
    FROM token_transactions
    WHERE token_id = ? AND status = 'confirmed'
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->execute([$token_id]);
$recentTrades = $stmt->fetchAll();

// Calculate 24h price change with proper percentage
$stmt = $pdo->prepare("
    SELECT 
        (SELECT price_per_token FROM token_transactions 
         WHERE token_id = ? AND status = 'confirmed' 
         ORDER BY created_at DESC LIMIT 1) as current_price,
        (SELECT price_per_token FROM token_transactions 
         WHERE token_id = ? AND status = 'confirmed' 
         AND created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
         ORDER BY created_at DESC LIMIT 1) as price_24h_ago,
        (SELECT COUNT(*) FROM token_transactions 
         WHERE token_id = ? AND status = 'confirmed' 
         AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as trades_24h
");
$stmt->execute([$token_id, $token_id, $token_id]);
$priceData = $stmt->fetch();

$priceChange24h = 0;
$priceChangeAbs = 0;
if ($priceData && $priceData['price_24h_ago'] > 0 && $priceData['current_price'] > 0) {
    $priceChangeAbs = $priceData['current_price'] - $priceData['price_24h_ago'];
    $priceChange24h = ($priceChangeAbs / $priceData['price_24h_ago']) * 100;
} else if ($priceData && $priceData['current_price'] > 0) {
    // If no 24h ago price, use the current price (new token)
    $priceChangeAbs = 0;
    $priceChange24h = 0;
}

// Process trade if form submitted
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['trade_action'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $orderType = $_POST['order_type'] ?? 'market';
    $limitPrice = floatval($_POST['limit_price'] ?? 0);
    
    if ($amount <= 0) {
        $error = "Please enter a valid amount";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Calculate price based on order type
            $price = ($orderType === 'limit' && $limitPrice > 0) ? $limitPrice : $token['current_price'];
            
            if ($action === 'buy') {
                $totalCost = ($amount * $price) + $tradingFee;
                
                if ($wallet['balance'] < $totalCost) {
                    throw new Exception("Insufficient TRX balance. You need " . number_format($totalCost, 4) . " TRX.");
                }
                
                // Update user TRX balance
                $stmt = $pdo->prepare("UPDATE trxbalance SET balance = balance - ? WHERE user_id = ?");
                $stmt->execute([$totalCost, $user_id]);
                
                // Update token balances
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
                $txHash = 'tx_' . uniqid();
                $stmt = $pdo->prepare("
                    INSERT INTO token_transactions (
                        token_id, user_id, transaction_hash, transaction_type,
                        trx_amount, token_amount, price_per_token, fee_amount,
                        status, created_at
                    ) VALUES (?, ?, ?, 'buy', ?, ?, ?, ?, 'confirmed', NOW())
                ");
                $stmt->execute([$token_id, $user_id, $txHash, $amount * $price, $amount, $price, $tradingFee]);
                
                $success = "Successfully bought " . number_format($amount, 2) . " tokens for " . number_format($amount * $price, 4) . " TRX";
                
            } else { // sell
                if ($token['user_balance'] < $amount) {
                    throw new Exception("Insufficient token balance. You have " . number_format($token['user_balance'], 2) . " tokens.");
                }
                
                $trxReceived = $amount * $price;
                $totalAfterFee = $trxReceived - $tradingFee;
                
                if ($totalAfterFee <= 0) {
                    throw new Exception("Amount too small to cover trading fee.");
                }
                
                // Update user TRX balance
                $stmt = $pdo->prepare("UPDATE trxbalance SET balance = balance + ? WHERE user_id = ?");
                $stmt->execute([$totalAfterFee, $user_id]);
                
                // Update token balances
                $stmt = $pdo->prepare("
                    UPDATE token_balances SET 
                        balance = balance - ?,
                        last_transaction_at = NOW(),
                        total_sold = total_sold + ?
                    WHERE token_id = ? AND user_id = ?
                ");
                $stmt->execute([$amount, $amount, $token_id, $user_id]);
                
                // Record transaction
                $txHash = 'tx_' . uniqid();
                $stmt = $pdo->prepare("
                    INSERT INTO token_transactions (
                        token_id, user_id, transaction_hash, transaction_type,
                        trx_amount, token_amount, price_per_token, fee_amount,
                        status, created_at
                    ) VALUES (?, ?, ?, 'sell', ?, ?, ?, ?, 'confirmed', NOW())
                ");
                $stmt->execute([$token_id, $user_id, $txHash, $trxReceived, $amount, $price, $tradingFee]);
                
                $success = "Successfully sold " . number_format($amount, 2) . " tokens for " . number_format($totalAfterFee, 4) . " TRX";
            }
            
            // Update token current price after successful trade
            $stmt = $pdo->prepare("UPDATE tokens SET current_price = ?, market_cap = current_price * total_supply WHERE id = ?");
            $stmt->execute([$price, $token_id]);

            // Update bonding curve data
            if ($action === 'buy') {
                $stmt = $pdo->prepare("
                    UPDATE bonding_curves SET 
                        virtual_trx_reserves = virtual_trx_reserves + ?,
                        virtual_token_reserves = virtual_token_reserves - ?,
                        tokens_sold = tokens_sold + ?,
                        real_trx_reserves = real_trx_reserves + ?
                    WHERE token_id = ?
                ");
                $stmt->execute([$amount * $price, $amount, $amount, $amount * $price, $token_id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE bonding_curves SET 
                        virtual_trx_reserves = virtual_trx_reserves - ?,
                        virtual_token_reserves = virtual_token_reserves + ?,
                        tokens_sold = tokens_sold - ?,
                        real_trx_reserves = real_trx_reserves - ?
                    WHERE token_id = ?
                ");
                $stmt->execute([$trxReceived, $amount, $amount, $trxReceived, $token_id]);
            }
            
            $pdo->commit();
            
            // Refresh data after successful transaction
            $stmt = $pdo->prepare("SELECT * FROM trxbalance WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $wallet = $stmt->fetch();
            
            $stmt = $pdo->prepare("
                SELECT t.*, bc.*, 
                       COALESCE(tb.balance, 0) as user_balance
                FROM tokens t 
                LEFT JOIN bonding_curves bc ON t.id = bc.token_id 
                LEFT JOIN token_balances tb ON t.id = tb.token_id AND tb.user_id = ?
                WHERE t.id = ?
            ");
            $stmt->execute([$user_id, $token_id]);
            $token = $stmt->fetch();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($token['name']); ?> Trading</title>
    <style>
        /* Reset and Base Styles */
        * {margin:0; padding:0; box-sizing:border-box;}
        body {font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif; background:#0a0a0a; color:#fff; overflow:hidden;}
        
        /* Layout */
        .trading-app {display:grid; grid-template-columns:1fr 350px; height:100vh; overflow:hidden;}
        .chart-area {background:#111; position:relative; overflow:hidden;}
        .trading-panel {background:#111; border-left:1px solid #222; overflow-y:auto;}
        
        /* Header */
        .app-header {display:flex; align-items:center; justify-content:space-between; padding:12px 20px; background:#111; border-bottom:1px solid #222;}
        .token-info {display:flex; align-items:center; gap:10px;}
        .token-icon {width:32px; height:32px; border-radius:50%; background:#222; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:12px;}
        .token-icon img {width:100%; height:100%; border-radius:50%; object-fit:cover;}
        .token-name {font-size:18px; font-weight:600;}
        .token-symbol {font-size:12px; color:#999;}
        .price-display {text-align:right;}
        .current-price {font-size:20px; color:#00d4aa; font-weight:600;}
        .price-change {font-size:12px; padding:2px 6px; border-radius:4px; display:inline-block; margin-top:4px;}
        .price-change.positive {background:rgba(0,212,170,0.1); color:#00d4aa;}
        .price-change.negative {background:rgba(255,68,85,0.1); color:#ff4455;}
        
        /* Chart Controls */
        .chart-controls {padding:10px 20px; border-bottom:1px solid #222; display:flex; justify-content:space-between;}
        .time-intervals {display:flex; gap:4px;}
        .interval-btn {background:#222; color:#999; border:none; padding:6px 12px; border-radius:4px; font-size:12px; cursor:pointer; transition:all 0.2s;}
        .interval-btn:hover {background:#333;}
        .interval-btn.active {background:#00d4aa; color:#000;}
        
        /* Pure CSS Chart */
        .chart-container {height:calc(100% - 100px); padding:20px; position:relative; background:#0a0a0a;}
        
        .css-chart-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0a0a0a 0%, #111 100%);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .css-chart {
            position: relative;
            width: 100%;
            height: calc(100% - 40px);
            display: flex;
            align-items: flex-end;
            padding: 20px;
            gap: 2px;
        }
        
        .chart-bar {
            flex: 1;
            background: linear-gradient(to top, rgba(0,212,170,0.2), rgba(0,212,170,0.8));
            position: relative;
            min-height: 2px;
            border-radius: 2px 2px 0 0;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .chart-bar.up {
            background: linear-gradient(to top, rgba(0,212,170,0.2), rgba(0,212,170,0.8));
            box-shadow: 0 0 10px rgba(0,212,170,0.3);
        }
        
        .chart-bar.down {
            background: linear-gradient(to top, rgba(255,68,85,0.2), rgba(255,68,85,0.8));
            box-shadow: 0 0 10px rgba(255,68,85,0.3);
        }
        
        .chart-bar:hover {
            transform: scaleY(1.05);
            filter: brightness(1.2);
        }
        
        .chart-bar:hover::after {
            content: attr(data-price);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.9);
            color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            white-space: nowrap;
            z-index: 10;
            border: 1px solid #333;
        }
        
        .chart-grid {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 60px;
            pointer-events: none;
        }
        
        .grid-line {
            position: absolute;
            width: 100%;
            height: 1px;
            background: rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10px;
        }
        
        .grid-label {
            font-size: 10px;
            color: #666;
            background: #0a0a0a;
            padding: 0 4px;
        }
        
        .chart-time-axis {
            position: absolute;
            bottom: 0;
            left: 20px;
            right: 20px;
            height: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(255,255,255,0.05);
            padding-top: 10px;
        }
        
        .time-label {
            font-size: 10px;
            color: #666;
        }
        
        /* Trading Panel */
        .panel-section {padding:20px; border-bottom:1px solid #222;}
        .panel-title {font-size:16px; font-weight:600; margin-bottom:15px;}
        
        /* Order Type Tabs */
        .order-tabs {display:flex; background:#222; border-radius:4px; padding:2px; margin-bottom:15px;}
        .order-tab {flex:1; background:transparent; color:#999; border:none; padding:8px; border-radius:3px; font-size:12px; cursor:pointer; transition:all 0.2s;}
        .order-tab:hover {background:#333;}
        .order-tab.active {background:#333; color:#fff;}
        
        /* Trade Type Buttons */
        .trade-buttons {display:flex; gap:8px; margin-bottom:15px;}
        .trade-btn {flex:1; padding:10px; border:none; border-radius:4px; font-size:14px; font-weight:600; cursor:pointer; transition:all 0.2s;}
        .buy-btn {background:#222; color:#00d4aa; border:1px solid #00d4aa;}
        .buy-btn:hover {background:#00d4aa; color:#000;}
        .buy-btn.active {background:#00d4aa; color:#000;}
        .sell-btn {background:#222; color:#ff4455; border:1px solid #ff4455;}
        .sell-btn:hover {background:#ff4455; color:#fff;}
        .sell-btn.active {background:#ff4455; color:#fff;}
        
        /* Balance Info */
        .balance-info {background:#1a1a1a; padding:12px; border-radius:4px; margin-bottom:15px;}
        .balance-row {display:flex; justify-content:space-between; font-size:12px; margin-bottom:4px;}
        .balance-row:last-child {margin-bottom:0;}
        .balance-label {color:#999;}
        .balance-value {font-weight:600;}
        
        /* Form Elements */
        .form-group {margin-bottom:15px;}
        .form-label {display:block; font-size:11px; color:#999; text-transform:uppercase; margin-bottom:6px;}
        .form-input {width:100%; background:#1a1a1a; border:1px solid #333; color:#fff; padding:10px; border-radius:4px; font-size:14px;}
        .form-input:focus {outline:none; border-color:#00d4aa;}
        
        /* Percentage Buttons */
        .percentage-buttons {display:grid; grid-template-columns:repeat(4,1fr); gap:4px; margin-top:8px;}
        .pct-btn {background:#1a1a1a; color:#999; border:1px solid #333; padding:6px; border-radius:4px; font-size:11px; cursor:pointer; transition:all 0.2s;}
        .pct-btn:hover {background:#333; color:#fff; border-color:#555;}
        .pct-btn:active {background:#00d4aa; color:#000;}
        
        /* Order Summary */
        .order-summary {background:#1a1a1a; padding:12px; border-radius:4px; margin-bottom:15px;}
        .summary-row {display:flex; justify-content:space-between; font-size:12px; margin-bottom:6px;}
        .summary-row:last-child {margin-bottom:0; border-top:1px solid #333; padding-top:6px; font-weight:600;}
        .summary-label {color:#999;}
        
        /* Execute Button */
        .execute-btn {width:100%; padding:12px; border:none; border-radius:4px; font-size:14px; font-weight:600; text-transform:uppercase; cursor:pointer; transition:all 0.2s;}
        .execute-btn:disabled {opacity:0.5; cursor:not-allowed;}
        .execute-btn.buy {background:#00d4aa; color:#000;}
        .execute-btn.buy:hover:not(:disabled) {background:#00b894;}
        .execute-btn.sell {background:#ff4455; color:#fff;}
        .execute-btn.sell:hover:not(:disabled) {background:#e63946;}
        
        /* Recent Trades */
        .trades-list {max-height:200px; overflow-y:auto;}
        .trade-item {display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #222; font-size:12px;}
        .trade-item:last-child {border-bottom:none;}
        .trade-price {font-weight:600;}
        .trade-price.buy {color:#00d4aa;}
        .trade-price.sell {color:#ff4455;}
        .trade-amount, .trade-time {color:#999;}
        
        /* Alerts */
        .alert {padding:12px; border-radius:4px; margin-bottom:15px; font-size:14px;}
        .alert-success {background:rgba(0,212,170,0.1); color:#00d4aa; border:1px solid rgba(0,212,170,0.3);}
        .alert-error {background:rgba(255,68,85,0.1); color:#ff4455; border:1px solid rgba(255,68,85,0.3);}
        
        /* Loading Animation */
        .loading-indicator {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #00d4aa;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            animation: spin 2s linear infinite;
            display: inline-block;
            margin-right: 5px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Chart Animation */
        @keyframes chartGlow {
            0%, 100% { box-shadow: 0 0 5px rgba(0,212,170,0.3); }
            50% { box-shadow: 0 0 20px rgba(0,212,170,0.6); }
        }
        
        .chart-bar.animated {
            animation: chartGlow 2s ease-in-out infinite;
        }
        
        /* Responsive */
        @media (max-width:768px) {
            .trading-app {grid-template-columns:1fr; grid-template-rows:1fr auto;}
            .chart-area {height:60vh;}
            .trading-panel {border-left:none; border-top:1px solid #222; height:auto; max-height:40vh;}
        }
    </style>
</head>
<body>
    <div class="trading-app">
        <!-- Chart Area -->
        <div class="chart-area">
            <div class="app-header">
                <div class="token-info">
                    <?php if ($token['image_url']): ?>
                        <div class="token-icon"><img src="../<?php echo htmlspecialchars($token['image_url']); ?>" alt="<?php echo htmlspecialchars($token['symbol']); ?>"></div>
                    <?php else: ?>
                        <div class="token-icon"><?php echo substr($token['symbol'], 0, 2); ?></div>
                    <?php endif; ?>
                    <div>
                        <div class="token-name"><?php echo htmlspecialchars($token['name']); ?></div>
                        <div class="token-symbol"><?php echo htmlspecialchars($token['symbol']); ?></div>
                    </div>
                </div>
                <div class="price-display">
                    <div class="current-price" id="current-price"><?php echo number_format($token['current_price'], 8); ?> TRX</div>
                    <div class="price-change <?php echo $priceChange24h >= 0 ? 'positive' : 'negative'; ?>" id="price-change">
                        <?php echo ($priceChange24h >= 0 ? '+' : '') . number_format($priceChange24h, 2); ?>%
                        <?php if ($priceChangeAbs != 0): ?>
                            (<?php echo ($priceChangeAbs >= 0 ? '+' : '') . number_format($priceChangeAbs, 8); ?> TRX)
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 10px; color: #666; margin-top: 2px;" id="trades-24h">
                        <?php echo isset($priceData['trades_24h']) ? $priceData['trades_24h'] : 0; ?> trades (24h)
                    </div>
                </div>
            </div>
            
            <div class="chart-controls">
                <div class="time-intervals">
                    <button class="interval-btn active" data-interval="1m">1m</button>
                    <button class="interval-btn" data-interval="5m">5m</button>
                    <button class="interval-btn" data-interval="15m">15m</button>
                    <button class="interval-btn" data-interval="1h">1h</button>
                    <button class="interval-btn" data-interval="4h">4h</button>
                    <button class="interval-btn" data-interval="1d">1d</button>
                </div>
            </div>
            
            <div class="chart-container">
                <div class="css-chart-wrapper">
                    <div class="chart-grid" id="chart-grid">
                        <!-- Grid lines will be added by JavaScript -->
                    </div>
                    <div class="css-chart" id="css-chart">
                        <!-- Chart bars will be added by JavaScript -->
                    </div>
                    <div class="chart-time-axis" id="chart-time-axis">
                        <!-- Time labels will be added by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Trading Panel -->
        <div class="trading-panel">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="panel-section">
                <h2 class="panel-title">Place Order</h2>
                
                <div class="order-tabs">
                    <button type="button" class="order-tab active" data-type="market">Market</button>
                    <button type="button" class="order-tab" data-type="limit">Limit</button>
                </div>
                
                <div class="trade-buttons">
                    <button type="button" class="trade-btn buy-btn active" data-action="buy">Buy</button>
                    <button type="button" class="trade-btn sell-btn" data-action="sell">Sell</button>
                </div>
                
                <div class="balance-info">
                    <div class="balance-row">
                        <span class="balance-label">TRX Balance:</span>
                        <span class="balance-value" id="trx-balance"><?php echo number_format($wallet['balance'], 2); ?> TRX</span>
                    </div>
                    <div class="balance-row">
                        <span class="balance-label">Token Balance:</span>
                        <span class="balance-value" id="token-balance"><?php echo number_format($token['user_balance'], 2); ?> <?php echo htmlspecialchars($token['symbol']); ?></span>
                    </div>
                </div>
                
                <form method="POST" id="trade-form">
                    <input type="hidden" name="trade_action" id="trade-action" value="buy">
                    <input type="hidden" name="order_type" id="order-type" value="market">
                    
                    <div class="form-group">
                        <label class="form-label">Amount (Tokens)</label>
                        <input type="number" name="amount" id="amount-input" class="form-input" step="0.01" min="0.01" placeholder="0.00" required>
                        <div class="percentage-buttons">
                            <button type="button" class="pct-btn" data-percent="25">25%</button>
                            <button type="button" class="pct-btn" data-percent="50">50%</button>
                            <button type="button" class="pct-btn" data-percent="75">75%</button>
                            <button type="button" class="pct-btn" data-percent="100">Max</button>
                        </div>
                    </div>
                    
                    <div class="form-group" id="limit-price-group" style="display:none;">
                        <label class="form-label">Limit Price (TRX)</label>
                        <input type="number" name="limit_price" id="limit-price" class="form-input" step="0.00000001" min="0.00000001" placeholder="0.00000000">
                    </div>
                    
                    <div class="order-summary">
                        <div class="summary-row">
                            <span class="summary-label">Price per token:</span>
                            <span id="price-per-token"><?php echo number_format($token['current_price'], 8); ?> TRX</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Trading fee:</span>
                            <span><?php echo number_format($tradingFee, 2); ?> TRX</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label" id="total-label">Total:</span>
                            <span id="total-amount">0.00000000 TRX</span>
                        </div>
                    </div>
                    
                    <button type="submit" class="execute-btn buy" id="execute-btn">
                        Buy <?php echo strtoupper(htmlspecialchars($token['symbol'])); ?>
                    </button>
                </form>
            </div>
            
            <div class="panel-section">
                <h2 class="panel-title">Recent Trades</h2>
                <div class="trades-list" id="trades-list">
                    <?php if (empty($recentTrades)): ?>
                        <div style="text-align:center; color:#999; padding:20px;">No trades yet</div>
                    <?php else: ?>
                        <?php foreach ($recentTrades as $trade): ?>
                            <div class="trade-item">
                                <span class="trade-price <?php echo $trade['transaction_type']; ?>">
                                    <?php echo number_format($trade['price_per_token'], 8); ?>
                                </span>
                                <span class="trade-amount"><?php echo number_format($trade['token_amount'], 2); ?></span>
                                <span class="trade-time"><?php echo date('H:i:s', strtotime($trade['created_at'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let currentPrice = <?php echo $token['current_price']; ?>;
        const tradingFee = <?php echo $tradingFee; ?>;
        let userTrxBalance = <?php echo $wallet['balance']; ?>;
        let userTokenBalance = <?php echo $token['user_balance']; ?>;
        const tokenSymbol = '<?php echo $token['symbol']; ?>';
        let priceChange24h = <?php echo $priceChange24h; ?>;
        let trades24h = <?php echo isset($priceData['trades_24h']) ? $priceData['trades_24h'] : 0; ?>;
        let currentInterval = '1m';
        let chartData = [];
        let priceUpdateInterval;
        let chartUpdateInterval;
        
        // DOM elements
        const tradeForm = document.getElementById('trade-form');
        const tradeAction = document.getElementById('trade-action');
        const orderType = document.getElementById('order-type');
        const amountInput = document.getElementById('amount-input');
        const limitPriceInput = document.getElementById('limit-price');
        const limitPriceGroup = document.getElementById('limit-price-group');
        const pricePerToken = document.getElementById('price-per-token');
        const totalAmount = document.getElementById('total-amount');
        const totalLabel = document.getElementById('total-label');
        const executeBtn = document.getElementById('execute-btn');
        const currentPriceDisplay = document.getElementById('current-price');
        const priceChangeDisplay = document.getElementById('price-change');
        const trades24hDisplay = document.getElementById('trades-24h');
        const trxBalanceDisplay = document.getElementById('trx-balance');
        const tokenBalanceDisplay = document.getElementById('token-balance');
        const tradesListDisplay = document.getElementById('trades-list');
        const cssChart = document.getElementById('css-chart');
        const chartGrid = document.getElementById('chart-grid');
        const chartTimeAxis = document.getElementById('chart-time-axis');
        
        // Generate realistic chart data
        function generateChartData(interval, points = 50) {
            const data = [];
            const now = new Date();
            const basePrice = currentPrice;
            
            // Interval multipliers in minutes
            const intervalMinutes = {
                '1m': 1, '5m': 5, '15m': 15, 
                '1h': 60, '4h': 240, '1d': 1440
            };
            
            const minutes = intervalMinutes[interval] || 1;
            
            for (let i = points - 1; i >= 0; i--) {
                const time = new Date(now.getTime() - i * minutes * 60000);
                
                // Generate realistic price with trend and volatility
                const trend = Math.sin(i / 10) * 0.001; // Slight trend
                const volatility = (Math.random() - 0.5) * 0.02; // 2% volatility
                const price = basePrice * (1 + trend + volatility);
                
                data.push({
                    time: time,
                    price: Math.max(price, basePrice * 0.5), // Prevent negative prices
                    volume: Math.random() * 1000 + 100
                });
            }
            
            return data;
        }
        
        // Create CSS chart
        function createChart(data) {
            if (!data || data.length === 0) return;
            
            // Clear existing chart
            cssChart.innerHTML = '';
            chartGrid.innerHTML = '';
            chartTimeAxis.innerHTML = '';
            
            // Find min and max prices for scaling
            const prices = data.map(item => item.price);
            const minPrice = Math.min(...prices);
            const maxPrice = Math.max(...prices);
            const priceRange = maxPrice - minPrice;
            
            // Create grid lines
            for (let i = 0; i < 5; i++) {
                const gridLine = document.createElement('div');
                gridLine.className = 'grid-line';
                gridLine.style.top = `${i * 25}%`;
                
                const gridLabel = document.createElement('span');
                gridLabel.className = 'grid-label';
                const price = maxPrice - (i * (priceRange / 4));
                gridLabel.textContent = price.toFixed(8);
                
                gridLine.appendChild(gridLabel);
                chartGrid.appendChild(gridLine);
            }
            
            // Create chart bars
            data.forEach((item, index) => {
                const price = item.price;
                const prevPrice = index > 0 ? data[index - 1].price : price;
                const height = priceRange > 0 ? ((price - minPrice) / priceRange) * 100 : 50;
                
                const bar = document.createElement('div');
                bar.className = `chart-bar ${price >= prevPrice ? 'up' : 'down'}`;
                bar.style.height = `${Math.max(2, height)}%`;
                bar.setAttribute('data-price', price.toFixed(8) + ' TRX');
                
                // Add animation delay
                bar.style.animationDelay = `${index * 0.05}s`;
                
                cssChart.appendChild(bar);
            });
            
            // Create time labels
            const timeLabels = [];
            const labelCount = 5;
            for (let i = 0; i < labelCount; i++) {
                const index = Math.floor((data.length - 1) * i / (labelCount - 1));
                if (data[index]) {
                    timeLabels.push(data[index].time.toLocaleTimeString());
                }
            }
            
            timeLabels.forEach(label => {
                const timeLabel = document.createElement('div');
                timeLabel.className = 'time-label';
                timeLabel.textContent = label;
                chartTimeAxis.appendChild(timeLabel);
            });
        }
        
        // Fetch real chart data from API
        async function fetchChartData(interval) {
            try {
                const response = await fetch(`api/chart.php?token_id=<?php echo $token_id; ?>&interval=${interval}&_t=${Date.now()}`);
                const result = await response.json();
                
                if (result.success && result.data && result.data.length > 0) {
                    // Convert API data to our format
                    const convertedData = result.data.map(item => ({
                        time: new Date(item.time),
                        price: item.close || item.price,
                        volume: item.volume || 0
                    }));
                    
                    // Update price change if available
                    if (result.price_change_24h !== undefined) {
                        updatePriceChangeDisplay(result.price_change_24h);
                    }
                    
                    // Update trades count
                    if (result.trades_24h !== undefined) {
                        trades24h = result.trades_24h;
                        trades24hDisplay.textContent = trades24h + ' trades (24h)';
                    }
                    
                    return convertedData;
                }
                
                return null;
            } catch (error) {
                console.error('Chart API error:', error);
                return null;
            }
        }
        
        // Update chart with new interval
        async function updateChart(interval) {
            currentInterval = interval;
            
            // Try to fetch real data first
            let data = await fetchChartData(interval);
            
            // If no real data, generate sample data
            if (!data) {
                data = generateChartData(interval);
            }
            
            chartData = data;
            createChart(data);
        }
        
        // Update price change display
        function updatePriceChangeDisplay(change) {
            priceChange24h = change;
            const isPositive = change >= 0;
            priceChangeDisplay.className = `price-change ${isPositive ? 'positive' : 'negative'}`;
            priceChangeDisplay.textContent = `${isPositive ? '+' : ''}${change.toFixed(2)}%`;
        }
        
        // Calculate total based on amount and action
        function updateTotal() {
            const amount = parseFloat(amountInput.value) || 0;
            const action = tradeAction.value;
            let price = currentPrice;
            
            // Use limit price if applicable
            if (orderType.value === 'limit') {
                const limitPrice = parseFloat(limitPriceInput.value) || 0;
                if (limitPrice > 0) {
                    price = limitPrice;
                }
            }
            
            pricePerToken.textContent = price.toFixed(8) + ' TRX';
            
            if (action === 'buy') {
                const total = (amount * price) + tradingFee;
                totalAmount.textContent = total.toFixed(8) + ' TRX';
                totalLabel.textContent = 'Total:';
                
                // Disable button if insufficient balance
                executeBtn.disabled = amount <= 0 || total > userTrxBalance;
            } else {
                const received = Math.max(0, (amount * price) - tradingFee);
                totalAmount.textContent = received.toFixed(8) + ' TRX';
                totalLabel.textContent = 'You Receive:';
                
                // Disable button if insufficient token balance or amount too small
                executeBtn.disabled = amount <= 0 || amount > userTokenBalance || received <= 0;
            }
        }
        
        // Real-time price updates
        async function updatePriceData() {
            try {
                const response = await fetch(`api/trades.php?token_id=<?php echo $token_id; ?>&limit=1&_t=${Date.now()}`);
                const result = await response.json();
                
                if (result.success && result.trades && result.trades.length > 0) {
                    const latestTrade = result.trades[0];
                    const newPrice = parseFloat(latestTrade.price_per_token);
                    
                    // Update price display
                    currentPrice = newPrice;
                    currentPriceDisplay.textContent = newPrice.toFixed(8) + ' TRX';
                    
                    // Update price change if available
                    if (result.stats && result.stats.price_change_24h !== undefined) {
                        updatePriceChangeDisplay(result.stats.price_change_24h);
                    }
                    
                    // Update trades count
                    if (result.stats && result.stats.total_trades !== undefined) {
                        trades24h = result.stats.total_trades;
                        trades24hDisplay.textContent = trades24h + ' trades (24h)';
                    }
                    
                    // Add new data point to chart
                    if (chartData.length > 0) {
                        const lastDataPoint = chartData[chartData.length - 1];
                        const now = new Date();
                        
                        // Add new point if enough time has passed
                        if (now - lastDataPoint.time > 60000) { // 1 minute
                            chartData.push({
                                time: now,
                                price: newPrice,
                                volume: Math.random() * 1000 + 100
                            });
                            
                            // Keep only last 50 points
                            if (chartData.length > 50) {
                                chartData.shift();
                            }
                            
                            createChart(chartData);
                        }
                    }
                    
                    // Update total calculation
                    updateTotal();
                }
            } catch (error) {
                console.error('Price update failed:', error);
            }
        }
        
        // Update recent trades list
        async function updateTradesList() {
            try {
                const response = await fetch(`api/trades.php?token_id=<?php echo $token_id; ?>&limit=20&_t=${Date.now()}`);
                const result = await response.json();
                
                if (result.success && result.trades) {
                    let tradesHTML = '';
                    
                    if (result.trades.length === 0) {
                        tradesHTML = '<div style="text-align:center; color:#999; padding:20px;">No trades yet</div>';
                    } else {
                        result.trades.forEach(trade => {
                            const time = new Date(trade.created_at).toLocaleTimeString();
                            tradesHTML += `
                                <div class="trade-item">
                                    <span class="trade-price ${trade.transaction_type}">
                                        ${parseFloat(trade.price_per_token).toFixed(8)}
                                    </span>
                                    <span class="trade-amount">${parseFloat(trade.token_amount).toFixed(2)}</span>
                                    <span class="trade-time">${time}</span>
                                </div>
                            `;
                        });
                    }
                    
                    tradesListDisplay.innerHTML = tradesHTML;
                }
            } catch (error) {
                console.error('Trades list update failed:', error);
            }
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize chart
            updateChart(currentInterval);
            
            // Order type tabs
            document.querySelectorAll('.order-tab').forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    document.querySelectorAll('.order-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    const type = this.dataset.type;
                    orderType.value = type;
                    
                    if (type === 'limit') {
                        limitPriceGroup.style.display = 'block';
                        limitPriceInput.value = currentPrice.toFixed(8);
                    } else {
                        limitPriceGroup.style.display = 'none';
                    }
                    
                    updateTotal();
                });
            });
            
            // Trade action buttons
            document.querySelectorAll('.trade-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    document.querySelectorAll('.trade-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    const action = this.dataset.action;
                    tradeAction.value = action;
                    
                    if (action === 'buy') {
                        executeBtn.textContent = 'Buy ' + tokenSymbol.toUpperCase();
                        executeBtn.className = 'execute-btn buy';
                    } else {
                        executeBtn.textContent = 'Sell ' + tokenSymbol.toUpperCase();
                        executeBtn.className = 'execute-btn sell';
                    }
                    
                    updateTotal();
                });
            });
            
            // Percentage buttons
            document.querySelectorAll('.pct-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const percent = parseInt(this.dataset.percent);
                    const action = tradeAction.value;
                    
                    let maxAmount = 0;
                    if (action === 'buy') {
                        // For buy: calculate max tokens based on TRX balance
                        const price = currentPrice;
                        if (price > 0) {
                            maxAmount = Math.max(0, (userTrxBalance - tradingFee) / price);
                        }
                    } else {
                        // For sell: use token balance
                        maxAmount = userTokenBalance;
                    }
                    
                    const amount = (maxAmount * percent / 100);
                    amountInput.value = amount.toFixed(2);
                    updateTotal();
                    
                    // Visual feedback
                    this.style.background = '#00d4aa';
                    this.style.color = '#000';
                    setTimeout(() => {
                        this.style.background = '';
                        this.style.color = '';
                    }, 200);
                });
            });
            
            // Amount input
            amountInput.addEventListener('input', updateTotal);
            
            // Limit price input
            if (limitPriceInput) {
                limitPriceInput.addEventListener('input', updateTotal);
            }
            
            // Time interval buttons
            document.querySelectorAll('.interval-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    document.querySelectorAll('.interval-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    const interval = this.dataset.interval;
                    updateChart(interval);
                });
            });
            
            // Form submission
            tradeForm.addEventListener('submit', function(e) {
                const amount = parseFloat(amountInput.value) || 0;
                if (amount <= 0) {
                    e.preventDefault();
                    alert('Please enter a valid amount');
                    return false;
                }
                
                // Show loading state
                executeBtn.disabled = true;
                executeBtn.innerHTML = '<div class="loading-indicator"></div> Processing...';
            });
            
            // Initialize total calculation
            updateTotal();
            
            // Start real-time updates
            priceUpdateInterval = setInterval(updatePriceData, 10000); // Every 10 seconds
            setTimeout(updateTradesList, 5000); // Update trades list after 5 seconds
            setInterval(updateTradesList, 30000); // Then every 30 seconds
            
            // Auto-refresh chart data every 30 seconds
            chartUpdateInterval = setInterval(() => {
                updateChart(currentInterval);
            }, 30000);
        });
        
        // Cleanup intervals when page unloads
        window.addEventListener('beforeunload', function() {
            if (priceUpdateInterval) clearInterval(priceUpdateInterval);
            if (chartUpdateInterval) clearInterval(chartUpdateInterval);
        });

        // Handle visibility change to pause/resume updates
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Page is hidden, clear intervals
                if (priceUpdateInterval) clearInterval(priceUpdateInterval);
                if (chartUpdateInterval) clearInterval(chartUpdateInterval);
            } else {
                // Page is visible, restart intervals
                priceUpdateInterval = setInterval(updatePriceData, 10000);
                chartUpdateInterval = setInterval(() => {
                    updateChart(currentInterval);
                }, 30000);
                
                // Immediate update when page becomes visible
                updatePriceData();
                updateTradesList();
            }
        });
    </script>
</body>
</html>
