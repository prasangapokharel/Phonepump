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

// Check if Composer autoloader exists
if (!file_exists('../vendor/autoload.php')) {
    die("Error: vendor/autoload.php not found. Please run 'composer require bacon/bacon-qr-code'");
}

require_once '../vendor/autoload.php';

// Import QR Code classes
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Writer;

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

// Generate QR Code
$qrCodeData = '';
$qrCodeError = '';

try {
    $walletAddress = $wallet['address'];
    
    // Check if we should use Imagick or SVG
    if (extension_loaded('imagick')) {
        // Use Imagick backend for PNG
        $renderer = new ImageRenderer(
            new RendererStyle(300, 0, null, null, Fill::uniformColor(new Rgb(0, 0, 0), new Rgb(255, 255, 255))),
            new ImagickImageBackEnd()
        );
        $writer = new Writer($renderer);
        
        // Generate QR code as base64 PNG
        $qrCodeData = 'data:image/png;base64,' . base64_encode($writer->writeString($walletAddress));
    } else {
        // Fallback to SVG
        $renderer = new ImageRenderer(
            new RendererStyle(300, 0, null, null, Fill::uniformColor(new Rgb(0, 0, 0), new Rgb(255, 255, 255))),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        
        // Generate QR code as SVG
        $qrCodeSvg = $writer->writeString($walletAddress);
        $qrCodeData = 'data:image/svg+xml;base64,' . base64_encode($qrCodeSvg);
    }
} catch (Exception $e) {
    $qrCodeError = "Error generating QR code: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit - TRX Wallet</title>
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

        /* Deposit Header */
        .deposit-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .deposit-icon {
            width: 48px;
            height: 48px;
            background: #111;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: #fff;
        }

        .deposit-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #fff;
        }

        .deposit-subtitle {
            color: #999;
            font-size: 16px;
        }

        /* Address Section */
        .address-section {
            background: #111;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid #333;
        }

        .address-label {
            font-size: 14px;
            color: #999;
            margin-bottom: 12px;
            font-weight: 500;
        }

        .address-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .address-text {
            background: #000;
            padding: 16px;
            border-radius: 12px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            word-break: break-all;
            color: #fff;
            border: 1px solid #333;
        }

        .copy-btn {
            background: #000;
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
        }

        /* QR Code Section */
        .qr-section {
            background: #111;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #333;
            text-align: center;
        }

        .qr-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }

        .qr-code {
            background: #fff;
            padding: 16px;
            border-radius: 12px;
            display: inline-block;
            width: 200px;
            height: 200px;
        }

        .qr-code img {
            display: block;
            width: 100%;
            height: 100%;
        }

        .qr-label {
            color: #999;
            font-size: 14px;
        }

        .qr-error {
            color: #fff;
            font-size: 14px;
            padding: 16px;
            background: #333;
            border-radius: 8px;
            border: 1px solid #444;
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

        /* Alert */
        .alert {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #111;
            color: #fff;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            z-index: 9999;
            border: 1px solid #333;
            display: none;
        }

        .alert.show {
            display: block;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .content {
                padding: 16px;
            }
            
            .address-text {
                font-size: 12px;
                padding: 12px;
            }
            
            .qr-section {
                padding: 20px;
            }
            
            .deposit-title {
                font-size: 20px;
            }

            .qr-code {
                width: 180px;
                height: 180px;
            }
        }
    </style>
</head>
<body>
    <div class="app">
    

        <!-- Content -->
        <div class="content">
            <!-- Deposit Header -->
            <div class="deposit-header">
                <div class="deposit-icon">
                    
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
                    <?php if ($qrCodeError): ?>
                        <div class="qr-error">
                            <?php echo htmlspecialchars($qrCodeError); ?>
                        </div>
                    <?php else: ?>
                        <div class="qr-code">
                            <img src="<?php echo $qrCodeData; ?>" alt="QR Code for wallet address" />
                        </div>
                        <div class="qr-label">Scan QR code to deposit</div>
                    <?php endif; ?>
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
        <?php include '../includes/bottomnav.php'; ?>
    </div>

    <!-- Alert for copy success -->
    <div class="alert" id="copyAlert">Address copied to clipboard</div>

    <script>
        // Copy address function
        function copyAddress() {
            const address = document.getElementById('walletAddress').textContent.trim();
            const copyBtn = document.getElementById('copyBtn');
            const copyAlert = document.getElementById('copyAlert');
            
            navigator.clipboard.writeText(address).then(() => {
                // Show alert
                copyAlert.classList.add('show');
                
                setTimeout(() => {
                    copyAlert.classList.remove('show');
                }, 2000);
            }).catch(() => {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = address;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                // Show alert
                copyAlert.classList.add('show');
                
                setTimeout(() => {
                    copyAlert.classList.remove('show');
                }, 2000);
            });
        }
    </script>
</body>
</html>