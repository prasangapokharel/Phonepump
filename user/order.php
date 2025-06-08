<?php
session_start();
require_once "../connect/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$token_id = intval($_GET['token_id'] ?? 0);

if (!$token_id) {
    header("Location: trade.php");
    exit;
}

// Get user wallet
$stmt = $pdo->prepare("SELECT * FROM trxbalance WHERE user_id = ?");
$stmt->execute([$user_id]);
$wallet = $stmt->fetch();

// Get token details with latest price
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

// Get company settings
$stmt = $pdo->prepare("SELECT setting_name, setting_value FROM company_settings");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_name']] = $row['setting_value'];
}

$trading_fee = floatval($settings['trading_fee_trx'] ?? 10);
$company_wallet = $settings['company_wallet_address'] ?? 'TCompanyWallet123';

// Calculate price change (24h)
$stmt = $pdo->prepare("
    SELECT 
        (SELECT price_per_token FROM token_transactions 
         WHERE token_id = ? AND status = 'confirmed' 
         ORDER BY created_at DESC LIMIT 1) as current_price,
        (SELECT price_per_token FROM token_transactions 
         WHERE token_id = ? AND status = 'confirmed' 
         AND created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
         ORDER BY created_at DESC LIMIT 1) as price_24h_ago
");
$stmt->execute([$token_id, $token_id]);
$price_data = $stmt->fetch();

$price_change_24h = 0;
if ($price_data && $price_data['price_24h_ago'] > 0) {
    $price_change_24h = (($price_data['current_price'] - $price_data['price_24h_ago']) / $price_data['price_24h_ago']) * 100;
}

// Get recent transactions for chart data
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:00') as time_bucket,
        AVG(price_per_token) as avg_price,
        MAX(price_per_token) as high_price,
        MIN(price_per_token) as low_price,
        SUM(trx_amount) as volume,
        COUNT(*) as trade_count
    FROM token_transactions 
    WHERE token_id = ? AND status = 'confirmed'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY time_bucket
    ORDER BY time_bucket ASC
");
$stmt->execute([$token_id]);
$chart_data = $stmt->fetchAll();

// Get order book data (recent transactions)
$stmt = $pdo->prepare("
    SELECT tt.*, u.username
    FROM token_transactions tt
    LEFT JOIN users2 u ON tt.user_id = u.id
    WHERE tt.token_id = ? AND tt.status = 'confirmed'
    ORDER BY tt.created_at DESC
    LIMIT 50
");
$stmt->execute([$token_id]);
$recent_trades = $stmt->fetchAll();

// Get 24h stats
$stmt = $pdo->prepare("
    SELECT 
        SUM(trx_amount) as volume_24h,
        MAX(price_per_token) as high_24h,
        MIN(price_per_token) as low_24h
    FROM token_transactions
    WHERE token_id = ? AND status = 'confirmed'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$stmt->execute([$token_id]);
$stats_24h = $stmt->fetch();

$error = "";
$success = "";

// Handle trading - FIXED DIVISION BY ZERO ERROR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trade_action'])) {
    $action = $_POST['trade_action'];
    $amount = floatval($_POST['amount']);
    $order_type = $_POST['order_type'] ?? 'market';
    $limit_price = floatval($_POST['limit_price'] ?? 0);
    
    // Validate amount first
    if ($amount <= 0) {
        $error = "Invalid amount. Please enter a positive number.";
    } else {
        try {
            $pdo->beginTransaction();
            
            if ($action === 'buy') {
                $price_per_token = ($order_type === 'limit' && $limit_price > 0) ? $limit_price : $token['current_price'];
                $total_cost = $amount * $price_per_token;
                $total_with_fee = $total_cost + $trading_fee;
                
                if ($wallet['balance'] < $total_with_fee) {
                    $error = "Insufficient TRX balance. Need " . number_format($total_with_fee, 4) . " TRX.";
                } else {
                    // Process buy
                    $stmt = $pdo->prepare("UPDATE trxbalance SET balance = balance - ? WHERE user_id = ?");
                    $stmt->execute([$total_with_fee, $user_id]);
                    
                    $stmt = $pdo->prepare("
                        UPDATE bonding_curves SET 
                            real_trx_reserves = real_trx_reserves + ?,
                            tokens_available = tokens_available - ?,
                            tokens_sold = tokens_sold + ?,
                            current_progress = LEAST(100.0, (real_trx_reserves + ?) / graduation_threshold * 100)
                        WHERE token_id = ?
                    ");
                    $stmt->execute([$total_cost, $amount, $amount, $total_cost, $token_id]);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO token_balances (token_id, user_id, balance, first_purchase_at, last_transaction_at, total_bought)
                        VALUES (?, ?, ?, NOW(), NOW(), ?)
                        ON DUPLICATE KEY UPDATE
                            balance = balance + VALUES(balance),
                            last_transaction_at = NOW(),
                            total_bought = total_bought + VALUES(total_bought)
                    ");
                    $stmt->execute([$token_id, $user_id, $amount, $amount]);
                    
                    $tx_hash = 'tx_' . uniqid();
                    $stmt = $pdo->prepare("
                        INSERT INTO token_transactions (
                            token_id, user_id, transaction_hash, transaction_type,
                            trx_amount, token_amount, price_per_token, fee_amount,
                            status, created_at
                        ) VALUES (?, ?, ?, 'buy', ?, ?, ?, ?, 'confirmed', NOW())
                    ");
                    $stmt->execute([$token_id, $user_id, $tx_hash, $total_cost, $amount, $price_per_token, $trading_fee]);
                    
                    // Record fee transaction
                    $fee_tx_hash = 'fee_' . uniqid();
                    $stmt = $pdo->prepare("
                        INSERT INTO trxhistory (
                            user_id, amount, status, timestamp, tx_hash, 
                            from_address, to_address, transaction_type
                        ) VALUES (?, ?, 'confirmed', NOW(), ?, ?, ?, 'trading_fee')
                    ");
                    $stmt->execute([
                        $user_id, -$trading_fee, $fee_tx_hash, 
                        $wallet['address'], $company_wallet
                    ]);
                    
                    $stmt = $pdo->prepare("
                        UPDATE tokens SET 
                            current_price = ?,
                            total_transactions = total_transactions + 1,
                            volume_total = volume_total + ?,
                            volume_24h = volume_24h + ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$price_per_token, $total_cost, $total_cost, $token_id]);
                    
                    $success = "Buy order executed successfully! Bought " . number_format($amount, 2) . " tokens for " . number_format($total_cost, 4) . " TRX.";
                }
            } else { // SELL - FIXED DIVISION BY ZERO ERROR
                if ($token['user_balance'] < $amount) {
                    $error = "Insufficient token balance. You have " . number_format($token['user_balance'], 2) . " tokens.";
                } else {
                    $is_creator = ($user_id == $token['creator_id']);
                    $has_external_buys = ($token['external_buys'] > 0);
                    
                    // Initialize variables to prevent division by zero
                    $price_per_token = 0;
                    $trx_received = 0;
                    
                    // Calculate sell price and TRX received - FIXED DIVISION BY ZERO
                    if ($is_creator && !$has_external_buys && !empty($token['initial_buy_amount']) && $token['initial_buy_amount'] > 0) {
                        // Special case: Creator selling with no external buys - return proportional initial investment
                        $creator_total_tokens = floatval($token['creator_initial_tokens']) + floatval($token['tokens_sold']);
                        
                        if ($creator_total_tokens > 0 && $amount > 0) {
                            $sell_percentage = $amount / $creator_total_tokens;
                            $trx_received = floatval($token['initial_buy_amount']) * $sell_percentage;
                            $price_per_token = $trx_received / $amount; // Safe division - amount > 0 checked above
                        } else {
                            // Fallback to market price if calculation fails
                            $price_per_token = ($order_type === 'limit' && $limit_price > 0) ? $limit_price : $token['current_price'];
                            $trx_received = $amount * $price_per_token;
                        }
                    } else {
                        // Normal market sell
                        $price_per_token = ($order_type === 'limit' && $limit_price > 0) ? $limit_price : $token['current_price'];
                        $trx_received = $amount * $price_per_token;
                    }
                    
                    // Additional safety check
                    if ($price_per_token <= 0) {
                        $price_per_token = $token['current_price'];
                        $trx_received = $amount * $price_per_token;
                    }
                    
                    // Check if transaction covers trading fee
                    if ($trx_received <= $trading_fee) {
                        if ($price_per_token > 0) {
                            $min_tokens_needed = ceil(($trading_fee + 0.0001) / $price_per_token);
                            $error = "Amount too small to cover trading fee. Minimum required: " . number_format($min_tokens_needed, 2) . " tokens.";
                        } else {
                            $error = "Cannot calculate minimum tokens required. Price is zero.";
                        }
                    } else {
                        $total_after_fee = $trx_received - $trading_fee;
                        
                        // Update user TRX balance
                        $stmt = $pdo->prepare("UPDATE trxbalance SET balance = balance + ? WHERE user_id = ?");
                        $stmt->execute([$total_after_fee, $user_id]);
                        
                        // Update bonding curve reserves
                        $stmt = $pdo->prepare("
                            UPDATE bonding_curves SET 
                                real_trx_reserves = GREATEST(0, real_trx_reserves - ?),
                                tokens_available = tokens_available + ?,
                                tokens_sold = GREATEST(0, tokens_sold - ?),
                                current_progress = LEAST(100.0, GREATEST(0, real_trx_reserves - ?) / graduation_threshold * 100)
                            WHERE token_id = ?
                        ");
                        $stmt->execute([$trx_received, $amount, $amount, $trx_received, $token_id]);
                        
                        // Update user token balance
                        $stmt = $pdo->prepare("
                            UPDATE token_balances SET 
                                balance = GREATEST(0, balance - ?),
                                last_transaction_at = NOW(),
                                total_sold = total_sold + ?
                            WHERE token_id = ? AND user_id = ?
                        ");
                        $stmt->execute([$amount, $amount, $token_id, $user_id]);
                        
                        // Record sell transaction
                        $tx_hash = 'tx_' . uniqid();
                        $stmt = $pdo->prepare("
                            INSERT INTO token_transactions (
                                token_id, user_id, transaction_hash, transaction_type,
                                trx_amount, token_amount, price_per_token, fee_amount,
                                status, created_at
                            ) VALUES (?, ?, ?, 'sell', ?, ?, ?, ?, 'confirmed', NOW())
                        ");
                        $stmt->execute([$token_id, $user_id, $tx_hash, $trx_received, $amount, $price_per_token, $trading_fee]);
                        
                        // Record fee transaction
                        $fee_tx_hash = 'fee_' . uniqid();
                        $stmt = $pdo->prepare("
                            INSERT INTO trxhistory (
                                user_id, amount, status, timestamp, tx_hash, 
                                from_address, to_address, transaction_type
                            ) VALUES (?, ?, 'confirmed', NOW(), ?, ?, ?, 'trading_fee')
                        ");
                        $stmt->execute([
                            $user_id, -$trading_fee, $fee_tx_hash, 
                            $wallet['address'], $company_wallet
                        ]);
                        
                        // Update token stats
                        $stmt = $pdo->prepare("
                            UPDATE tokens SET 
                                current_price = ?,
                                total_transactions = total_transactions + 1,
                                volume_total = volume_total + ?,
                                volume_24h = volume_24h + ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$price_per_token, $trx_received, $trx_received, $token_id]);
                        
                        $success = "Sell order executed successfully! Sold " . number_format($amount, 2) . " tokens for " . number_format($total_after_fee, 4) . " TRX (after " . $trading_fee . " TRX fee).";
                    }
                }
            }
            
            if (empty($error)) {
                $pdo->commit();
                
                // Refresh token data after successful transaction
                $stmt = $pdo->prepare("
                    SELECT t.*, bc.*, 
                           COALESCE(tb.balance, 0) as user_balance,
                           (SELECT COUNT(*) FROM token_transactions WHERE token_id = t.id AND transaction_type = 'buy' AND user_id != t.creator_id) as external_buys
                    FROM tokens t 
                    LEFT JOIN bonding_curves bc ON t.id = bc.token_id 
                    LEFT JOIN token_balances tb ON t.id = tb.token_id AND tb.user_id = ?
                    WHERE t.id = ?
                ");
                $stmt->execute([$user_id, $token_id]);
                $token = $stmt->fetch();
                
                // Refresh wallet data
                $stmt = $pdo->prepare("SELECT * FROM trxbalance WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $wallet = $stmt->fetch();
            } else {
                $pdo->rollBack();
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Transaction failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($token['name']); ?> - Advanced Trading</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <style>
        * {margin:0; padding:0; box-sizing:border-box;}
        body {font-family:system-ui, -apple-system, sans-serif; background:#0a0a0a; color:#fff; overflow-x:hidden;}
        
        .trading-container {display:grid; grid-template-columns:1fr 350px; grid-template-rows:60px 1fr; height:100vh; gap:1px; background:#111;}
        .header {grid-column:1/-1; background:#1a1a1a; display:flex; align-items:center; justify-content:space-between; padding:0 20px; border-bottom:1px solid #333;}
        .token-info {display:flex; align-items:center; gap:15px;}
        .token-image {width:40px; height:40px; border-radius:50%; background:#333; display:flex; align-items:center; justify-content:center; font-weight:bold;}
        .token-details h1 {font-size:20px; margin-bottom:2px;}
        .token-symbol {color:#999; font-size:14px;}
        .price-info {display:flex; align-items:center; gap:20px;}
        .current-price {font-size:24px; font-weight:700; color:#00d4aa;}
        .price-change {padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600;}
        .price-change.positive {background:#00d4aa20; color:#00d4aa;}
        .price-change.negative {background:#ff445520; color:#ff4455;}
        .nav-actions {display:flex; gap:10px;}
        .back-btn {background:#333; color:#fff; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; text-decoration:none; font-size:14px;}
        .back-btn:hover {background:#444;}
        
        .main-content {background:#0f0f0f; display:flex; flex-direction:column; overflow:hidden;}
        .chart-section {flex:1; padding:20px; display:flex; flex-direction:column;}
        .chart-controls {display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;}
        .time-intervals {display:flex; gap:5px;}
        .interval-btn {background:#222; color:#999; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; transition:all 0.2s;}
        .interval-btn.active {background:#00d4aa; color:#000;}
        .interval-btn:hover {background:#333; color:#fff;}
        .chart-container {flex:1; position:relative; background:#111; border-radius:8px; padding:20px;}
        
        .sidebar {background:#111; display:flex; flex-direction:column; overflow-y:auto;}
        .trading-panel {padding:20px; border-bottom:1px solid #222;}
        .panel-title {font-size:16px; font-weight:600; margin-bottom:15px; color:#fff;}
        
        .order-type-tabs {display:flex; margin-bottom:20px; background:#222; border-radius:6px; padding:2px;}
        .order-tab {flex:1; background:transparent; color:#999; border:none; padding:8px; border-radius:4px; cursor:pointer; font-size:12px; font-weight:600; transition:all 0.2s;}
        .order-tab.active {background:#333; color:#fff;}
        
        .trade-type-tabs {display:flex; margin-bottom:20px; gap:5px;}
        .trade-tab {flex:1; padding:10px; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:14px; transition:all 0.2s;}
        .buy-tab {background:#00d4aa20; color:#00d4aa; border:1px solid #00d4aa40;}
        .buy-tab.active {background:#00d4aa; color:#000;}
        .sell-tab {background:#ff445520; color:#ff4455; border:1px solid #ff445540;}
        .sell-tab.active {background:#ff4455; color:#fff;}
        
        .form-group {margin-bottom:15px;}
        .form-label {display:block; margin-bottom:5px; font-size:12px; color:#999; font-weight:600; text-transform:uppercase;}
        .form-input {width:100%; background:#222; border:1px solid #333; color:#fff; padding:12px; border-radius:6px; font-size:14px;}
        .form-input:focus {outline:none; border-color:#00d4aa;}
        
        .balance-info {background:#1a1a1a; padding:12px; border-radius:6px; margin-bottom:15px;}
        .balance-row {display:flex; justify-content:space-between; margin-bottom:5px; font-size:12px;}
        .balance-label {color:#999;}
        .balance-value {color:#fff; font-weight:600;}
        
        .order-summary {background:#1a1a1a; padding:15px; border-radius:6px; margin-bottom:15px;}
        .summary-row {display:flex; justify-content:space-between; margin-bottom:8px; font-size:12px;}
        .summary-row:last-child {border-top:1px solid #333; padding-top:8px; font-weight:600;}
        
        .execute-btn {width:100%; padding:15px; border:none; border-radius:6px; font-weight:700; font-size:14px; cursor:pointer; text-transform:uppercase; transition:all 0.2s;}
        .execute-btn:disabled {opacity:0.5; cursor:not-allowed;}
        .buy-btn {background:#00d4aa; color:#000;}
        .buy-btn:hover:not(:disabled) {background:#00c499;}
        .sell-btn {background:#ff4455; color:#fff;}
        .sell-btn:hover:not(:disabled) {background:#ff3344;}
        
        .order-book {padding:20px; border-bottom:1px solid #222;}
        .trades-list {max-height:300px; overflow-y:auto;}
        .trade-item {display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #1a1a1a; font-size:12px;}
        .trade-price {font-weight:600;}
        .trade-price.buy {color:#00d4aa;}
        .trade-price.sell {color:#ff4455;}
        .trade-amount {color:#999;}
        .trade-time {color:#666;}
        
        .stats-panel {padding:20px;}
        .stats-grid {display:grid; grid-template-columns:1fr 1fr; gap:15px;}
        .stat-item {background:#1a1a1a; padding:12px; border-radius:6px; text-align:center;}
        .stat-value {font-size:16px; font-weight:700; margin-bottom:4px;}
        .stat-label {font-size:10px; color:#999; text-transform:uppercase;}
        
        .progress-bar {width:100%; height:6px; background:#222; border-radius:3px; overflow:hidden; margin:10px 0;}
        .progress-fill {height:100%; background:linear-gradient(90deg, #00d4aa, #00c499); transition:width 0.3s ease;}
        
        .alert {padding:12px; border-radius:6px; margin-bottom:15px; font-size:14px;}
        .alert-success {background:#00d4aa20; color:#00d4aa; border:1px solid #00d4aa40;}
        .alert-error {background:#ff445520; color:#ff4455; border:1px solid #ff445540;}
        
        .quick-amounts {display:grid; grid-template-columns:repeat(4, 1fr); gap:5px; margin-top:10px;}
        .quick-amount {background:#222; color:#999; border:none; padding:6px; border-radius:4px; cursor:pointer; font-size:10px; font-weight:600;}
        .quick-amount:hover {background:#333; color:#fff;}
        
        ::-webkit-scrollbar {width:6px;}
        ::-webkit-scrollbar-track {background:#1a1a1a;}
        ::-webkit-scrollbar-thumb {background:#333; border-radius:3px;}
        ::-webkit-scrollbar-thumb:hover {background:#444;}
        
        @media (max-width:768px) {
            .trading-container {grid-template-columns:1fr; grid-template-rows:60px 1fr 400px;}
            .sidebar {border-left:none; border-top:1px solid #222;}
        }
    </style>
</head>
<body>
    <div class="trading-container">
        <div class="header">
            <div class="token-info">
                <?php if ($token['image_url']): ?>
                    <img src="../<?php echo htmlspecialchars($token['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($token['name']); ?>" class="token-image">
                <?php else: ?>
                    <div class="token-image">
                        <?php echo substr($token['symbol'], 0, 2); ?>
                    </div>
                <?php endif; ?>
                <div class="token-details">
                    <h1><?php echo htmlspecialchars($token['name']); ?></h1>
                    <div class="token-symbol"><?php echo htmlspecialchars($token['symbol']); ?></div>
                </div>
            </div>
            
            <div class="price-info">
                <div class="current-price"><?php echo number_format($token['current_price'], 8); ?> TRX</div>
                <div class="price-change <?php echo $price_change_24h >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo ($price_change_24h >= 0 ? '+' : '') . number_format($price_change_24h, 2); ?>%
                </div>
            </div>
            
            <div class="nav-actions">
                <a href="trade.php" class="back-btn">← Back to Trade</a>
            </div>
        </div>
        
        <div class="main-content">
            <div class="chart-section">
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
                    <canvas id="priceChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="sidebar">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="trading-panel">
                <div class="panel-title">Place Order</div>
                
                <form method="POST" id="trading-form">
                    <input type="hidden" name="trade_action" id="trade_action" value="buy">
                    
                    <div class="order-type-tabs">
                        <button type="button" class="order-tab active" data-type="market">Market</button>
                        <button type="button" class="order-tab" data-type="limit">Limit</button>
                    </div>
                    
                    <div class="trade-type-tabs">
                        <button type="button" class="trade-tab buy-tab active" data-action="buy">Buy</button>
                        <button type="button" class="trade-tab sell-tab" data-action="sell">Sell</button>
                    </div>
                    
                    <div class="balance-info">
                        <div class="balance-row">
                            <span class="balance-label">TRX Balance:</span>
                            <span class="balance-value" id="trx-balance"><?php echo number_format($wallet['balance'], 4); ?> TRX</span>
                        </div>
                        <div class="balance-row">
                            <span class="balance-label">Token Balance:</span>
                            <span class="balance-value" id="token-balance"><?php echo number_format($token['user_balance'], 2); ?> <?php echo $token['symbol']; ?></span>
                        </div>
                    </div>
                    
                    <div class="form-group" id="limit-price-group" style="display: none;">
                        <label class="form-label">Limit Price (TRX)</label>
                        <input type="number" name="limit_price" id="limit-price" class="form-input" step="0.00000001" placeholder="0.00000000">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Amount (Tokens)</label>
                        <input type="number" name="amount" id="token-amount" class="form-input" step="0.01" min="0.01" required placeholder="0.00">
                        <div class="quick-amounts">
                            <button type="button" class="quick-amount" data-percent="25">25%</button>
                            <button type="button" class="quick-amount" data-percent="50">50%</button>
                            <button type="button" class="quick-amount" data-percent="75">75%</button>
                            <button type="button" class="quick-amount" data-percent="100">Max</button>
                        </div>
                    </div>
                    
                    <input type="hidden" name="order_type" id="order_type" value="market">
                    
                    <div class="order-summary">
                        <div class="summary-row">
                            <span>Price per token:</span>
                            <span id="summary-price"><?php echo number_format($token['current_price'], 8); ?> TRX</span>
                        </div>
                        <div class="summary-row">
                            <span>Trading fee:</span>
                            <span><?php echo $trading_fee; ?> TRX</span>
                        </div>
                        <div class="summary-row">
                            <span id="total-label">Total:</span>
                            <span id="summary-total">0.0000 TRX</span>
                        </div>
                    </div>
                    
                    <button type="submit" class="execute-btn buy-btn" id="execute-btn">
                        Buy <?php echo $token['symbol']; ?>
                    </button>
                </form>
            </div>
            
            <div class="order-book">
                <div class="panel-title">Recent Trades</div>
                <div class="trades-list">
                    <?php if (empty($recent_trades)): ?>
                        <div style="text-align:center; color:#999; padding:20px;">No trades yet</div>
                    <?php else: ?>
                        <?php foreach ($recent_trades as $trade): ?>
                            <div class="trade-item">
                                <span class="trade-price <?php echo $trade['transaction_type']; ?>">
                                    <?php echo number_format($trade['price_per_token'], 8); ?>
                                </span>
                                <span class="trade-amount"><?php echo number_format($trade['token_amount'], 2); ?></span>
                                <span class="trade-time"><?php echo date('H:i', strtotime($trade['created_at'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="stats-panel">
                <div class="panel-title">Token Stats</div>
                
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $token['current_progress']; ?>%"></div>
                </div>
                <div style="text-align: center; font-size: 12px; color: #999; margin-bottom: 15px;">
                    Bonding Curve: <?php echo number_format($token['current_progress'], 1); ?>%
                </div>
                
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($stats_24h['volume_24h'] ?? 0, 0); ?> TRX</div>
                        <div class="stat-label">24h Volume</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $token['total_transactions']; ?></div>
                        <div class="stat-label">Transactions</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($stats_24h['high_24h'] ?? $token['current_price'], 8); ?></div>
                        <div class="stat-label">24h High</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($stats_24h['low_24h'] ?? $token['current_price'], 8); ?></div>
                        <div class="stat-label">24h Low</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Chart configuration
        const ctx = document.getElementById('priceChart').getContext('2d');
        const chartData = <?php echo json_encode($chart_data); ?>;
        
        // Process chart data
        const processedData = chartData.map(item => ({
            x: new Date(item.time_bucket),
            y: parseFloat(item.avg_price)
        }));
        
        // Add current price as latest point if no data
        if (processedData.length === 0) {
            processedData.push({
                x: new Date(),
                y: <?php echo $token['current_price']; ?>
            });
        }
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                datasets: [{
                    label: 'Price',
                    data: processedData,
                    borderColor: '#00d4aa',
                    backgroundColor: 'rgba(0, 212, 170, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'minute',
                            displayFormats: {
                                minute: 'HH:mm'
                            }
                        },
                        grid: {
                            color: '#333'
                        },
                        ticks: {
                            color: '#999'
                        }
                    },
                    y: {
                        grid: {
                            color: '#333'
                        },
                        ticks: {
                            color: '#999',
                            callback: function(value) {
                                return value.toFixed(8) + ' TRX';
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
        
        // Trading form functionality
        const tradeActionInput = document.getElementById('trade_action');
        const executeBtn = document.getElementById('execute-btn');
        const orderTypeInput = document.getElementById('order_type');
        const limitPriceGroup = document.getElementById('limit-price-group');
        const tokenAmountInput = document.getElementById('token-amount');
        const limitPriceInput = document.getElementById('limit-price');
        const totalLabel = document.getElementById('total-label');
        
        // Current token data
        const currentPrice = <?php echo $token['current_price']; ?>;
        const tradingFee = <?php echo $trading_fee; ?>;
        let userTrxBalance = <?php echo $wallet['balance']; ?>;
        let userTokenBalance = <?php echo $token['user_balance']; ?>;
        const tokenSymbol = '<?php echo $token['symbol']; ?>';
        
        // Trade type tabs - FIXED DIVISION BY ZERO
        document.querySelectorAll('.trade-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.trade-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                const action = this.dataset.action;
                tradeActionInput.value = action;
                
                if (action === 'buy') {
                    executeBtn.textContent = 'Buy ' + tokenSymbol;
                    executeBtn.className = 'execute-btn buy-btn';
                    totalLabel.textContent = 'Total Cost:';
                } else {
                    executeBtn.textContent = 'Sell ' + tokenSymbol;
                    executeBtn.className = 'execute-btn sell-btn';
                    totalLabel.textContent = 'You Receive:';
                }
                
                // Clear amount and update summary
                tokenAmountInput.value = '';
                updateSummary();
            });
        });
        
        // Order type tabs
        document.querySelectorAll('.order-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.order-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                const type = this.dataset.type;
                orderTypeInput.value = type;
                
                if (type === 'limit') {
                    limitPriceGroup.style.display = 'block';
                    limitPriceInput.value = currentPrice.toFixed(8);
                } else {
                    limitPriceGroup.style.display = 'none';
                    limitPriceInput.value = '';
                }
                
                updateSummary();
            });
        });
        
        // Quick amount buttons - FIXED FOR SELL
        document.querySelectorAll('.quick-amount').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const percent = parseInt(this.dataset.percent);
                const action = tradeActionInput.value;
                
                let maxAmount = 0;
                if (action === 'buy') {
                    // For buy: calculate max tokens based on TRX balance minus fee
                    const price = getEffectivePrice();
                    if (price > 0) {
                        maxAmount = Math.max(0, (userTrxBalance - tradingFee) / price);
                    }
                } else {
                    // For sell: use token balance
                    maxAmount = userTokenBalance;
                }
                
                const amount = (maxAmount * percent / 100).toFixed(2);
                tokenAmountInput.value = amount;
                updateSummary();
            });
        });
        
        // Amount input listener
        tokenAmountInput.addEventListener('input', updateSummary);
        if (limitPriceInput) {
            limitPriceInput.addEventListener('input', updateSummary);
        }
        
        function getEffectivePrice() {
            const orderType = orderTypeInput.value;
            const limitPrice = parseFloat(limitPriceInput?.value) || 0;
            
            return (orderType === 'limit' && limitPrice > 0) ? limitPrice : currentPrice;
        }
        
        function updateSummary() {
            const amount = parseFloat(tokenAmountInput.value) || 0;
            const price = getEffectivePrice();
            const action = tradeActionInput.value;
            
            // Prevent division by zero
            if (price <= 0) {
                document.getElementById('summary-price').textContent = '0.00000000 TRX';
                document.getElementById('summary-total').textContent = '0.0000 TRX';
                executeBtn.disabled = true;
                executeBtn.title = 'Invalid price';
                return;
            }
            
            document.getElementById('summary-price').textContent = price.toFixed(8) + ' TRX';
            
            if (action === 'buy') {
                const totalCost = (amount * price) + tradingFee;
                document.getElementById('summary-total').textContent = totalCost.toFixed(4) + ' TRX';
                
                // Disable button if insufficient balance
                if (amount <= 0) {
                    executeBtn.disabled = true;
                    executeBtn.title = 'Enter amount';
                } else if (totalCost > userTrxBalance) {
                    executeBtn.disabled = true;
                    executeBtn.title = 'Insufficient TRX balance';
                } else {
                    executeBtn.disabled = false;
                    executeBtn.title = '';
                }
            } else {
                // SELL - FIXED CALCULATION WITH DIVISION BY ZERO PROTECTION
                if (amount <= 0) {
                    document.getElementById('summary-total').textContent = '0.0000 TRX';
                    executeBtn.disabled = true;
                    executeBtn.title = 'Enter amount';
                } else {
                    const trxReceived = amount * price;
                    
                    if (trxReceived <= tradingFee) {
                        const minTokens = Math.ceil((tradingFee + 0.0001) / price);
                        document.getElementById('summary-total').textContent = '0.0000 TRX (Fee > Value)';
                        executeBtn.disabled = true;
                        executeBtn.title = 'Minimum ' + minTokens + ' tokens required';
                    } else {
                        const totalAfterFee = trxReceived - tradingFee;
                        document.getElementById('summary-total').textContent = totalAfterFee.toFixed(4) + ' TRX';
                        
                        // Disable button if insufficient token balance
                        if (amount > userTokenBalance) {
                            executeBtn.disabled = true;
                            executeBtn.title = 'Insufficient token balance';
                        } else {
                            executeBtn.disabled = false;
                            executeBtn.title = '';
                        }
                    }
                }
            }
        }
        
        // Time interval buttons
        document.querySelectorAll('.interval-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.interval-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                console.log('Selected interval:', this.dataset.interval);
            });
        });
        
        // Form validation - ENHANCED WITH DIVISION BY ZERO PROTECTION
        document.getElementById('trading-form').addEventListener('submit', function(e) {
            const amount = parseFloat(tokenAmountInput.value) || 0;
            const action = tradeActionInput.value;
            const price = getEffectivePrice();
            
            if (amount <= 0) {
                e.preventDefault();
                alert('Please enter a valid amount');
                return;
            }
            
            if (price <= 0) {
                e.preventDefault();
                alert('Invalid price. Please try again.');
                return;
            }
            
            if (action === 'buy') {
                const totalCost = (amount * price) + tradingFee;
                if (totalCost > userTrxBalance) {
                    e.preventDefault();
                    alert('Insufficient TRX balance');
                    return;
                }
            } else {
                if (amount > userTokenBalance) {
                    e.preventDefault();
                    alert('Insufficient token balance');
                    return;
                }
                
                const trxReceived = amount * price;
                if (trxReceived <= tradingFee) {
                    e.preventDefault();
                    alert('Amount too small to cover trading fee');
                    return;
                }
            }
        });
        
        // Initialize summary
        updateSummary();
        
        // Auto-refresh data every 30 seconds
        setInterval(function() {
            console.log('Auto-refresh...');
        }, 30000);
    </script>
</body>
</html>
