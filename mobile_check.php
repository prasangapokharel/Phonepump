<?php
// Mobile app verification script
session_start();
require_once "connect/db.php";

// Check all critical components
$checks = [
    'Database Connection' => checkDatabaseConnection(),
    'User Registration' => checkUserRegistration(),
    'Wallet Generation' => checkWalletGeneration(),
    'API Endpoints' => checkAPIEndpoints(),
    'Security Functions' => checkSecurityFunctions(),
    'Mobile Responsiveness' => checkMobileFeatures()
];

function checkDatabaseConnection() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT 1");
        return ['status' => 'OK', 'message' => 'Database connected successfully'];
    } catch (Exception $e) {
        return ['status' => 'ERROR', 'message' => 'Database connection failed: ' . $e->getMessage()];
    }
}

function checkUserRegistration() {
    global $pdo;
    try {
        // Check if users table exists and has required columns
        $stmt = $pdo->query("DESCRIBE users2");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $required = ['id', 'username', 'email', 'password', 'PH_id'];
        $missing = array_diff($required, $columns);
        
        if (empty($missing)) {
            return ['status' => 'OK', 'message' => 'User registration system ready'];
        } else {
            return ['status' => 'ERROR', 'message' => 'Missing columns: ' . implode(', ', $missing)];
        }
    } catch (Exception $e) {
        return ['status' => 'ERROR', 'message' => 'User table check failed: ' . $e->getMessage()];
    }
}

function checkWalletGeneration() {
    try {
        require_once "components/wallet_generator.php";
        $wallet = TronWalletGenerator::generateWallet();
        
        if ($wallet['success'] && !empty($wallet['address']) && !empty($wallet['private_key'])) {
            return ['status' => 'OK', 'message' => 'Wallet generation working'];
        } else {
            return ['status' => 'ERROR', 'message' => 'Wallet generation failed'];
        }
    } catch (Exception $e) {
        return ['status' => 'ERROR', 'message' => 'Wallet generation error: ' . $e->getMessage()];
    }
}

function checkAPIEndpoints() {
    $endpoints = [
        'api/wallet_operations.php?action=balance',
        'api/wallet_operations.php?action=transactions',
        'api/wallet_operations.php?action=validate_address&address=TLyqzVGLV1srkB7dToTAEqgDSfPtXRJZYH'
    ];
    
    foreach ($endpoints as $endpoint) {
        if (!file_exists($endpoint)) {
            return ['status' => 'ERROR', 'message' => 'API endpoint missing: ' . $endpoint];
        }
    }
    
    return ['status' => 'OK', 'message' => 'All API endpoints present'];
}

function checkSecurityFunctions() {
    try {
        require_once "config/security.php";
        
        // Test CSRF token generation
        $token = generateCSRFToken();
        if (empty($token)) {
            return ['status' => 'ERROR', 'message' => 'CSRF token generation failed'];
        }
        
        // Test input sanitization
        $clean = sanitize('<script>alert("xss")</script>');
        if (strpos($clean, '<script>') !== false) {
            return ['status' => 'ERROR', 'message' => 'Input sanitization failed'];
        }
        
        return ['status' => 'OK', 'message' => 'Security functions working'];
    } catch (Exception $e) {
        return ['status' => 'ERROR', 'message' => 'Security check failed: ' . $e->getMessage()];
    }
}

function checkMobileFeatures() {
    $mobile_files = [
        'includes/bottomnav.php',
        'includes/loader.php',
        'includes/successalert.php',
        'includes/failalert.php'
    ];
    
    foreach ($mobile_files as $file) {
        if (!file_exists($file)) {
            return ['status' => 'ERROR', 'message' => 'Mobile component missing: ' . $file];
        }
    }
    
    return ['status' => 'OK', 'message' => 'Mobile components ready'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRON Wallet - System Check</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        background: { dark: '#0C0C0E', card: '#1A1A1D' },
                        accent: { green: '#00FF7F', red: '#FF4C4C', yellow: '#FFD700' },
                        text: { primary: '#FFFFFF', secondary: '#AAAAAA' }
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #0C0C0E; color: #FFFFFF; font-family: 'Arial', sans-serif; }
    </style>
</head>
<body class="min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold mb-2">TRON Wallet Mobile App</h1>
                <p class="text-text-secondary">System Verification Check</p>
            </div>
            
            <div class="grid gap-4">
                <?php foreach ($checks as $component => $result): ?>
                    <div class="bg-background-card p-6 rounded-xl shadow-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-4 h-4 rounded-full mr-3 <?php echo $result['status'] === 'OK' ? 'bg-accent-green' : 'bg-accent-red'; ?>"></div>
                                <h3 class="text-lg font-medium"><?php echo $component; ?></h3>
                            </div>
                            <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $result['status'] === 'OK' ? 'bg-accent-green bg-opacity-20 text-accent-green' : 'bg-accent-red bg-opacity-20 text-accent-red'; ?>">
                                <?php echo $result['status']; ?>
                            </span>
                        </div>
                        <p class="text-text-secondary mt-2"><?php echo $result['message']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php
            $all_ok = array_reduce($checks, function($carry, $check) {
                return $carry && ($check['status'] === 'OK');
            }, true);
            ?>
            
            <div class="mt-8 text-center">
                <div class="bg-background-card p-6 rounded-xl shadow-lg">
                    <h2 class="text-2xl font-bold mb-4">Overall Status</h2>
                    <?php if ($all_ok): ?>
                        <div class="text-accent-green">
                            <svg class="w-16 h-16 mx-auto mb-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M22 11.08V12C21.9988 14.1564 21.3005 16.2547 20.0093 17.9818C18.7182 19.709 16.9033 20.9725 14.8354 21.5839C12.7674 22.1953 10.5573 22.1219 8.53447 21.3746C6.51168 20.6273 4.78465 19.2461 3.61096 17.4371C2.43727 15.628 1.87979 13.4881 2.02168 11.3363C2.16356 9.18455 2.99721 7.13631 4.39828 5.49706C5.79935 3.85781 7.69279 2.71537 9.79619 2.24013C11.8996 1.7649 14.1003 1.98232 16.07 2.85999" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M22 4L12 14.01L9 11.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <h3 class="text-xl font-bold">All Systems Operational</h3>
                            <p class="text-text-secondary mt-2">Your TRON Wallet mobile app is ready to use!</p>
                        </div>
                    <?php else: ?>
                        <div class="text-accent-red">
                            <svg class="w-16 h-16 mx-auto mb-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M15 9L9 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M9 9L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <h3 class="text-xl font-bold">Issues Detected</h3>
                            <p class="text-text-secondary mt-2">Please fix the errors above before using the application.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-8 text-center">
                <a href="index.php" class="bg-accent-yellow text-black px-6 py-3 rounded-lg font-medium hover:bg-opacity-80 transition">
                    Go to Application
                </a>
            </div>
        </div>
    </div>
</body>
</html>
