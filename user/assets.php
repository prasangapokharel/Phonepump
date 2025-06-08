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

// Get TRX price in USD
function getTRXPrice() {
    $api_url = 'https://api.api-ninjas.com/v1/cryptoprice?symbol=TRXUSDT';
    $api_key = 'jRN/iU++CJrVw0zkBf9tBg==ekPzRifWfQ8jCTFe';
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $api_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'X-Api-Key: ' . $api_key,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        return 0.20; // Fallback price
    }
    
    $data = json_decode($response, true);
    return isset($data['price']) ? floatval($data['price']) : 0.20;
}

$trx_price_usd = getTRXPrice();
$wallet_usd_value = $wallet ? $wallet['balance'] * $trx_price_usd : 0;

// Get user's token balances
$stmt = $pdo->prepare("
    SELECT tb.*, t.name, t.symbol, t.current_price, t.image_url,
           (tb.balance * t.current_price) as current_value
    FROM token_balances tb
    JOIN tokens t ON tb.token_id = t.id
    WHERE tb.user_id = ? AND tb.balance > 0
    ORDER BY current_value DESC
");
$stmt->execute([$user_id]);
$token_balances = $stmt->fetchAll();

// Calculate total portfolio value
$total_token_value_trx = 0;
foreach ($token_balances as $token) {
    $total_token_value_trx += $token['current_value'];
}

$total_portfolio_trx = ($wallet ? $wallet['balance'] : 0) + $total_token_value_trx;
$total_portfolio_usd = $total_portfolio_trx * $trx_price_usd;

// Get recent transactions - using separate queries to avoid collation issues
$recent_transactions = [];

try {
    // Get TRX transactions
    $stmt = $pdo->prepare("
        SELECT 
            'trx' as type,
            amount,
            status,
            timestamp as created_at,
            tx_hash as transaction_hash,
            COALESCE(from_address, '') as from_address,
            COALESCE(to_address, '') as to_address,
            COALESCE(transaction_type, 'transfer') as transaction_type
        FROM trxhistory 
        WHERE user_id = ?
        ORDER BY timestamp DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $trx_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get token transactions
    $stmt = $pdo->prepare("
        SELECT 
            'token' as type,
            trx_amount as amount,
            status,
            created_at,
            transaction_hash,
            '' as from_address,
            '' as to_address,
            transaction_type
        FROM token_transactions 
        WHERE user_id = ?
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $token_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine and sort transactions
    $recent_transactions = array_merge($trx_transactions, $token_transactions);
    
    // Sort by created_at timestamp
    usort($recent_transactions, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Limit to 10 most recent
    $recent_transactions = array_slice($recent_transactions, 0, 10);
    
} catch (Exception $e) {
    // If there's an error, just use empty array
    $recent_transactions = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assets</title>
    <link rel="stylesheet" href="../assets/css/trade.css">
    <style>
        /* Component-specific styles - global styles are in bottomnav.php */
        .balance-card {
            background: #111;
            border: 1px solid #333;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .balance-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .balance-title {
            font-size: 16px;
            color: #999;
        }
        
        .balance-refresh {
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .balance-refresh svg {
            width: 16px;
            height: 16px;
        }
        
        .balance-amount {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .balance-value {
            font-size: 14px;
            color: #999;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 24px 0 16px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        .section-action {
            color: #fff;
            font-size: 14px;
            text-decoration: none;
        }
        
        .token-list {
            background: #111;
            border: 1px solid #333;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .token-item {
            display: flex;
            padding: 16px;
            border-bottom: 1px solid #222;
        }
        
        .token-item:last-child {
            border-bottom: none;
        }
        
        .token-icon {
            width: 40px;
            height: 40px;
            border-radius: 20px;
            background: #222;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-weight: bold;
        }
        
        .token-details {
            flex: 1;
        }
        
        .token-name {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .token-balance {
            font-size: 14px;
            color: #999;
        }
        
        .token-value {
            text-align: right;
        }
        
        .token-value-trx {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .token-value-usd {
            font-size: 14px;
            color: #999;
        }
        
        .empty-state {
            background: #111;
            border: 1px solid #333;
            border-radius: 4px;
            padding: 40px 20px;
            text-align: center;
            color: #999;
        }
        
        .empty-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 16px;
        }
        
        .empty-text {
            margin-bottom: 16px;
        }
        
        .empty-action {
            background: #fff;
            color: #000;
            padding: 8px 16px;
            border: none;
            font-weight: 500;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
        }
        
        .transactions-list {
            background: #111;
            border: 1px solid #333;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .transaction-item {
            display: flex;
            padding: 16px;
            border-bottom: 1px solid #222;
        }
        
        .transaction-item:last-child {
            border-bottom: none;
        }
        
        .transaction-icon {
            width: 40px;
            height: 40px;
            border-radius: 20px;
            background: #222;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
        }
        
        .transaction-icon svg {
            width: 20px;
            height: 20px;
        }
        
        .transaction-details {
            flex: 1;
        }
        
        .transaction-type {
            font-weight: 600;
            margin-bottom: 4px;
            text-transform: capitalize;
        }
        
        .transaction-date {
            font-size: 14px;
            color: #999;
        }
        
        .transaction-amount {
            text-align: right;
        }
        
        .transaction-amount-value {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .transaction-status {
            font-size: 14px;
            color: #999;
            text-transform: capitalize;
        }
        
        .tabs {
            display: flex;
            background: #111;
            border: 1px solid #333;
            border-radius: 4px;
            margin-bottom: 16px;
            overflow: hidden;
        }
        
        .tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            color: #999;
            font-weight: 500;
            cursor: pointer;
            border-right: 1px solid #222;
        }
        
        .tab:last-child {
            border-right: none;
        }
        
        .tab.active {
            background: #222;
            color: #fff;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        .icon{
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="app">
  

        <div class="assets-container">
            <div class="balance-card">
                <div class="balance-header">
                    <div class="balance-title">Total Balance</div>
                    <button class="balance-refresh" onclick="location.reload()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M23 4v6h-6"></path>
                            <path d="M1 20v-6h6"></path>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10"></path>
                            <path d="M20.49 15a9 9 0 0 1-14.85 3.36L1 14"></path>
                        </svg>
                    </button>
                </div>
                <div class="balance-amount"><?php echo number_format($total_portfolio_trx, 4); ?> TRX</div>
                <div class="balance-value">≈ $<?php echo number_format($total_portfolio_usd, 2); ?> USD</div>
            </div>

            <div class="tabs">
                <div class="tab active" onclick="switchTab('assets')">Assets</div>
                <div class="tab" onclick="switchTab('transactions')">Transactions</div>
            </div>

            <div id="assets-tab" class="tab-content active">
                <div class="section-header">
                    <div class="section-title">TRX</div>
                </div>
                
                <div class="token-list">
                    <div class="token-item">
                        <div class="token-icon">
                            <svg viewBox="0 0 482 507.15" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                                <path fill="currentColor" d="M475.44,152.78C451.67,130.83,418.79,97.31,392,73.54l-1.58-1.11a30.33,30.33,0,0,0-8.8-4.91h0C317,55.48,16.48-.71,10.62,0A11.07,11.07,0,0,0,6,1.75L4.52,2.94A17.51,17.51,0,0,0,.4,9.59l-.4,1v6.5C33.84,111.34,167.44,420,193.74,492.41c1.59,4.91,4.6,14.26,10.23,14.74h1.26c3,0,15.85-17,15.85-17S450.56,211.9,473.78,182.26a74.25,74.25,0,0,0,7.92-11.73A19.1,19.1,0,0,0,475.44,152.78ZM280,185.19,377.9,104l57.45,52.93Zm-38-5.31L73.3,41.69,346.12,92Zm15.22,36.22,172.58-27.82L232.41,426ZM50.4,55.48,227.82,206,202.14,426.16Z"/>
                            </svg>
                        </div>
                        <div class="token-details">
                            <div class="token-name">TRON</div>
                            <div class="token-balance"><?php echo number_format($wallet ? $wallet['balance'] : 0, 4); ?> TRX</div>
                        </div>
                        <div class="token-value">
                            <div class="token-value-trx"><?php echo number_format($wallet ? $wallet['balance'] : 0, 4); ?> TRX</div>
                            <div class="token-value-usd">≈ $<?php echo number_format($wallet_usd_value, 2); ?></div>
                        </div>
                    </div>
                </div>

                <div class="section-header">
                    <div class="section-title">Tokens</div>
                    <a href="trade.php" class="section-action">Trade</a>
                </div>
                
                <?php if (empty($token_balances)): ?>
                    <div class="empty-state">
                        <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <div class="empty-text">No tokens in your portfolio yet</div>
                        <a href="trade.php" class="empty-action">Start Trading</a>
                    </div>
                <?php else: ?>
                    <div class="token-list">
                        <?php foreach ($token_balances as $token): ?>
                            <div class="token-item">
                                <div class="token-icon">
                                    <?php if ($token['image_url']): ?>
                                        <img class="icon" src="../<?php echo htmlspecialchars($token['image_url']); ?>" alt="<?php echo htmlspecialchars($token['symbol']); ?>" width="40" height="40">
                                    <?php else: ?>
                                        <?php echo substr($token['symbol'], 0, 2); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="token-details">
                                    <div class="token-name"><?php echo htmlspecialchars($token['name']); ?></div>
                                    <div class="token-balance"><?php echo number_format($token['balance'], 0); ?> <?php echo htmlspecialchars($token['symbol']); ?></div>
                                </div>
                                <div class="token-value">
                                    <div class="token-value-trx"><?php echo number_format($token['current_value'], 4); ?> TRX</div>
                                    <div class="token-value-usd">≈ $<?php echo number_format($token['current_value'] * $trx_price_usd, 2); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div id="transactions-tab" class="tab-content">
                <div class="section-header">
                    <div class="section-title">Recent Transactions</div>
                </div>
                
                <?php if (empty($recent_transactions)): ?>
                    <div class="empty-state">
                        <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <div class="empty-text">No transactions yet</div>
                        <a href="trade.php" class="empty-action">Start Trading</a>
                    </div>
                <?php else: ?>
                    <div class="transactions-list">
                        <?php foreach ($recent_transactions as $tx): ?>
                            <div class="transaction-item">
                                <div class="transaction-icon">
                                    <?php if ($tx['transaction_type'] == 'buy' || $tx['transaction_type'] == 'initial_buy'): ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="12" y1="19" x2="12" y2="5"></line>
                                            <polyline points="5 12 12 5 19 12"></polyline>
                                        </svg>
                                    <?php elseif ($tx['transaction_type'] == 'sell'): ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="12" y1="5" x2="12" y2="19"></line>
                                            <polyline points="19 12 12 19 5 12"></polyline>
                                        </svg>
                                    <?php else: ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="22" y1="12" x2="2" y2="12"></line>
                                            <polyline points="12 2 2 12 12 22"></polyline>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="transaction-details">
                                    <div class="transaction-type"><?php echo str_replace('_', ' ', $tx['transaction_type']); ?></div>
                                    <div class="transaction-date"><?php echo date('M d, Y H:i', strtotime($tx['created_at'])); ?></div>
                                </div>
                                <div class="transaction-amount">
                                    <div class="transaction-amount-value">
                                        <?php 
                                            $prefix = ($tx['transaction_type'] == 'sell' || $tx['amount'] > 0) ? '+' : '';
                                            echo $prefix . number_format($tx['amount'], 4) . ' TRX'; 
                                        ?>
                                    </div>
                                    <div class="transaction-status"><?php echo $tx['status']; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php include '../includes/bottomnav.php'; ?>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked tab
            document.querySelectorAll('.tab').forEach(tab => {
                if (tab.textContent.toLowerCase() === tabName) {
                    tab.classList.add('active');
                }
            });
        }
    </script>
</body>
</html>
