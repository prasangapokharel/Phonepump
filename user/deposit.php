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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit</title>
    <link rel="stylesheet" href="../assets/css/deposit.css">
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
                <h1 class="page-title">Deposit</h1>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Deposit Header -->
            <div class="deposit-header">
                <div class="deposit-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14"/>
                        <path d="m19 12-7 7-7-7"/>
                    </svg>
                </div>
                <h2 class="deposit-title">Deposit TRX</h2>
                <p class="deposit-subtitle">Send TRX to your wallet address</p>
            </div>

            <!-- Address Section -->
            <div class="address-section">
                <div class="address-label">Your Wallet Address</div>
                <div class="address-container">
                    <div class="address-text" id="walletAddress">
                        <?php echo htmlspecialchars($wallet['address']); ?>
                    </div>
                    <button onclick="copyAddress()" class="copy-btn" id="copyBtn">
                        Copy Address
                    </button>
                </div>
            </div>

            <!-- QR Code Section -->
            <div class="qr-section">
                <div class="qr-container">
                    <div class="qr-code" id="qrcode"></div>
                    <div class="qr-label">Scan QR code to deposit</div>
                </div>
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
                            <li>• Only send TRX to this address</li>
                            <li>• Minimum deposit: 1 TRX</li>
                            <li>• Deposits are usually confirmed within 3-5 minutes</li>
                            <li>• Do not send other cryptocurrencies to this address</li>
                            <li>• Network confirmations required: 19 blocks</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Navigation -->
        <div class="bottom-nav">
            <a href="dashboard.php" class="nav-btn">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 7V4a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v3"/>
                    <rect x="2" y="7" width="20" height="13" rx="2"/>
                    <circle cx="17" cy="13.5" r="1.5"/>
                </svg>
                Wallet
            </a>
            <a href="market.php" class="nav-btn">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 3v18h18"/>
                    <path d="m19 9-5 5-4-4-3 3"/>
                </svg>
                Market
            </a>
            <a href="earn.php" class="nav-btn active">
                <svg class="tron-icon" viewBox="0 0 482 507.15" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M475.44,152.78C451.67,130.83,418.79,97.31,392,73.54l-1.58-1.11a30.33,30.33,0,0,0-8.8-4.91h0C317,55.48,16.48-.71,10.62,0A11.07,11.07,0,0,0,6,1.75L4.52,2.94A17.51,17.51,0,0,0,.4,9.59l-.4,1v6.5C33.84,111.34,167.44,420,193.74,492.41c1.59,4.91,4.6,14.26,10.23,14.74h1.26c3,0,15.85-17,15.85-17S450.56,211.9,473.78,182.26a74.25,74.25,0,0,0,7.92-11.73A19.1,19.1,0,0,0,475.44,152.78ZM280,185.19,377.9,104l57.45,52.93Zm-38-5.31L73.3,41.69,346.12,92Zm15.22,36.22,172.58-27.82L232.41,426ZM50.4,55.48,227.82,206,202.14,426.16Z"/>
                </svg>
                Earn
            </a>
            <a href="profile.php" class="nav-btn">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="m12 1 3 6 6 3-6 3-3 6-3-6-6-3 6-3 3-6z"/>
                </svg>
                Profile
            </a>
        </div>
    </div>

    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    
    <script>
        // Generate QR code
        document.addEventListener('DOMContentLoaded', function() {
            const walletAddress = '<?php echo $wallet['address']; ?>';
            const qrOptions = {
                width: 160,
                margin: 1,
                color: {
                    dark: '#FFFFFF',
                    light: '#000000'
                }
            };
            
            QRCode.toCanvas(document.getElementById('qrcode'), walletAddress, qrOptions);
        });
        
        // Copy address function
        function copyAddress() {
            const address = document.getElementById('walletAddress').textContent.trim();
            const copyBtn = document.getElementById('copyBtn');
            
            navigator.clipboard.writeText(address).then(() => {
                // Success feedback
                copyBtn.textContent = 'Copied!';
                copyBtn.classList.add('copy-success');
                
                setTimeout(() => {
                    copyBtn.textContent = 'Copy Address';
                    copyBtn.classList.remove('copy-success');
                }, 2000);
            }).catch(() => {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = address;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                // Success feedback
                copyBtn.textContent = 'Copied!';
                copyBtn.classList.add('copy-success');
                
                setTimeout(() => {
                    copyBtn.textContent = 'Copy Address';
                    copyBtn.classList.remove('copy-success');
                }, 2000);
            });
        }
    </script>
</body>
</html>