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

// Include TRON wallet libraries
require_once "../vendor/autoload.php";

use kornrunner\Keccak;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Base58;

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

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

                // Insert generated address into database
                $stmt = $pdo->prepare("INSERT INTO trxbalance (user_id, private_key, address, username, balance, status) VALUES (?, ?, ?, ?, 0.00, 'Active')");
                
                if ($stmt->execute([$user_id, $privateKeyHex, $tronAddress, $username])) {
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

// Get real-time TRX price from API Ninjas
function getTRXPrice() {
    $api_url = 'https://api.api-ninjas.com/v1/cryptoprice?symbol=TRXUSDT';
    $api_key = 'jRN/iU++CJrVw0zkBf9tBg==ekPzRifWfQ8jCTFe';
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $api_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'X-Api-Key: ' . $api_key,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        error_log("API Error: " . $error);
        return 0.20; // Fallback price if API fails
    }
    
    $data = json_decode($response, true);
    if (isset($data['price'])) {
        return floatval($data['price']);
    } else {
        error_log("API Response Error: " . $response);
        return 0.20; // Fallback price if response is invalid
    }
}

// Get TRX price
$trx_price = getTRXPrice();
$price_change = -1.56; // Mock price change for now

// Get recent transactions
function getRecentTransactions($pdo, $user_id, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT amount, status, timestamp, tx_hash 
        FROM trxhistory 
        WHERE user_id = ? 
        ORDER BY timestamp DESC 
        LIMIT ?
    ");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$recent_transactions = getRecentTransactions($pdo, $user_id);

// Helper function to format time ago
function getTimeAgo($timestamp) {
    $now = time();
    $diff = $now - strtotime($timestamp);
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet</title>
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

        <?php if ($wallet): ?>
            <!-- Balance Section -->
            <div class="balance-section">
                <div class="balance-label">Total TRX Balance</div>
                <div id="balance-display" class="balance-amount">
                    <?php echo number_format($wallet['balance'], 4); ?>
                </div>
                <div id="balance-usd" class="balance-usd">
                    $<?php echo number_format($wallet['balance'] * $trx_price, 2); ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="actions">
                <a href="withdraw.php" class="action-btn">
                    <svg class="action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 19V5"/>
                        <path d="m5 12 7-7 7 7"/>
                    </svg>
                    Send
                </a>
                <a href="deposit.php" class="action-btn">
                    <svg class="action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14"/>
                        <path d="m19 12-7 7-7-7"/>
                    </svg>
                    Receive
                </a>
            </div>

            <!-- Transactions -->
            <div class="transactions">
                <div class="transactions-header">
                    <div class="transactions-title">Recent Activity</div>
                    <button class="refresh-btn" onclick="loadTransactions()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 2v6h-6"></path>
                            <path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path>
                            <path d="M3 22v-6h6"></path>
                            <path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path>
                        </svg>
                    </button>
                </div>
                
                <div id="transactions-container">
                    <?php if (empty($recent_transactions)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14,2 14,8 20,8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                    <polyline points="10,9 9,9 8,9"/>
                                </svg>
                            </div>
                            <div class="empty-text">No transactions yet</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_transactions as $tx): ?>
                            <div class="transaction">
                                <div class="tx-left">
                                    <div class="tx-icon <?php echo $tx['status'] === 'receive' ? 'receive' : 'send'; ?>">
                                        <?php if ($tx['status'] === 'receive'): ?>
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 5v14"/>
                                                <path d="m19 12-7 7-7-7"/>
                                            </svg>
                                        <?php else: ?>
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 19V5"/>
                                                <path d="m5 12 7-7 7 7"/>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tx-details">
                                        <div class="tx-type">
                                            <?php echo $tx['status'] === 'receive' ? 'Received' : 'Sent'; ?> TRX
                                        </div>
                                        <div class="tx-time"><?php echo getTimeAgo($tx['timestamp']); ?></div>
                                    </div>
                                </div>
                                <div class="tx-amount <?php echo $tx['status'] === 'receive' ? 'positive' : 'negative'; ?>">
                                    <?php echo $tx['status'] === 'receive' ? '+' : '-'; ?><?php echo number_format(floatval($tx['amount']), 4); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Create Wallet -->
            <div class="create-wallet">
                <div class="create-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14"/>
                        <path d="M5 12h14"/>
                    </svg>
                </div>
                <div class="create-title">Create Your Wallet</div>
                <div class="create-description">
                    Get started with your secure TRON wallet to send, receive, and manage your TRX tokens.
                </div>
                <form method="POST">
                    <button type="submit" name="create_wallet" class="create-btn">
                        Create Wallet
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Bottom Navigation -->
        <?php include '../includes/bottomnav.php'; ?>
    </div>

    <script>
        function getTimeAgo(timestamp) {
            const now = new Date();
            const diffInSeconds = Math.floor((now - new Date(timestamp * 1000)) / 1000);
            
            if (diffInSeconds < 60) return 'Just now';
            if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + 'm ago';
            if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + 'h ago';
            return Math.floor(diffInSeconds / 86400) + 'd ago';
        }
        
        let currentBalance = <?php echo $wallet ? $wallet['balance'] : 0; ?>;
        let balanceCheckInterval;
        let transactionCheckInterval;
        
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($wallet): ?>
                startBalanceMonitoring();
                startTransactionMonitoring();
            <?php endif; ?>
        });
        
        function startBalanceMonitoring() {
            balanceCheckInterval = setInterval(checkBalance, 30000);
        }
        
        function startTransactionMonitoring() {
            transactionCheckInterval = setInterval(loadTransactions, 60000);
        }
        
        function checkBalance() {
            fetch('../api/balance_checker.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.balance_updated) {
                            updateBalanceDisplay(data.new_balance, data.usd_value);
                            
                            if (data.new_balance > currentBalance) {
                                const balanceSection = document.querySelector('.balance-section');
                                balanceSection.classList.add('balance-updated');
                                setTimeout(() => {
                                    balanceSection.classList.remove('balance-updated');
                                }, 1000);
                                
                                loadTransactions();
                            }
                            
                            currentBalance = data.new_balance;
                        }
                    }
                })
                .catch(error => {
                    console.error('Balance check error:', error);
                });
        }
        
        function updateBalanceDisplay(balance, usdValue) {
            document.getElementById('balance-display').textContent = balance.toFixed(4);
            document.getElementById('balance-usd').textContent = '$' + usdValue.toFixed(2);
        }
        
        function loadTransactions() {
            fetch('../api/transaction_history.php?limit=8')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTransactions(data.transactions);
                    }
                })
                .catch(error => {
                    console.error('Transaction load error:', error);
                });
        }
        
        function displayTransactions(transactions) {
            const container = document.getElementById('transactions-container');
            
            if (transactions.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14,2 14,8 20,8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10,9 9,9 8,9"/>
                            </svg>
                        </div>
                        <div class="empty-text">No transactions yet</div>
                    </div>
                `;
                return;
            }
            
            let html = '';
            
            transactions.forEach((tx, index) => {
                const isReceive = tx.status === 'receive';
                const date = new Date(tx.timestamp);
                const timeAgo = getTimeAgo(date / 1000);
                
                html += `
                    <div class="transaction">
                        <div class="tx-left">
                            <div class="tx-icon ${isReceive ? 'receive' : 'send'}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    ${isReceive ? 
                                        '<path d="M12 5v14"/><path d="m19 12-7 7-7-7"/>' :
                                        '<path d="M12 19V5"/><path d="m5 12 7-7 7 7"/>'
                                    }
                                </svg>
                            </div>
                            <div class="tx-details">
                                <div class="tx-type">${isReceive ? 'Received' : 'Sent'} TRX</div>
                                <div class="tx-time">${timeAgo}</div>
                            </div>
                        </div>
                        <div class="tx-amount ${isReceive ? 'positive' : 'negative'}">
                            ${isReceive ? '+' : '-'}${parseFloat(tx.amount).toFixed(4)}
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Cleanup intervals when page unloads
        window.addEventListener('beforeunload', function() {
            if (balanceCheckInterval) clearInterval(balanceCheckInterval);
            if (transactionCheckInterval) clearInterval(transactionCheckInterval);
        });
    </script>
</body>
</html>