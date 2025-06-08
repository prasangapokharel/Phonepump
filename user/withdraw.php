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

// Include email service
require_once "../utils/email.php";

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get user's wallet and email
$stmt = $pdo->prepare("SELECT tb.*, u.email FROM trxbalance tb JOIN users2 u ON tb.user_id = u.id WHERE tb.user_id = ?");
$stmt->execute([$user_id]);
$wallet = $stmt->fetch();

if (!$wallet) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
$success = "";
$step = 1; // 1: Enter details, 2: Email verification, 3: Processing transaction

// Company wallet and fee
$companyWallet = 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv';
$withdrawFee = 1.5;

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

// Handle AJAX requests for transaction processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_POST['action'] === 'process_withdrawal') {
            // Check if withdrawal data exists in session
            if (!isset($_SESSION['withdrawal_data'])) {
                throw new Exception("No withdrawal data found in session");
            }
            
            $withdrawalData = $_SESSION['withdrawal_data'];
            $toAddress = $withdrawalData['to_address'];
            $amount = floatval($withdrawalData['amount']);
            $fromAddress = $wallet['address'];
            $privateKey = $wallet['private_key'];
            
            // Validate addresses
            if (!preg_match('/^T[a-zA-Z0-9]{33}$/', $toAddress)) {
                throw new Exception("Invalid destination address format");
            }
            
            if (!preg_match('/^T[a-zA-Z0-9]{33}$/', $fromAddress)) {
                throw new Exception("Invalid source address format");
            }
            
            // Convert TRX to SUN (1 TRX = 1,000,000 SUN)
            $amountInSun = intval($amount * 1000000);
            $feeInSun = intval($withdrawFee * 1000000);
            
            // Create transaction using direct API calls
            $transactionData = [
                'owner_address' => $fromAddress,
                'to_address' => $toAddress,
                'amount' => $amountInSun,
                'visible' => true
            ];
            
            // Create transaction
            $createResponse = makeApiCall('https://api.trongrid.io/wallet/createtransaction', $transactionData);
            
            if (!isset($createResponse['txID'])) {
                throw new Exception("Failed to create transaction: " . json_encode($createResponse));
            }
            
            // Sign transaction
            $signData = [
                'transaction' => $createResponse,
                'privateKey' => $privateKey
            ];
            
            $signResponse = makeApiCall('https://api.trongrid.io/wallet/gettransactionsign', $signData);
            
            if (!isset($signResponse['signature'])) {
                throw new Exception("Failed to sign transaction: " . json_encode($signResponse));
            }
            
            // Broadcast transaction
            $broadcastResponse = makeApiCall('https://api.trongrid.io/wallet/broadcasttransaction', $signResponse);
            
            if (!isset($broadcastResponse['result']) || !$broadcastResponse['result']) {
                throw new Exception("Failed to broadcast transaction: " . ($broadcastResponse['message'] ?? 'Unknown error'));
            }
            
            $txHash = $createResponse['txID'];
            
            // Create and broadcast fee transaction
            try {
                $feeTransactionData = [
                    'owner_address' => $fromAddress,
                    'to_address' => $companyWallet,
                    'amount' => $feeInSun,
                    'visible' => true
                ];
                
                $feeCreateResponse = makeApiCall('https://api.trongrid.io/wallet/createtransaction', $feeTransactionData);
                
                if (isset($feeCreateResponse['txID'])) {
                    $feeSignData = [
                        'transaction' => $feeCreateResponse,
                        'privateKey' => $privateKey
                    ];
                    
                    $feeSignResponse = makeApiCall('https://api.trongrid.io/wallet/gettransactionsign', $feeSignData);
                    
                    if (isset($feeSignResponse['signature'])) {
                        makeApiCall('https://api.trongrid.io/wallet/broadcasttransaction', $feeSignResponse);
                    }
                }
            } catch (Exception $e) {
                error_log("Fee transaction failed: " . $e->getMessage());
                // Continue even if fee transaction fails
            }
            
            // Save transaction to database
            $stmt = $pdo->prepare("INSERT INTO trxhistory (user_id, username, from_address, to_address, amount, tx_hash, status, timestamp) VALUES (?, ?, ?, ?, ?, ?, 'send', NOW())");
            $stmt->execute([$user_id, $username, $fromAddress, $toAddress, $amount, $txHash]);
            
            // Update user balance
            $stmt = $pdo->prepare("UPDATE trxbalance SET balance = balance - ? WHERE user_id = ?");
            $stmt->execute([$amount + $withdrawFee, $user_id]);
            
            // Clear session data
            unset($_SESSION['withdrawal_data']);
            
            // Clear OTP
            $stmt = $pdo->prepare("UPDATE users2 SET otp = NULL WHERE id = ?");
            $stmt->execute([$user_id]);
            
            // Send notification email
            $emailService = new EmailService();
            $emailService->sendTransactionNotification($wallet['email'], 'send', $amount, $txHash);
            
            echo json_encode([
                'success' => true,
                'txHash' => $txHash,
                'message' => 'Transaction completed successfully'
            ]);
            
        } else {
            throw new Exception("Invalid action");
        }
        
    } catch (Exception $e) {
        error_log("Withdrawal processing error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Helper function to make API calls
function makeApiCall($url, $data) {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        throw new Exception("cURL Error: " . $error);
    }
    
    if ($httpCode !== 200) {
        throw new Exception("HTTP Error: " . $httpCode . " - " . $response);
    }
    
    $decodedResponse = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON Decode Error: " . json_last_error_msg() . " - Response: " . $response);
    }
    
    return $decodedResponse;
}

// Get TRX price
$trx_price = getTRXPrice();

// Handle withdrawal request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['request_withdrawal'])) {
        $to_address = isset($_POST['to_address']) ? htmlspecialchars(strip_tags(trim($_POST['to_address']))) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $total_amount = $amount + $withdrawFee;
        
        if (empty($to_address) || $amount <= 0) {
            $error = "Please enter valid address and amount";
        } elseif (!preg_match('/^T[a-zA-Z0-9]{33}$/', $to_address)) {
            $error = "Invalid TRON address format";
        } elseif ($total_amount > $wallet['balance']) {
            $error = "Insufficient balance. You need " . number_format($total_amount, 2) . " TRX (including " . $withdrawFee . " TRX fee)";
        } elseif ($amount < 5) {
            $error = "Minimum withdrawal amount is 5 TRX";
        } else {
            // Generate OTP
            $otp = sprintf("%06d", mt_rand(1, 999999));
            
            // Store withdrawal details in session
            $_SESSION['withdrawal_data'] = [
                'to_address' => $to_address,
                'amount' => $amount,
                'otp' => $otp,
                'otp_time' => time()
            ];
            
            // Update user's OTP in database
            $stmt = $pdo->prepare("UPDATE users2 SET otp = ? WHERE id = ?");
            $stmt->execute([$otp, $user_id]);
            
            // Send OTP email using EmailService
            $emailService = new EmailService();
            $email_sent = $emailService->sendOTP($wallet['email'], $otp, 'withdrawal');
            
            if ($email_sent) {
                $success = "OTP sent to your email: " . substr($wallet['email'], 0, 3) . "***@" . substr(strrchr($wallet['email'], "@"), 1);
                $step = 2;
            } else {
                $error = "Failed to send OTP email. Please try again.";
                error_log("Failed to send OTP email to: " . $wallet['email']);
            }
        }
    } elseif (isset($_POST['verify_otp'])) {
        $entered_otp = isset($_POST['otp']) ? htmlspecialchars(strip_tags(trim($_POST['otp']))) : '';
        
        if (!isset($_SESSION['withdrawal_data'])) {
            $error = "Session expired. Please try again.";
        } elseif (time() - $_SESSION['withdrawal_data']['otp_time'] > 300) { // 5 minutes
            $error = "OTP expired. Please request a new one.";
            unset($_SESSION['withdrawal_data']);
        } elseif ($entered_otp !== $_SESSION['withdrawal_data']['otp']) {
            $error = "Invalid OTP. Please try again.";
        } else {
            // OTP verified, proceed to blockchain transaction
            $step = 3;
        }
    }
}

// Check if we're in step 2
if (isset($_SESSION['withdrawal_data']) && empty($error) && $step != 3) {
    $step = 2;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw</title>
    <link rel="stylesheet" href="../assets/css/withdraw.css">
    <style>
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #FFD700;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .processing-section {
            text-align: center;
            padding: 40px 20px;
        }
        
        .processing-icon {
            margin-bottom: 20px;
        }
        
        .processing-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #FFD700;
        }
        
        .processing-text {
            color: #AAAAAA;
            margin-bottom: 30px;
        }
        
        .transaction-details {
            background-color: #1A1A1D;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        
        .detail-label {
            color: #AAAAAA;
        }
        
        .detail-value {
            color: #FFFFFF;
            font-weight: bold;
        }
        
        .tx-hash {
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
        }
        
        .success {
            color: #00FF7F !important;
        }
        
        .error {
            color: #FF4444 !important;
        }
        
        .paste-btn, .max-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: #FFD700;
            color: #000;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
        }
        
        .form-group {
            position: relative;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #FFD700;
            color: #000;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            margin-top: 20px;
        }
        
        .btn:hover {
            background: #FFC700;
        }
        
        .otp-timer {
            text-align: center;
            margin-top: 20px;
            color: #AAAAAA;
        }
        
        .expired {
            color: #FF4444;
            margin-top: 10px;
        }
        
        .try-again-btn {
            background-color: #FFD700;
            color: #000;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
        }
        
        .try-again-btn:hover {
            background-color: #FFC700;
        }
        
        .progress-steps {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        
        .progress-step {
            padding: 5px 10px;
            margin: 0 5px;
            background: #333;
            border-radius: 4px;
            font-size: 12px;
            color: #AAA;
        }
        
        .progress-step.active {
            background: #FFD700;
            color: #000;
        }
        
        .progress-step.completed {
            background: #00FF7F;
            color: #000;
        }
        
        .api-indicator {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #00FF7F;
            color: #000;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            z-index: 1000;
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #00FF7F;
            color: #000;
            padding: 15px 20px;
            border-radius: 8px;
            font-weight: bold;
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }
        
        .notification.show {
            transform: translateX(0);
        }
    </style>
</head>
<body>
    <!-- API Status Indicator -->
    <div class="api-indicator">
        🔗 TRON API
    </div>

    <div class="app">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <a href="dashboard.php" class="back-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5"/>
                        <path d="M12 19L5 12L12 5"/>
                    </svg>
                </a>
                <h1 class="page-title">Withdraw</h1>
            </div>
        </div>

        <!-- Balance Section -->
        <div class="balance-section">
            <div class="balance-label">Available Balance</div>
            <div class="balance-amount">
                <span id="balance"><?php echo number_format($wallet['balance'], 4); ?></span> TRX
            </div>
            <div class="balance-usd">
                $<?php echo number_format($wallet['balance'] * $trx_price, 2); ?>
            </div>
        </div>

        <!-- Form Section -->
        <div class="form-section">
            <!-- Alerts -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-error fade-in">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success fade-in">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 19V5"/>
                        <path d="m5 12 7-7 7 7"/>
                    </svg>
                </div>
                <h2 class="step-title">
                    <?php 
                    if ($step === 1) echo 'Withdraw TRX';
                    elseif ($step === 2) echo 'Email Verification';
                    else echo 'Processing Transaction';
                    ?>
                </h2>
            </div>

            <?php if ($step === 1): ?>
                <!-- Step 1: Withdrawal Details -->
                <form method="POST" class="fade-in">
                    <div class="form-group">
                        <label for="to_address" class="form-label">Destination Address</label>
                        <input type="text" id="to_address" name="to_address" class="form-input" placeholder="Enter TRON address" required>
                        <button type="button" id="pasteBtn" class="paste-btn">Paste</button>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount" class="form-label">Amount (TRX)</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="5" max="<?php echo $wallet['balance'] - $withdrawFee; ?>" class="form-input" placeholder="Minimum 5 TRX" required>
                        <button type="button" id="maxBtn" class="max-btn">MAX</button>
                    </div>
                    
                    <div class="fee-breakdown">
                        <div class="fee-row">
                            <span class="fee-label">Withdrawal Amount:</span>
                            <span class="fee-amount" id="withdrawAmount">0.00 TRX</span>
                        </div>
                        <div class="fee-row">
                            <span class="fee-label">Network Fee:</span>
                            <span class="fee-amount negative"><?php echo $withdrawFee; ?> TRX</span>
                        </div>
                        <div class="fee-row total">
                            <span class="fee-label">Total Deducted:</span>
                            <span class="fee-amount" id="totalAmount"><?php echo $withdrawFee; ?> TRX</span>
                        </div>
                    </div>
                    
                    <button type="submit" name="request_withdrawal" class="submit-btn">
                        Request Withdrawal
                    </button>
                </form>
            <?php elseif ($step === 2): ?>
                <!-- Step 2: OTP Verification -->
                <div class="fade-in">
                    <div class="withdrawal-details">
                        <div class="detail-label">Withdrawal Details</div>
                        <div class="detail-value">Amount: <?php echo number_format($_SESSION['withdrawal_data']['amount'], 2); ?> TRX</div>
                        <div class="detail-address">To: <?php echo substr($_SESSION['withdrawal_data']['to_address'], 0, 10) . '...'; ?></div>
                    </div>

                    <form method="POST">
                        <div class="form-group">
                            <label for="otp" class="form-label">Enter OTP</label>
                            <input type="text" id="otp" name="otp" maxlength="6" class="form-input otp-input" placeholder="000000" required>
                        </div>
                        
                        <button type="submit" name="verify_otp" class="submit-btn">
                            Verify & Withdraw
                        </button>
                    </form>

                    <div class="resend-link">
                        <button type="button" onclick="window.location.reload()" class="resend-btn">
                            Didn't receive OTP? Resend
                        </button>
                    </div>
                    
                    <div class="otp-timer">
                        <p>Code expires in <span id="timer">05:00</span></p>
                    </div>
                </div>
            <?php else: ?>
                <!-- Step 3: Processing Transaction -->
                <div class="processing-section">
                    <div id="transaction-pending">
                        <div class="processing-icon">
                            <div class="spinner"></div>
                        </div>
                        <h2 class="processing-title">Processing Transaction</h2>
                        <p class="processing-text" id="status-text">Initializing TRON API...</p>
                        
                        <div class="progress-steps">
                            <div class="progress-step" id="step-create">Create</div>
                            <div class="progress-step" id="step-sign">Sign</div>
                            <div class="progress-step" id="step-broadcast">Broadcast</div>
                            <div class="progress-step" id="step-confirm">Confirm</div>
                        </div>
                        
                        <div class="transaction-details">
                            <div class="detail-row">
                                <span class="detail-label">Amount:</span>
                                <span class="detail-value"><?php echo number_format($_SESSION['withdrawal_data']['amount'], 4); ?> TRX</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Fee:</span>
                                <span class="detail-value"><?php echo number_format($withdrawFee, 4); ?> TRX</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">To:</span>
                                <span class="detail-value address"><?php echo substr($_SESSION['withdrawal_data']['to_address'], 0, 10) . '...' . substr($_SESSION['withdrawal_data']['to_address'], -10); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div id="transaction-success" style="display: none;">
                        <div class="processing-icon success">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                        </div>
                        <h2 class="processing-title">Transaction Successful</h2>
                        <p class="processing-text">Your withdrawal has been processed successfully using TRON API.</p>
                        <div class="transaction-details">
                            <div class="detail-row">
                                <span class="detail-label">Amount:</span>
                                <span class="detail-value"><?php echo number_format($_SESSION['withdrawal_data']['amount'], 4); ?> TRX</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Fee:</span>
                                <span class="detail-value"><?php echo number_format($withdrawFee, 4); ?> TRX</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">To:</span>
                                <span class="detail-value address"><?php echo substr($_SESSION['withdrawal_data']['to_address'], 0, 10) . '...' . substr($_SESSION['withdrawal_data']['to_address'], -10); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Transaction ID:</span>
                                <span class="detail-value tx-hash" id="tx-hash">Processing...</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Method:</span>
                                <span class="detail-value">TRON API</span>
                            </div>
                        </div>
                        <a href="dashboard.php" class="btn">Back to Dashboard</a>
                    </div>
                    
                    <div id="transaction-error" style="display: none;">
                        <div class="processing-icon error">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="15" y1="9" x2="9" y2="15"/>
                                <line x1="9" y1="9" x2="15" y2="15"/>
                            </svg>
                        </div>
                        <h2 class="processing-title">Transaction Failed</h2>
                        <p class="processing-text" id="error-message">An error occurred while processing your withdrawal.</p>
                        <button class="try-again-btn" onclick="retryTransaction()">Try Again</button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Important Notes -->
            <div class="notes-section">
                <div class="notes-header">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <div>
                        <div class="notes-title">Important Notes:</div>
                        <ul class="notes-list">
                            <li>• Minimum withdrawal: 5 TRX</li>
                            <li>• Network fee: <?php echo $withdrawFee; ?> TRX (fixed)</li>
                            <li>• Processing time: 5-30 minutes</li>
                            <li>• Email verification required for security</li>
                            <li>• Double-check the destination address</li>
                            <li>• Using TRON API for reliable transactions</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Navigation -->
        <?php include '../includes/bottomnav.php'; ?>
    </div>

    <!-- Success Notification -->
    <div id="notification" class="notification">
        <i class="ri-check-line mr-2"></i>
        Transaction Successful!
    </div>

    <script>
        const withdrawFee = <?php echo $withdrawFee; ?>;
        let transactionAttempts = 0;
        
        function updateAmounts() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const fee = <?php echo $withdrawFee; ?>;
            const total = amount + fee;
            
            document.getElementById('withdrawAmount').textContent = amount.toFixed(2) + ' TRX';
            document.getElementById('totalAmount').textContent = total.toFixed(2) + ' TRX';
        }
        
        function updateProgressStep(stepId, status) {
            const step = document.getElementById(stepId);
            if (step) {
                step.classList.remove('active', 'completed');
                if (status === 'active') {
                    step.classList.add('active');
                } else if (status === 'completed') {
                    step.classList.add('completed');
                }
            }
        }
        
        function showNotification() {
            const notification = document.getElementById('notification');
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const amountInput = document.getElementById('amount');
            if (amountInput) {
                amountInput.addEventListener('input', updateAmounts);
            }
            
            // Paste button functionality
            const pasteBtn = document.getElementById('pasteBtn');
            if (pasteBtn) {
                pasteBtn.addEventListener('click', async function() {
                    try {
                        const text = await navigator.clipboard.readText();
                        document.getElementById('to_address').value = text.trim();
                    } catch (err) {
                        console.error('Failed to read clipboard: ', err);
                        alert('Unable to paste. Please check your browser permissions.');
                    }
                });
            }
            
            // Max button functionality
            const maxBtn = document.getElementById('maxBtn');
            if (maxBtn) {
                maxBtn.addEventListener('click', function() {
                    const maxAmount = <?php echo $wallet['balance'] - $withdrawFee; ?>;
                    document.getElementById('amount').value = maxAmount.toFixed(4);
                    updateAmounts();
                });
            }
            
            // Auto-focus OTP input and format
            <?php if ($step === 2): ?>
            const otpInput = document.getElementById('otp');
            if (otpInput) {
                otpInput.focus();
                otpInput.addEventListener('input', function(e) {
                    e.target.value = e.target.value.replace(/[^0-9]/g, '');
                });
            }
            
            // OTP timer
            const timerElement = document.getElementById('timer');
            if (timerElement) {
                let timeLeft = 5 * 60; // 5 minutes in seconds
                
                const countdownTimer = setInterval(function() {
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    
                    timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    
                    if (timeLeft <= 0) {
                        clearInterval(countdownTimer);
                        timerElement.textContent = "00:00";
                        timerElement.parentElement.innerHTML += '<p class="expired">Code expired. <a href="withdraw.php">Request a new code</a></p>';
                    }
                    
                    timeLeft--;
                }, 1000);
            }
            <?php endif; ?>
            
            <?php if ($step === 3): ?>
            // Process the withdrawal using server-side TRON API
            async function processTransaction() {
                const statusText = document.getElementById('status-text');
                transactionAttempts++;
                
                try {
                    statusText.textContent = 'Creating transaction...';
                    updateProgressStep('step-create', 'active');
                    
                    const formData = new FormData();
                    formData.append('action', 'process_withdrawal');
                    
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    console.log('Transaction result:', result);
                    
                    if (result.success) {
                        updateProgressStep('step-create', 'completed');
                        statusText.textContent = 'Signing transaction...';
                        updateProgressStep('step-sign', 'active');
                        
                        setTimeout(() => {
                            updateProgressStep('step-sign', 'completed');
                            statusText.textContent = 'Broadcasting transaction...';
                            updateProgressStep('step-broadcast', 'active');
                            
                            setTimeout(() => {
                                updateProgressStep('step-broadcast', 'completed');
                                statusText.textContent = 'Confirming transaction...';
                                updateProgressStep('step-confirm', 'active');
                                
                                setTimeout(() => {
                                    updateProgressStep('step-confirm', 'completed');
                                    statusText.textContent = 'Transaction completed successfully!';
                                    
                                    // Show success UI
                                    setTimeout(() => {
                                        document.getElementById('transaction-pending').style.display = 'none';
                                        document.getElementById('transaction-success').style.display = 'block';
                                        document.getElementById('tx-hash').textContent = result.txHash;
                                        
                                        // Show notification
                                        showNotification();
                                    }, 1000);
                                }, 1000);
                            }, 1000);
                        }, 1000);
                        
                    } else {
                        throw new Error(result.error || 'Transaction failed');
                    }
                    
                } catch (error) {
                    console.error('Withdrawal error:', error);
                    
                    // Show error UI
                    document.getElementById('transaction-pending').style.display = 'none';
                    document.getElementById('transaction-error').style.display = 'block';
                    document.getElementById('error-message').textContent = 'Transaction failed: ' + error.message;
                }
            }
            
            async function retryTransaction() {
                if (transactionAttempts < 3) {
                    document.getElementById('transaction-error').style.display = 'none';
                    document.getElementById('transaction-pending').style.display = 'block';
                    document.getElementById('status-text').textContent = 'Retrying transaction...';
                    
                    // Reset progress steps
                    ['step-create', 'step-sign', 'step-broadcast', 'step-confirm'].forEach(stepId => {
                        updateProgressStep(stepId, '');
                    });
                    
                    processTransaction();
                } else {
                    document.getElementById('error-message').textContent = 'Maximum retry attempts reached. Please try again later.';
                }
            }
            
            // Make retryTransaction available globally
            window.retryTransaction = retryTransaction;
            
            // Start the withdrawal process
            processTransaction();
            <?php endif; ?>
        });
    </script>
</body>
</html>
