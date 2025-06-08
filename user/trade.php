<?php
session_start();
require_once "../connect/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

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

$trading_fee = floatval($settings['trading_fee_trx'] ?? 10);
$company_wallet = $settings['company_wallet_address'] ?? 'TCompanyWallet123';

$error = "";
$success = "";

// Handle buy/sell transactions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trade_action'])) {
    $token_id = intval($_POST['token_id']);
    $action = $_POST['trade_action']; // 'buy' or 'sell'
    $amount = floatval($_POST['amount']);
    
    if ($amount <= 0) {
        $error = "Invalid amount.";
    } else {
        try {
            // Get token and bonding curve info
            $stmt = $pdo->prepare("
                SELECT t.*, bc.*, 
                       (SELECT COUNT(*) FROM token_transactions WHERE token_id = t.id AND transaction_type = 'buy' AND user_id != t.creator_id) as external_buys
                FROM tokens t 
                JOIN bonding_curves bc ON t.id = bc.token_id 
                WHERE t.id = ?
            ");
            $stmt->execute([$token_id]);
            $token = $stmt->fetch();
            
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
                                market_cap = ? * total_supply,
                                total_transactions = total_transactions + 1,
                                volume_total = volume_total + ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$price_per_token, $price_per_token, $total_cost, $token_id]);
                        
                        $pdo->commit();
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
                        // Special case: If creator is selling and no external buys, return initial investment
                        $is_creator = ($user_id == $token['creator_id']);
                        $has_external_buys = ($token['external_buys'] > 0);
                        
                        if ($is_creator && !$has_external_buys) {
                            // Calculate what percentage of their tokens they're selling
                            $creator_total_tokens = $token['creator_initial_tokens'] + $token['tokens_sold'];
                            $sell_percentage = $amount / $creator_total_tokens;
                            
                            // Return proportional amount of initial investment
                            $trx_received = $token['initial_buy_amount'] * $sell_percentage;
                            $price_per_token = $trx_received / $amount; // Calculate price per token
                        } else {
                            // Normal bonding curve sell
                            $price_per_token = $token['current_price'];
                            $trx_received = $amount * $price_per_token;
                        }
                        
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
                            
                            // Record transaction - NOW price_per_token is properly defined
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
                            
                            $pdo->commit();
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

// Get trending tokens
$stmt = $pdo->prepare("
    SELECT t.*, bc.current_progress, bc.real_trx_reserves, bc.tokens_sold, bc.tokens_available,
           COALESCE(tb.balance, 0) as user_balance,
           (SELECT COUNT(*) FROM token_transactions WHERE token_id = t.id AND transaction_type = 'buy' AND user_id != t.creator_id) as external_buys
    FROM tokens t 
    LEFT JOIN bonding_curves bc ON t.id = bc.token_id 
    LEFT JOIN token_balances tb ON t.id = tb.token_id AND tb.user_id = ?
    WHERE t.status = 'active' 
    ORDER BY t.volume_24h DESC, t.market_cap DESC 
    LIMIT 20
");
$stmt->execute([$user_id]);
$tokens = $stmt->fetchAll();

// Get user's portfolio
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trade Tokens</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        /* Trading-specific styles - global styles are in bottomnav.php */
        .trade-tabs {
            display: flex;
            background: #111;
            border: 1px solid #333;
            margin-bottom: 20px;
        }
        
        .tab-btn {
            flex: 1;
            padding: 12px;
            border: none;
            background: transparent;
            font-weight: 600;
            cursor: pointer;
            color: #999;
            border-right: 1px solid #333;
        }
        
        .tab-btn:last-child {
            border-right: none;
        }
        
        .tab-btn.active {
            background: #222;
            color: #fff;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .token-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .token-card {
            background: #111;
            border: 1px solid #333;
            padding: 20px;
        }
        
        .token-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .token-image {
            width: 48px;
            height: 48px;
            border: 1px solid #333;
            margin-right: 12px;
            object-fit: cover;
            background: #222;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: bold;
        }
        
        .token-info h3 {
            margin: 0;
            font-size: 18px;
            color: #fff;
        }
        
        .token-symbol {
            color: #999;
            font-size: 14px;
        }
        
        .token-price {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 10px;
        }
        
        .progress-section {
            margin: 15px 0;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #333;
            margin: 8px 0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #fff, #aaa);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .user-balance-indicator {
            background: #222;
            border: 1px solid #333;
            padding: 12px;
            margin: 12px 0;
            text-align: center;
        }
        
        .user-balance-text {
            font-size: 14px;
            color: #fff;
            font-weight: 600;
        }
        
        .trade-actions {
            display: flex;
            gap: 12px;
            margin-top: 15px;
        }
        
        .trade-btn {
            flex: 1;
            padding: 12px;
            border: 1px solid #333;
            background: #222;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            text-transform: uppercase;
        }
        
        .buy-btn {
            background: #fff;
            color: #000;
        }
        
        .sell-btn {
            background: #000;
            color: #fff;
            border: 1px solid #fff;
        }
        
        .trade-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #111;
            border: 1px solid #333;
            padding: 30px;
            width: 90%;
            max-width: 400px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #333;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #fff;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
            padding: 0;
            width: 24px;
            height: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #fff;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #333;
            background: #222;
            color: #fff;
            font-size: 16px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #fff;
        }
        
        .trade-summary {
            background: #222;
            border: 1px solid #333;
            padding: 16px;
            margin: 16px 0;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .summary-row:last-child {
            margin-bottom: 0;
            padding-top: 8px;
            border-top: 1px solid #333;
            font-weight: 600;
        }
        
        .summary-label {
            color: #999;
        }
        
        .summary-value {
            color: #fff;
            font-weight: 500;
        }
        
        .portfolio-card {
            background: #111;
            border: 1px solid #333;
            padding: 20px;
            margin-bottom: 15px;
        }
        
        .portfolio-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #333;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-text {
            font-size: 18px;
            margin-bottom: 20px;
        }
        
        .launch-btn {
            background: #fff;
            color: #000;
            border: none;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-transform: uppercase;
        }
        
        .alert {
            padding: 16px;
            margin-bottom: 20px;
            border: 1px solid #333;
        }
        
        .alert-success {
            background: #222;
            color: #fff;
        }
        
        .alert-error {
            background: #222;
            color: #fff;
        }
        
        .alert-warning {
            background: #222;
            color: #fff;
        }
        
        .creator-badge {
            display: inline-block;
            background: #333;
            color: #fff;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <div class="app">
        <div class="header">
            <div class="logo">Trade</div>
            <a href="launch.php" class="action-btn">Launch</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="trade-container">
            <?php if (!$wallet): ?>
                <div class="alert alert-warning">
                    Please create a wallet first to start trading.
                    <a href="../dashboard.php">Create Wallet</a>
                </div>
            <?php else: ?>
                <div class="balance-display">
                    <div class="balance-amount"><?php echo number_format($wallet['balance'], 4); ?> TRX</div>
                    <div class="balance-label">Available Balance</div>
                </div>

                <div class="trade-tabs">
                    <button class="tab-btn active" onclick="switchTab('market')">Market</button>
                    <button class="tab-btn" onclick="switchTab('portfolio')">Portfolio</button>
                </div>

                <div id="market-tab" class="tab-content active">
                    <h2>Trending Tokens</h2>
                    <div class="token-grid">
                        <?php foreach ($tokens as $token): ?>
                            <div class="token-card" onclick="window.location.href='order.php?token_id=<?php echo $token['id']; ?>'" style="cursor: pointer;">
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
                                        <h3>
                                            <?php echo htmlspecialchars($token['name']); ?>
                                            <?php if ($token['creator_id'] == $user_id): ?>
                                                <span class="creator-badge">Creator</span>
                                            <?php endif; ?>
                                        </h3>
                                        <div class="token-symbol"><?php echo htmlspecialchars($token['symbol']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="token-price"><?php echo number_format($token['current_price'], 6); ?> TRX</div>
                                
                                <div class="progress-section">
                                    <div style="display: flex; justify-content: space-between; font-size: 12px; color: #999;">
                                        <span>Bonding Curve</span>
                                        <span><?php echo number_format($token['current_progress'], 1); ?>%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $token['current_progress']; ?>%"></div>
                                    </div>
                                </div>

                                <?php if ($token['user_balance'] > 0): ?>
                                    <div class="user-balance-indicator">
                                        <div class="user-balance-text">
                                            You own: <?php echo number_format($token['user_balance'], 2); ?> <?php echo $token['symbol']; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="trade-actions">
                                    <button class="trade-btn buy-btn" onclick="openTradeModal(
                                        <?php echo $token['id']; ?>, 
                                        'buy', 
                                        '<?php echo htmlspecialchars($token['name']); ?>', 
                                        '<?php echo htmlspecialchars($token['symbol']); ?>', 
                                        <?php echo $token['current_price']; ?>,
                                        false,
                                        0,
                                        <?php echo $token['external_buys']; ?>
                                    )">
                                        Buy
                                    </button>
                                    <?php if ($token['user_balance'] > 0): ?>
                                        <button class="trade-btn sell-btn" onclick="openTradeModal(
                                            <?php echo $token['id']; ?>, 
                                            'sell', 
                                            '<?php echo htmlspecialchars($token['name']); ?>', 
                                            '<?php echo htmlspecialchars($token['symbol']); ?>', 
                                            <?php echo $token['current_price']; ?>,
                                            <?php echo ($token['creator_id'] == $user_id) ? 'true' : 'false'; ?>,
                                            <?php echo $token['initial_buy_amount'] ?? 0; ?>,
                                            <?php echo $token['external_buys']; ?>
                                        )">
                                            Sell
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="portfolio-tab" class="tab-content">
                    <h2>Your Portfolio</h2>
                    <?php if (empty($portfolio)): ?>
                        <div class="empty-state">
                            <div class="empty-text">No tokens in your portfolio yet</div>
                            <button onclick="switchTab('market')" class="launch-btn">Start Trading</button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($portfolio as $holding): ?>
                            <div class="portfolio-card" onclick="window.location.href='order.php?token_id=<?php echo $holding['token_id']; ?>'" style="cursor: pointer;">
                                <div class="token-header">
                                    <?php if ($holding['image_url']): ?>
                                        <img src="../<?php echo htmlspecialchars($holding['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($holding['name']); ?>" class="token-image">
                                    <?php else: ?>
                                        <div class="token-image">
                                            <?php echo substr($holding['symbol'], 0, 2); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="token-info">
                                        <h3>
                                            <?php echo htmlspecialchars($holding['name']); ?>
                                            <?php if ($holding['creator_id'] == $user_id): ?>
                                                <span class="creator-badge">Creator</span>
                                            <?php endif; ?>
                                        </h3>
                                        <div class="token-symbol"><?php echo htmlspecialchars($holding['symbol']); ?></div>
                                    </div>
                                </div>

                                <div class="portfolio-stats">
                                    <div class="stat">
                                        <div class="stat-value"><?php echo number_format($holding['balance'], 2); ?></div>
                                        <div class="stat-label">Balance</div>
                                    </div>
                                    <div class="stat">
                                        <div class="stat-value"><?php echo number_format($holding['current_price'], 6); ?></div>
                                        <div class="stat-label">Price (TRX)</div>
                                    </div>
                                    <div class="stat">
                                        <div class="stat-value"><?php echo number_format($holding['current_value'], 4); ?></div>
                                        <div class="stat-label">Value (TRX)</div>
                                    </div>
                                </div>

                                <div class="trade-actions">
                                    <button class="trade-btn sell-btn" onclick="openTradeModal(
                                        <?php echo $holding['token_id']; ?>, 
                                        'sell', 
                                        '<?php echo htmlspecialchars($holding['name']); ?>', 
                                        '<?php echo htmlspecialchars($holding['symbol']); ?>', 
                                        <?php echo $holding['current_price']; ?>,
                                        <?php echo ($holding['creator_id'] == $user_id) ? 'true' : 'false'; ?>,
                                        <?php echo $holding['initial_buy_amount'] ?? 0; ?>,
                                        <?php echo $holding['external_buys']; ?>
                                    )">
                                        Sell
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Trade Modal -->
        <div id="trade-modal" class="trade-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modal-title">Trade Token</h3>
                    <button class="close-btn" onclick="closeTradeModal()">&times;</button>
                </div>
                
                <form method="POST" id="trade-form">
                    <input type="hidden" id="modal-token-id" name="token_id">
                    <input type="hidden" id="modal-action" name="trade_action">
                    
                    <div class="form-group">
                        <label for="trade-amount">Amount</label>
                        <input type="number" id="trade-amount" name="amount" step="0.01" min="0.01" required>
                    </div>
                    
                    <div id="trade-summary" class="trade-summary">
                        <div class="summary-row">
                            <span class="summary-label">Price per token:</span>
                            <span class="summary-value" id="price-per-token">-</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Total cost:</span>
                            <span class="summary-value" id="total-cost">-</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Trading fee:</span>
                            <span class="summary-value"><?php echo $trading_fee; ?> TRX</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">You will receive:</span>
                            <span class="summary-value" id="will-receive">-</span>
                        </div>
                    </div>
                    
                    <button type="submit" id="confirm-trade-btn" class="launch-btn">
                        Confirm Trade
                    </button>
                </form>
            </div>
        </div>

        <?php include '../includes/bottomnav.php'; ?>
    </div>

    <script>
        const tradingFee = <?php echo $trading_fee; ?>;
        let currentTokenPrice = 0;
        let isCreator = false;
        let initialBuyAmount = 0;
        let hasExternalBuys = false;
        let currentTokenBalance = 0;
        
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

        function openTradeModal(tokenId, action, tokenName, tokenSymbol, tokenPrice, creator, initialBuy, externalBuys) {
            currentTokenPrice = tokenPrice;
            isCreator = creator;
            initialBuyAmount = initialBuy;
            hasExternalBuys = (externalBuys > 0);
            
            document.getElementById('modal-token-id').value = tokenId;
            document.getElementById('modal-action').value = action;
            document.getElementById('modal-title').textContent = 
                (action === 'buy' ? 'Buy ' : 'Sell ') + tokenName;
            
            document.getElementById('confirm-trade-btn').textContent = 
                'Confirm ' + (action === 'buy' ? 'Buy' : 'Sell');
            document.getElementById('confirm-trade-btn').className = 
                'launch-btn ' + (action === 'buy' ? 'buy-btn' : 'sell-btn');
            
            document.getElementById('trade-modal').style.display = 'block';
            
            // Update summary when amount changes
            document.getElementById('trade-amount').oninput = function() {
                updateTradeSummary(this.value, action);
            };
        }

        function closeTradeModal() {
            document.getElementById('trade-modal').style.display = 'none';
            document.getElementById('trade-form').reset();
        }

        function updateTradeSummary(amount, action) {
            if (!amount || amount <= 0) {
                document.getElementById('price-per-token').textContent = '-';
                document.getElementById('total-cost').textContent = '-';
                document.getElementById('will-receive').textContent = '-';
                return;
            }
            
            const tokenAmount = parseFloat(amount);
            
            if (action === 'buy') {
                const totalCost = tokenAmount * currentTokenPrice;
                const totalWithFee = totalCost + tradingFee;
                
                document.getElementById('price-per-token').textContent = currentTokenPrice.toFixed(8) + ' TRX';
                document.getElementById('total-cost').textContent = totalCost.toFixed(4) + ' TRX';
                document.getElementById('will-receive').textContent = tokenAmount.toFixed(2) + ' tokens';
            } else {
                let trxReceived;
                
                // Special case for creator selling with no external buys
                if (isCreator && !hasExternalBuys) {
                    // Get proportion of initial buy amount
                    trxReceived = initialBuyAmount;
                } else {
                    // Normal price calculation
                    trxReceived = tokenAmount * currentTokenPrice;
                }
                
                const totalAfterFee = Math.max(0, trxReceived - tradingFee);
                
                document.getElementById('price-per-token').textContent = currentTokenPrice.toFixed(8) + ' TRX';
                document.getElementById('total-cost').textContent = trxReceived.toFixed(4) + ' TRX';
                document.getElementById('will-receive').textContent = totalAfterFee.toFixed(4) + ' TRX';
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('trade-modal');
            if (event.target === modal) {
                closeTradeModal();
            }
        }

        // Auto-refresh token prices every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
