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

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get user's wallet
$stmt = $pdo->prepare("SELECT * FROM trxbalance WHERE user_id = ?");
$stmt->execute([$user_id]);
$wallet = $stmt->fetch();

if (!$wallet) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
$success = "";

// Handle transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer'])) {
    $recipient_username = sanitize($_POST['recipient_username']);
    $amount = floatval($_POST['amount']);
    
    if (empty($recipient_username) || $amount <= 0) {
        $error = "Please enter valid recipient username and amount";
    } elseif ($amount > $wallet['balance']) {
        $error = "Insufficient balance";
    } elseif ($recipient_username === $username) {
        $error = "Cannot transfer to yourself";
    } else {
        // Check if recipient exists
        $stmt = $pdo->prepare("SELECT tb.*, u.id as user_id FROM trxbalance tb JOIN users2 u ON tb.user_id = u.id WHERE u.username = ?");
        $stmt->execute([$recipient_username]);
        $recipient = $stmt->fetch();
        
        if (!$recipient) {
            $error = "Recipient not found or doesn't have a wallet";
        } else {
            try {
                $pdo->beginTransaction();
                
                // Deduct from sender
                $stmt = $pdo->prepare("UPDATE trxbalance SET balance = balance - ? WHERE user_id = ?");
                $stmt->execute([$amount, $user_id]);
                
                // Add to recipient
                $stmt = $pdo->prepare("UPDATE trxbalance SET balance = balance + ? WHERE user_id = ?");
                $stmt->execute([$amount, $recipient['user_id']]);
                
                // Record transaction for sender
                $tx_id = 'txn_' . uniqid();
                $stmt = $pdo->prepare("INSERT INTO trxhistory (user_id, from_address, to_address, amount, tx_hash, status) VALUES (?, ?, ?, ?, ?, 'send')");
                $stmt->execute([$user_id, $wallet['address'], $recipient['address'], $amount, $tx_id]);
                
                // Record transaction for recipient
                $stmt = $pdo->prepare("INSERT INTO trxhistory (user_id, from_address, to_address, amount, tx_hash, status) VALUES (?, ?, ?, ?, ?, 'receive')");
                $stmt->execute([$recipient['user_id'], $wallet['address'], $recipient['address'], $amount, $tx_id]);
                
                $pdo->commit();
                
                // Update wallet balance
                $wallet['balance'] -= $amount;
                
                $success = "Transfer successful! Sent " . number_format($amount, 2) . " TRX to " . htmlspecialchars($recipient_username);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Transfer failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer - TRON Wallet</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        background: {
                            dark: '#0C0C0E',
                            card: '#1A1A1D'
                        },
                        accent: {
                            green: '#00FF7F',
                            red: '#FF4C4C',
                            yellow: '#FFD700'
                        },
                        text: {
                            primary: '#FFFFFF',
                            secondary: '#AAAAAA'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #0C0C0E;
            color: #FFFFFF;
            font-family: 'Arial', sans-serif;
            padding-bottom: 80px;
        }
    </style>
</head>
<body>
    <?php include "../includes/loader.php"; ?>
    <?php include "../includes/successalert.php"; ?>
    <?php include "../includes/failalert.php"; ?>
    <?php include "../includes/transactionsuccess.php"; ?>
    
    <div class="container mx-auto px-4 py-6">
        <header class="flex justify-between items-center mb-6">
            <div class="flex items-center">
                <a href="dashboard.php" class="mr-4">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 12H5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 19L5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <h1 class="text-xl font-bold">Transfer TRX</h1>
            </div>
        </header>
        
        <main>
            <!-- Balance Card -->
            <div class="bg-background-card p-4 rounded-xl shadow-lg mb-6">
                <div class="text-center">
                    <div class="text-text-secondary">Available Balance</div>
                    <div class="text-2xl font-bold"><?php echo number_format($wallet['balance'], 2); ?> TRX</div>
                    <div class="text-text-secondary">≈ $<?php echo number_format($wallet['balance'] * 0.20, 2); ?> USD</div>
                </div>
            </div>
            
            <!-- Transfer Form -->
            <div class="bg-background-card p-6 rounded-xl shadow-lg mb-6">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-accent-yellow bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-accent-yellow" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17 1L21 5L17 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M3 11V9C3 7.93913 3.42143 6.92172 4.17157 6.17157C4.92172 5.42143 5.93913 5 7 5H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7 23L3 19L7 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M21 13V15C21 16.0609 20.5786 17.0783 19.8284 17.8284C19.0783 18.5786 18.0609 19 17 19H3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold">Send TRX</h2>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="bg-red-800 bg-opacity-50 text-white p-3 rounded-lg mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="bg-green-800 bg-opacity-50 text-white p-3 rounded-lg mb-4">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" onsubmit="showLoader()">
                    <div class="mb-4">
                        <label for="recipient_username" class="block text-text-secondary mb-2">Recipient Username</label>
                        <input type="text" id="recipient_username" name="recipient_username" class="w-full bg-background-dark border border-gray-700 rounded-lg px-4 py-3 focus:outline-none focus:border-accent-yellow" placeholder="Enter username" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="amount" class="block text-text-secondary mb-2">Amount (TRX)</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" max="<?php echo $wallet['balance']; ?>" class="w-full bg-background-dark border border-gray-700 rounded-lg px-4 py-3 focus:outline-none focus:border-accent-yellow" placeholder="0.00" required>
                        <div class="flex justify-between mt-2">
                            <button type="button" onclick="setAmount(<?php echo $wallet['balance'] * 0.25; ?>)" class="text-accent-yellow text-sm">25%</button>
                            <button type="button" onclick="setAmount(<?php echo $wallet['balance'] * 0.50; ?>)" class="text-accent-yellow text-sm">50%</button>
                            <button type="button" onclick="setAmount(<?php echo $wallet['balance'] * 0.75; ?>)" class="text-accent-yellow text-sm">75%</button>
                            <button type="button" onclick="setAmount(<?php echo $wallet['balance']; ?>)" class="text-accent-yellow text-sm">Max</button>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <div class="bg-background-dark p-3 rounded-lg">
                            <div class="flex justify-between text-sm">
                                <span class="text-text-secondary">Network Fee:</span>
                                <span>Free</span>
                            </div>
                            <div class="flex justify-between text-sm mt-1">
                                <span class="text-text-secondary">You will send:</span>
                                <span id="totalAmount">0.00 TRX</span>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="transfer" class="w-full bg-accent-yellow text-black py-3 rounded-lg font-medium hover:bg-opacity-80 transition">
                        Send TRX
                    </button>
                </form>
            </div>
        </main>
    </div>
    
    <?php include "../includes/bottomnav.php"; ?>
    
    <script>
        function setAmount(amount) {
            document.getElementById('amount').value = amount.toFixed(2);
            updateTotal();
        }
        
        function updateTotal() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            document.getElementById('totalAmount').textContent = amount.toFixed(2) + ' TRX';
        }
        
        document.getElementById('amount').addEventListener('input', updateTotal);
        
        // Show transaction success if transfer was successful
        <?php if (!empty($success)): ?>
            setTimeout(() => {
                showTransactionSuccess({
                    amount: '<?php echo isset($amount) ? number_format($amount, 2) : "0.00"; ?>',
                    txId: 'Internal Transfer',
                    date: '<?php echo date('M j, Y H:i'); ?>'
                });
            }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>
