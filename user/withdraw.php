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

// Get user's wallet and balance
$stmt = $pdo->prepare("SELECT tb.*, u.email FROM trxbalance tb JOIN users2 u ON tb.user_id = u.id WHERE tb.user_id = ?");
$stmt->execute([$user_id]);
$wallet = $stmt->fetch();

if (!$wallet) {
    header("Location: dashboard.php");
    exit;
}

// Company settings
$companyPrivateKey = 'ff6ffde367245699b58713f4ce44885521da6aff84903889cf61c730e887b777'; // Your company private key
$companyAddress = 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv'; // Your company address
$withdrawalFee = 1.5; // TRX

// TronGrid API Key
$tronGridApiKey = '3022fab4-cd87-48c5-b5d1-65fb3e588f67';

$error = "";
$success = "";
$processing = false;

/**
 * Validate TRON address format
 */
function isValidTronAddress($address) {
    if (!is_string($address) || strlen($address) !== 34 || $address[0] !== 'T') {
        return false;
    }
    
    $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    for ($i = 0; $i < strlen($address); $i++) {
        if (strpos($alphabet, $address[$i]) === false) {
            return false;
        }
    }
    
    return true;
}

// Get TRX price
function getTRXPrice() {
    try {
        $api_url = 'https://api.api-ninjas.com/v1/cryptoprice?symbol=TRXUSDT';
        $api_key = 'jRN/iU++CJrVw0zkBf9tBg==ekPzRifWfQ8jCTFe';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $api_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'X-Api-Key: ' . $api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($curl);
        curl_close($curl);

        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['price'])) {
                return floatval($data['price']);
            }
        }
    } catch (Exception $e) {
        // Fallback price
    }
    
    return 0.20; // Fallback price
}

$trx_price = getTRXPrice();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw - TRX Wallet</title>
    <script src="https://cdn.jsdelivr.net/npm/tronweb@4.4.0/dist/TronWeb.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #000;
            color: #fff;
            line-height: 1.4;
            overflow-x: hidden;
        }

        .app {
            min-height: 100vh;
            background: #000;
            padding-bottom: 100px;
        }

        /* Header */
        .header {
            background: #111;
            padding: 16px 20px;
            border-bottom: 1px solid #333;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            align-items: center;
            gap: 16px;
            max-width: 600px;
            margin: 0 auto;
        }

        .back-btn {
            color: #fff;
            text-decoration: none;
            padding: 8px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .page-title {
            font-size: 20px;
            font-weight: 600;
            color: #fff;
        }

        /* Content */
        .content {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Balance Section */
        .balance-section {
            background: #111;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            text-align: center;
            border: 1px solid #333;
        }

        .balance-label {
            color: #999;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .balance-amount {
            font-size: 32px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 4px;
        }

        .balance-usd {
            color: #999;
            font-size: 16px;
        }

        /* Form Section */
        .form-section {
            background: #111;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #333;
        }

        .form-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #fff;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-label {
            display: block;
            color: #999;
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            background: #000;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 16px;
            color: #fff;
            font-size: 16px;
        }

        .form-input:focus {
            outline: none;
            border-color: #666;
        }

        .form-input.valid {
            border-color: #00ff88;
        }

        .form-input.invalid {
            border-color: #ff4444;
        }

        .max-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: #333;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
        }

        .address-validation {
            font-size: 12px;
            margin-top: 4px;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .address-validation.valid {
            color: #00ff88;
            background: rgba(0, 255, 136, 0.1);
        }

        .address-validation.invalid {
            color: #ff4444;
            background: rgba(255, 68, 68, 0.1);
        }

        /* Fee Breakdown */
        .fee-breakdown {
            background: #000;
            border-radius: 12px;
            padding: 16px;
            margin: 20px 0;
            border: 1px solid #333;
        }

        .fee-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .fee-row:last-child {
            margin-bottom: 0;
            padding-top: 8px;
            border-top: 1px solid #333;
            font-weight: 600;
        }

        .fee-label {
            color: #999;
        }

        .fee-amount {
            color: #fff;
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            background: #333;
            color: #fff;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Alerts */
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background: rgba(255, 68, 68, 0.1);
            color: #ff4444;
            border: 1px solid rgba(255, 68, 68, 0.3);
        }

        .alert-success {
            background: rgba(0, 255, 136, 0.1);
            color: #00ff88;
            border: 1px solid rgba(0, 255, 136, 0.3);
        }

        /* Processing */
        .processing {
            text-align: center;
            padding: 20px;
        }

        .spinner {
            border: 3px solid #333;
            border-top: 3px solid #fff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Notes Section */
        .notes-section {
            background: #111;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #333;
        }

        .notes-header {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .notes-title {
            font-weight: 600;
            margin-bottom: 12px;
            color: #fff;
        }

        .notes-list {
            list-style: none;
            color: #999;
            font-size: 14px;
            line-height: 1.6;
        }

        .notes-list li {
            margin-bottom: 8px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #111;
            border-radius: 16px;
            padding: 24px;
            width: 90%;
            max-width: 500px;
            border: 1px solid #333;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
        }

        .close-modal {
            background: none;
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: #333;
            color: #fff;
        }

        .btn-secondary {
            background: #222;
            color: #fff;
        }

        /* Progress Steps */
        .progress-steps {
            display: none;
            background: #000;
            border-radius: 12px;
            padding: 16px;
            margin: 20px 0;
            border: 1px solid #333;
        }

        .step {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .step:last-child {
            margin-bottom: 0;
        }

        .step-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .step-pending {
            background: #333;
            color: #999;
        }

        .step-processing {
            background: #ffc107;
            color: #000;
        }

        .step-completed {
            background: #00ff88;
            color: #000;
        }

        .step-failed {
            background: #ff4444;
            color: #fff;
        }

        /* Debug link */
        .debug-link {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: #333;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .content {
                padding: 16px;
            }
            
            .balance-amount {
                font-size: 28px;
            }
            
            .form-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
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

        <!-- Content -->
        <div class="content">
            <!-- Balance Section -->
            <div class="balance-section">
                <div class="balance-label">Available Balance</div>
                <div class="balance-amount" id="currentBalance">
                    <?php echo number_format($wallet['balance'], 6); ?> TRX
                </div>
                <div class="balance-usd">
                    $<?php echo number_format($wallet['balance'] * $trx_price, 2); ?>
                </div>
            </div>

            <!-- Form Section -->
            <div class="form-section">
                <h2 class="form-title">Withdraw TRX</h2>

                <!-- Progress Steps -->
                <div id="progressSteps" class="progress-steps">
                    <div class="step" id="step1">
                        <div class="step-icon step-pending">1</div>
                        <span>Checking balance and deducting amount</span>
                    </div>
                    <div class="step" id="step2">
                        <div class="step-icon step-pending">2</div>
                        <span>Creating blockchain transaction</span>
                    </div>
                    <div class="step" id="step3">
                        <div class="step-icon step-pending">3</div>
                        <span>Broadcasting to TRON network</span>
                    </div>
                </div>

                <!-- Alerts -->
                <div id="alertContainer"></div>

                <form id="withdrawForm">
                    <div class="form-group">
                        <label for="to_address" class="form-label">Destination Address</label>
                        <input type="text" id="to_address" name="to_address" class="form-input" placeholder="Enter TRON address (T...)" required>
                        <div id="address-validation" class="address-validation" style="display: none;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount" class="form-label">Amount (TRX)</label>
                        <input type="number" id="amount" name="amount" step="0.000001" min="5" max="<?php echo $wallet['balance'] - $withdrawalFee; ?>" class="form-input" placeholder="Minimum 5 TRX" required>
                        <button type="button" id="maxBtn" class="max-btn">MAX</button>
                    </div>
                    
                    <div class="fee-breakdown">
                        <div class="fee-row">
                            <span class="fee-label">Withdrawal Amount:</span>
                            <span class="fee-amount" id="withdrawAmount">0.000000 TRX</span>
                        </div>
                        <div class="fee-row">
                            <span class="fee-label">Network Fee:</span>
                            <span class="fee-amount"><?php echo number_format($withdrawalFee, 6); ?> TRX</span>
                        </div>
                        <div class="fee-row">
                            <span class="fee-label">Total Deducted:</span>
                            <span class="fee-amount" id="totalAmount"><?php echo number_format($withdrawalFee, 6); ?> TRX</span>
                        </div>
                    </div>
                    
                    <button type="button" id="submitBtn" class="submit-btn" disabled onclick="showConfirmModal()">
                        Withdraw TRX
                    </button>
                </form>
            </div>

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
                            <li>• Network fee: <?php echo $withdrawalFee; ?> TRX (fixed)</li>
                            <li>• Processing time: 1-5 minutes</li>
                            <li>• Funds sent from company wallet</li>
                            <li>• Double-check the destination address</li>
                            <li>• Transactions are irreversible</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div id="confirmModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Confirm Withdrawal</h3>
                    <button class="close-modal" onclick="hideConfirmModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Please confirm your withdrawal details:</p>
                    <div class="fee-breakdown" style="margin-top: 15px;">
                        <div class="fee-row">
                            <span class="fee-label">To Address:</span>
                            <span class="fee-amount" id="confirmAddress" style="font-size: 12px; word-break: break-all;"></span>
                        </div>
                        <div class="fee-row">
                            <span class="fee-label">Amount:</span>
                            <span class="fee-amount" id="confirmAmount"></span>
                        </div>
                        <div class="fee-row">
                            <span class="fee-label">Fee:</span>
                            <span class="fee-amount"><?php echo number_format($withdrawalFee, 6); ?> TRX</span>
                        </div>
                        <div class="fee-row">
                            <span class="fee-label">Total:</span>
                            <span class="fee-amount" id="confirmTotal"></span>
                        </div>
                    </div>
                    <div style="margin-top: 15px; background: rgba(255, 193, 7, 0.1); padding: 10px; border-radius: 8px; border: 1px solid rgba(255, 193, 7, 0.3);">
                        <p style="color: #ffc107; font-size: 14px;">⚠️ Your balance will be deducted immediately. If the blockchain transaction fails, you will be refunded automatically.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="hideConfirmModal()">Cancel</button>
                    <button class="btn btn-primary" onclick="processWithdrawal()">Confirm Withdrawal</button>
                </div>
            </div>
        </div>

        <!-- Debug Link -->
        <a href="check_tables.php" class="debug-link">Check DB</a>

        <!-- Bottom Navigation -->
        <?php include '../includes/bottomnav.php'; ?>
    </div>

    <script>
        // Constants
        const withdrawalFee = <?php echo $withdrawalFee; ?>;
        const companyAddress = '<?php echo $companyAddress; ?>';
        const companyPrivateKey = '<?php echo $companyPrivateKey; ?>';
        const userId = <?php echo $user_id; ?>;
        let userBalance = <?php echo $wallet['balance']; ?>;
        let addressValid = false;
        let tronWeb;
        let withdrawalId = null;
        
        // Initialize TronWeb
        async function initTronWeb() {
            try {
                tronWeb = new TronWeb({
                    fullHost: 'https://api.trongrid.io',
                    headers: { "TRON-PRO-API-KEY": '<?php echo $tronGridApiKey; ?>' },
                    privateKey: companyPrivateKey
                });
                
                console.log('TronWeb initialized successfully');
                
                // Verify company wallet
                const companyBalance = await tronWeb.trx.getBalance(companyAddress);
                console.log('Company wallet balance:', tronWeb.fromSun(companyBalance), 'TRX');
                
            } catch (error) {
                console.error('Failed to initialize TronWeb:', error);
                showAlert('Failed to initialize wallet connection', 'error');
            }
        }
        
        // Set up event listeners
        document.addEventListener('DOMContentLoaded', function() {
            initTronWeb();
            
            const addressInput = document.getElementById('to_address');
            const amountInput = document.getElementById('amount');
            const maxBtn = document.getElementById('maxBtn');
            
            if (addressInput) {
                addressInput.addEventListener('input', validateAddress);
            }
            
            if (amountInput) {
                amountInput.addEventListener('input', updateAmounts);
            }
            
            if (maxBtn) {
                maxBtn.addEventListener('click', setMaxAmount);
            }
        });
        
        // Show alert
        function showAlert(message, type = 'error') {
            const alertContainer = document.getElementById('alertContainer');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            
            alertContainer.innerHTML = `
                <div class="alert ${alertClass}">
                    ${message}
                </div>
            `;
            
            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    alertContainer.innerHTML = '';
                }, 5000);
            }
        }
        
        // Update step status
        function updateStep(stepNumber, status) {
            const step = document.getElementById(`step${stepNumber}`);
            const icon = step.querySelector('.step-icon');
            
            // Remove all status classes
            icon.classList.remove('step-pending', 'step-processing', 'step-completed', 'step-failed');
            
            // Add new status
            icon.classList.add(`step-${status}`);
            
            // Update icon content
            if (status === 'processing') {
                icon.innerHTML = '⏳';
            } else if (status === 'completed') {
                icon.innerHTML = '✓';
            } else if (status === 'failed') {
                icon.innerHTML = '✗';
            } else {
                icon.innerHTML = stepNumber;
            }
        }
        
        // Validate TRON address
        async function validateAddress() {
            const addressInput = document.getElementById('to_address');
            const validationDiv = document.getElementById('address-validation');
            const address = addressInput.value.trim();
            
            if (address.length === 0) {
                validationDiv.style.display = 'none';
                addressInput.classList.remove('valid', 'invalid');
                addressValid = false;
                updateSubmitButton();
                return;
            }
            
            try {
                // Check if address is valid using TronWeb
                const isValid = tronWeb && tronWeb.isAddress(address);
                
                if (isValid) {
                    validationDiv.textContent = '✓ Valid TRON address';
                    validationDiv.className = 'address-validation valid';
                    validationDiv.style.display = 'block';
                    addressInput.classList.remove('invalid');
                    addressInput.classList.add('valid');
                    addressValid = true;
                } else {
                    validationDiv.textContent = '✗ Invalid TRON address';
                    validationDiv.className = 'address-validation invalid';
                    validationDiv.style.display = 'block';
                    addressInput.classList.remove('valid');
                    addressInput.classList.add('invalid');
                    addressValid = false;
                }
            } catch (error) {
                console.error("Address validation error:", error);
                validationDiv.textContent = '✗ Address validation error';
                validationDiv.className = 'address-validation invalid';
                validationDiv.style.display = 'block';
                addressInput.classList.remove('valid');
                addressInput.classList.add('invalid');
                addressValid = false;
            }
            
            updateSubmitButton();
        }
        
        // Update amount displays
        function updateAmounts() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const total = amount + withdrawalFee;
            
            document.getElementById('withdrawAmount').textContent = amount.toFixed(6) + ' TRX';
            document.getElementById('totalAmount').textContent = total.toFixed(6) + ' TRX';
            
            updateSubmitButton();
        }
        
        // Set maximum amount
        function setMaxAmount() {
            const maxAmount = userBalance - withdrawalFee;
            document.getElementById('amount').value = maxAmount.toFixed(6);
            updateAmounts();
        }
        
        // Update submit button state
        function updateSubmitButton() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const submitBtn = document.getElementById('submitBtn');
            
            if (addressValid && amount >= 5 && amount <= (userBalance - withdrawalFee)) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }
        
        // Show confirmation modal
        function showConfirmModal() {
            const toAddress = document.getElementById('to_address').value;
            const amount = parseFloat(document.getElementById('amount').value);
            const total = amount + withdrawalFee;
            
            document.getElementById('confirmAddress').textContent = toAddress;
            document.getElementById('confirmAmount').textContent = amount.toFixed(6) + ' TRX';
            document.getElementById('confirmTotal').textContent = total.toFixed(6) + ' TRX';
            
            document.getElementById('confirmModal').style.display = 'flex';
        }
        
        // Hide confirmation modal
        function hideConfirmModal() {
            document.getElementById('confirmModal').style.display = 'none';
        }
        
        // Process withdrawal with simplified flow
        async function processWithdrawal() {
            hideConfirmModal();
            
            const toAddress = document.getElementById('to_address').value;
            const amount = parseFloat(document.getElementById('amount').value);
            const totalDeduction = amount + withdrawalFee;
            
            // Show progress steps
            document.getElementById('progressSteps').style.display = 'block';
            document.getElementById('withdrawForm').style.display = 'none';
            
            try {
                // Step 1: Check balance and deduct amount immediately
                updateStep(1, 'processing');
                
                const deductResult = await deductBalance(toAddress, amount, totalDeduction);
                
                if (!deductResult.success) {
                    throw new Error('Failed to deduct balance: ' + deductResult.error);
                }
                
                withdrawalId = deductResult.withdrawal_id;
                userBalance = deductResult.new_balance;
                
                // Update balance display
                document.getElementById('currentBalance').textContent = userBalance.toFixed(6) + ' TRX';
                
                updateStep(1, 'completed');
                
                // Step 2: Create blockchain transaction
                updateStep(2, 'processing');
                
                if (!tronWeb) {
                    throw new Error('TronWeb not initialized');
                }
                
                // Check company wallet balance
                const companyBalance = await tronWeb.trx.getBalance(companyAddress);
                const companyBalanceTRX = tronWeb.fromSun(companyBalance);
                
                if (companyBalanceTRX < amount) {
                    throw new Error(`Insufficient funds in company wallet. Available: ${companyBalanceTRX} TRX`);
                }
                
                const amountInSun = tronWeb.toSun(amount);
                const transaction = await tronWeb.trx.sendTransaction(toAddress, amountInSun);
                
                if (!transaction || !transaction.txid) {
                    throw new Error('Failed to create transaction');
                }
                
                updateStep(2, 'completed');
                
                // Step 3: Transaction is automatically broadcast by TronWeb
                updateStep(3, 'processing');
                
                // Wait a moment for network confirmation
                await new Promise(resolve => setTimeout(resolve, 2000));
                
                updateStep(3, 'completed');
                
                // Try to finalize withdrawal, but don't fail if it doesn't work
                try {
                    await finalizeWithdrawal(withdrawalId, transaction.txid);
                } catch (finalizeError) {
                    console.warn('Finalize withdrawal failed, but transaction was successful:', finalizeError);
                    // Don't throw error - the withdrawal was successful
                }
                
                // Show success message
                showAlert(`✅ Withdrawal successful! Your TRX has been sent to ${toAddress}. Transaction ID: ${transaction.txid}`, 'success');
                
                // Refresh the page after 5 seconds
                setTimeout(() => {
                    window.location.reload();
                }, 5000);
                
            } catch (error) {
                console.error('Withdrawal error:', error);
                
                // Mark current step as failed
                for (let i = 1; i <= 3; i++) {
                    const stepIcon = document.getElementById(`step${i}`).querySelector('.step-icon');
                    if (stepIcon.classList.contains('step-processing')) {
                        updateStep(i, 'failed');
                        break;
                    }
                }
                
                // If we have a withdrawal ID, attempt to refund
                if (withdrawalId) {
                    try {
                        const refundResult = await refundWithdrawal(withdrawalId);
                        if (refundResult.success) {
                            userBalance = refundResult.new_balance;
                            document.getElementById('currentBalance').textContent = userBalance.toFixed(6) + ' TRX';
                            showAlert('❌ Withdrawal failed: ' + error.message + '. Your balance has been refunded.', 'error');
                        } else {
                            showAlert('❌ Withdrawal failed: ' + error.message + '. Please contact support for refund.', 'error');
                        }
                    } catch (refundError) {
                        showAlert('❌ Withdrawal failed: ' + error.message + '. Refund failed. Please contact support.', 'error');
                    }
                } else {
                    showAlert('❌ Withdrawal failed: ' + error.message, 'error');
                }
                
                // Show form again after 3 seconds
                setTimeout(() => {
                    document.getElementById('progressSteps').style.display = 'none';
                    document.getElementById('withdrawForm').style.display = 'block';
                    
                    // Reset all steps
                    for (let i = 1; i <= 3; i++) {
                        updateStep(i, 'pending');
                    }
                }, 3000);
            }
        }
        
        // Deduct balance first
        async function deductBalance(toAddress, amount, totalDeduction) {
            try {
                console.log('Deducting balance:', {
                    user_id: userId,
                    to_address: toAddress,
                    amount: amount,
                    total_deduction: totalDeduction,
                    from_address: companyAddress
                });
                
                const response = await fetch('deduct_balance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        to_address: toAddress,
                        amount: amount,
                        total_deduction: totalDeduction,
                        from_address: companyAddress
                    })
                });
                
                const responseText = await response.text();
                console.log('Deduct balance response:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    throw new Error('Server returned invalid JSON: ' + responseText.substring(0, 100));
                }
                
                return result;
                
            } catch (error) {
                console.error('Deduct balance error:', error);
                return { success: false, error: error.message };
            }
        }
        
        // Finalize withdrawal with transaction hash (optional - don't fail if this fails)
        async function finalizeWithdrawal(withdrawalId, txHash) {
            try {
                const response = await fetch('finalize_withdrawal.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        withdrawal_id: withdrawalId,
                        tx_hash: txHash
                    })
                });
                
                const responseText = await response.text();
                console.log('Finalize withdrawal response:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.warn('Finalize withdrawal returned invalid JSON:', responseText.substring(0, 100));
                    return { success: false, error: 'Invalid JSON response' };
                }
                
                return result;
                
            } catch (error) {
                console.error('Finalize withdrawal error:', error);
                return { success: false, error: error.message };
            }
        }
        
        // Refund withdrawal if blockchain transaction fails
        async function refundWithdrawal(withdrawalId) {
            try {
                const response = await fetch('refund_withdrawal.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        withdrawal_id: withdrawalId
                    })
                });
                
                const responseText = await response.text();
                console.log('Refund withdrawal response:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    throw new Error('Server returned invalid JSON: ' + responseText.substring(0, 100));
                }
                
                return result;
                
            } catch (error) {
                console.error('Refund withdrawal error:', error);
                return { success: false, error: error.message };
            }
        }
    </script>
</body>
</html>