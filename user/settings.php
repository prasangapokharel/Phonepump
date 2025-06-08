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

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users2 WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user's wallet
$stmt = $pdo->prepare("SELECT * FROM trxbalance WHERE user_id = ?");
$stmt->execute([$user_id]);
$wallet = $stmt->fetch();

$error = "";
$success = "";

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required";
    } elseif (!password_verify($current_password, $user['password'])) {
        $error = "Current password is incorrect";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters long";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users2 SET password = ? WHERE id = ?");
        
        if ($stmt->execute([$hashed_password, $user_id])) {
            $success = "Password changed successfully!";
        } else {
            $error = "Failed to change password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - TRON Wallet</title>
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
    
    <div class="container mx-auto px-4 py-6">
        <header class="flex justify-between items-center mb-6">
            <div class="flex items-center">
                <a href="dashboard.php" class="mr-4">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 12H5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 19L5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <h1 class="text-xl font-bold">Settings</h1>
            </div>
        </header>
        
        <main>
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
            
            <!-- Settings Menu -->
            <div class="space-y-4">
                
                <!-- Change Password -->
                <div class="bg-background-card rounded-xl shadow-lg overflow-hidden">
                    <button onclick="toggleSection('password-section')" class="w-full p-4 flex items-center justify-between hover:bg-opacity-80 transition">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-accent-yellow mr-3" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18 11H6C4.89543 11 4 11.8954 4 13V19C4 20.1046 4.89543 21 6 21H18C19.1046 21 20 20.1046 20 19V13C20 11.8954 19.1046 11 18 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M7 11V7C7 5.67392 7.52678 4.40215 8.46447 3.46447C9.40215 2.52678 10.6739 2 12 2C13.3261 2 14.5979 2.52678 15.5355 3.46447C16.4732 4.40215 17 5.67392 17 7V11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <div class="text-left">
                                <div class="font-medium">Change Password</div>
                                <div class="text-text-secondary text-sm">Update your account password</div>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-text-secondary" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    
                    <div id="password-section" class="hidden border-t border-gray-700 p-4">
                        <form method="POST">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-text-secondary mb-2">Current Password</label>
                                    <input type="password" name="current_password" class="w-full bg-background-dark border border-gray-700 rounded-lg px-4 py-3 focus:outline-none focus:border-accent-yellow" required>
                                </div>
                                <div>
                                    <label class="block text-text-secondary mb-2">New Password</label>
                                    <input type="password" name="new_password" class="w-full bg-background-dark border border-gray-700 rounded-lg px-4 py-3 focus:outline-none focus:border-accent-yellow" required>
                                </div>
                                <div>
                                    <label class="block text-text-secondary mb-2">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="w-full bg-background-dark border border-gray-700 rounded-lg px-4 py-3 focus:outline-none focus:border-accent-yellow" required>
                                </div>
                                <button type="submit" name="change_password" class="w-full bg-accent-yellow text-black py-3 rounded-lg font-medium hover:bg-opacity-80 transition">
                                    Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Security (Private Key & Mnemonic) -->
                <div class="bg-background-card rounded-xl shadow-lg overflow-hidden">
                    <button onclick="toggleSection('security-section')" class="w-full p-4 flex items-center justify-between hover:bg-opacity-80 transition">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-accent-red mr-3" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 22S8 18 8 13V6L12 4L16 6V13C16 18 12 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <div class="text-left">
                                <div class="font-medium">Security</div>
                                <div class="text-text-secondary text-sm">View private key and mnemonic</div>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-text-secondary" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    
                    <div id="security-section" class="hidden border-t border-gray-700 p-4">
                        <?php if ($wallet): ?>
                            <div class="space-y-4">
                                <div class="bg-accent-red bg-opacity-10 border border-accent-red rounded-lg p-4">
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-accent-red mr-2 mt-0.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 9V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M12 17H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <div>
                                            <h3 class="font-medium text-accent-red mb-1">Security Warning</h3>
                                            <p class="text-sm text-text-secondary">Never share your private key or mnemonic with anyone. Keep them secure and private.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <label class="text-text-secondary">Private Key</label>
                                        <button onclick="copyToClipboard('private-key')" class="text-accent-yellow text-sm hover:underline">Copy</button>
                                    </div>
                                    <div class="bg-background-dark p-3 rounded-lg">
                                        <div id="private-key" class="font-mono text-sm break-all blur-sm hover:blur-none transition-all duration-300 cursor-pointer">
                                            <?php echo htmlspecialchars($wallet['private_key']); ?>
                                        </div>
                                    </div>
                                    <p class="text-xs text-text-secondary mt-1">Click to reveal</p>
                                </div>
                                
                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <label class="text-text-secondary">Mnemonic Phrase</label>
                                        <button onclick="copyToClipboard('mnemonic')" class="text-accent-yellow text-sm hover:underline">Copy</button>
                                    </div>
                                    <div class="bg-background-dark p-3 rounded-lg">
                                        <div id="mnemonic" class="font-mono text-sm break-all blur-sm hover:blur-none transition-all duration-300 cursor-pointer">
                                            <?php echo htmlspecialchars($wallet['mnemonic'] ?? 'abandon ability able about above absent absorb abstract absurd abuse access accident'); ?>
                                        </div>
                                    </div>
                                    <p class="text-xs text-text-secondary mt-1">Click to reveal</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8 text-text-secondary">
                                <svg class="w-12 h-12 mx-auto mb-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 22S8 18 8 13V6L12 4L16 6V13C16 18 12 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <p>No wallet found. Create a wallet first.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Logout -->
                <div class="bg-background-card rounded-xl shadow-lg">
                    <a href="../logout.php" class="w-full p-4 flex items-center justify-between hover:bg-opacity-80 transition block">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-accent-red mr-3" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M16 17L21 12L16 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M21 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <div class="text-left">
                                <div class="font-medium">Logout</div>
                                <div class="text-text-secondary text-sm">Sign out of your account</div>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-text-secondary" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
                
            </div>
        </main>
    </div>
    
    <?php include "../includes/bottomnav.php"; ?>
    
    <script>
        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId);
            section.classList.toggle('hidden');
        }
        
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                showSuccess('Copied to clipboard!');
            }).catch(() => {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showSuccess('Copied to clipboard!');
            });
        }
        
        // Show success/error messages if they exist
        <?php if (!empty($success)): ?>
            setTimeout(() => showSuccess('<?php echo addslashes($success); ?>'), 100);
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            setTimeout(() => showFail('<?php echo addslashes($error); ?>'), 100);
        <?php endif; ?>
    </script>
</body>
</html>
