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

// Get user's wallet
$stmt = $pdo->prepare("SELECT * FROM trxbalance WHERE user_id = ?");
$stmt->execute([$user_id]);
$wallet = $stmt->fetch();

if (!$wallet) {
    header("Location: dashboard.php");
    exit;
}

// Format wallet address for display (show first 4 and last 4 characters)
$displayAddress = substr($wallet['address'], 0, 4) . "..." . substr($wallet['address'], -4);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receive TRX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/helvetica-neue-cdn@2.0.0/css/helvetica-neue.min.css">
    <style>
        body {
            background-color: #000000;
            color: #ffffff;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }
        .status-bar {
            font-size: 12px;
            font-weight: 500;
        }
        .qr-container {
            background-color: #0D0D0D;
            border-radius: 16px;
        }
        .qr-code {
            background-color: #ffffff;
            padding: 16px;
            border-radius: 8px;
        }
        .button {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 9999px;
            padding: 12px 0;
            font-weight: 500;
        }
        .home-indicator {
            background-color: #ffffff;
            height: 5px;
            width: 134px;
            border-radius: 100px;
            margin: 8px auto;
        }
    </style>
</head>
<body class="h-screen flex flex-col">
    <!-- Status Bar -->
    <div class="status-bar flex justify-between items-center p-2">
        <div>00:41 ⟩</div>
        <div class="flex items-center space-x-1">
            <div class="signal">•••</div>
            <div class="wifi">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 6C16.4183 6 20 9.58172 20 14M12 10C14.2091 10 16 11.7909 16 14" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <path d="M12 14H12.01" stroke="white" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="battery">
                <svg width="20" height="12" viewBox="0 0 20 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="0.5" y="0.5" width="17" height="11" rx="2.5" stroke="white"/>
                    <rect x="2" y="2" width="14" height="8" rx="1" fill="white"/>
                    <rect x="18" y="3" width="2" height="6" rx="1" fill="white"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="flex justify-between items-center px-4 py-3">
        <button onclick="window.location.href='dashboard.php'">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M6 18L18 6M6 6L18 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <h1 class="text-lg font-medium">Receive</h1>
        <button>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="10" stroke="white" stroke-width="2"/>
                <path d="M12 8V12M12 16H12.01" stroke="white" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col items-center justify-center px-6">
        <div class="qr-container w-full max-w-xs p-6 flex flex-col items-center">
            <div class="qr-code mb-4">
                <div id="qrcode" class="w-[180px] h-[180px]"></div>
            </div>
            <div class="text-base font-medium">Main Wallet</div>
            <div class="text-gray-400 text-sm"><?php echo $displayAddress; ?></div>
            <div class="mt-2">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="white" stroke-width="2"/>
                    <path d="M15 9L9 15M9 9L15 15" stroke="white" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
        
        <div class="text-gray-400 text-sm mt-8 mb-4">
            This address is for receiving TRON assets only.
        </div>
    </div>

    <!-- Bottom Buttons -->
    <div class="px-6 pb-8 grid grid-cols-2 gap-4">
        <button id="copyBtn" class="button flex items-center justify-center">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="mr-2">
                <rect x="9" y="9" width="13" height="13" rx="2" stroke="white" stroke-width="2"/>
                <path d="M5 15H4C2.89543 15 2 14.1046 2 13V4C2 2.89543 2.89543 2 4 2H13C14.1046 2 15 2.89543 15 4V5" stroke="white" stroke-width="2"/>
            </svg>
            Copy
        </button>
        <button id="shareBtn" class="button flex items-center justify-center">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="mr-2">
                <path d="M8 12H16M16 12L12 8M16 12L12 16" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="white" stroke-width="2"/>
            </svg>
            Share
        </button>
    </div>

    <!-- Home Indicator -->
    <div class="home-indicator"></div>

    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Generate QR code
            const walletAddress = '<?php echo $wallet['address']; ?>';
            const qrOptions = {
                width: 180,
                margin: 0,
                color: {
                    dark: '#000000',
                    light: '#FFFFFF'
                }
            };
            
            QRCode.toCanvas(document.getElementById('qrcode'), walletAddress, qrOptions);
            
            // Copy address function
            document.getElementById('copyBtn').addEventListener('click', function() {
                navigator.clipboard.writeText('<?php echo $wallet['address']; ?>')
                    .then(() => {
                        alert('Address copied to clipboard');
                    })
                    .catch(err => {
                        console.error('Could not copy text: ', err);
                    });
            });
            
            // Share function
            document.getElementById('shareBtn').addEventListener('click', function() {
                if (navigator.share) {
                    navigator.share({
                        title: 'My TRON Address',
                        text: 'Here is my TRON wallet address: <?php echo $wallet['address']; ?>',
                    })
                    .catch(err => {
                        console.error('Share failed:', err);
                    });
                } else {
                    alert('Web Share API not supported on this browser');
                }
            });
        });
    </script>
</body>
</html>
