<?php
/**
 * Enhanced Deposit Fund Manager with Database Integration
 * 
 * This version properly integrates with the trxbalance table and ensures
 * all deposits are transferred to the company address
 */

session_start();

// Admin authentication check
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../connect/db.php";

// Configuration - Updated to use the correct company address
$config = [
    'company_private_key' => 'ff6ffde367245699b58713f4ce44885521da6aff84903889cf61c730e887b777',
    'company_address' => 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv',
    'trongrid_api_key' => '3022fab4-cd87-48c5-b5d1-65fb3e588f67',
    'trongrid_endpoint' => 'https://api.trongrid.io',
    'min_transfer_amount' => 1.0,
    'transfer_fee' => 1.1,
    'auto_transfer' => true,
    'check_interval' => 30
];

/**
 * Improved TRON API Helper Class
 */
class TronAPIFixed {
    private $apiKey;
    private $endpoint;
    
    public function __construct($apiKey, $endpoint) {
        $this->apiKey = $apiKey;
        $this->endpoint = $endpoint;
    }
    
    /**
     * Get TRX balance for an address using TronGrid API
     */
    public function getBalance($address) {
        try {
            // Use TronGrid API to get account info
            $url = $this->endpoint . "/v1/accounts/{$address}";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'TRON-PRO-API-KEY: ' . $this->apiKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                error_log("CURL Error: " . $error);
                return false;
            }
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if (isset($data['data'][0]['balance'])) {
                    return $data['data'][0]['balance'] / 1000000; // Convert from SUN to TRX
                }
            }
            
            // Try alternative endpoint if first fails
            return $this->getBalanceAlternative($address);
            
        } catch (Exception $e) {
            error_log("TronAPI getBalance error: " . $e->getMessage());
            return $this->getBalanceAlternative($address);
        }
    }
    
    /**
     * Alternative balance check using TronScan API
     */
    private function getBalanceAlternative($address) {
        try {
            $url = "https://apilist.tronscanapi.com/api/account?address={$address}";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if (isset($data['balance'])) {
                    return $data['balance'] / 1000000; // Convert from SUN to TRX
                }
            }
            
            return 0;
        } catch (Exception $e) {
            error_log("Alternative balance check error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get transaction history for an address
     */
    public function getTransactions($address, $limit = 20) {
        try {
            $url = "https://apilist.tronscanapi.com/api/transaction?sort=-timestamp&count=true&limit={$limit}&start=0&address={$address}";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                return $data['data'] ?? [];
            }
            
            return [];
        } catch (Exception $e) {
            error_log("getTransactions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if address has recent incoming transactions
     */
    public function hasRecentDeposits($address, $lastCheckTime = null) {
        $transactions = $this->getTransactions($address, 10);
        $recentDeposits = [];
        
        $checkTime = $lastCheckTime ? strtotime($lastCheckTime) : (time() - 3600); // Last hour if no time specified
        
        foreach ($transactions as $tx) {
            if ($tx['timestamp'] > ($checkTime * 1000)) { // TronScan uses milliseconds
                if (isset($tx['toAddress']) && $tx['toAddress'] === $address && $tx['contractType'] === 1) {
                    $recentDeposits[] = [
                        'hash' => $tx['hash'],
                        'amount' => $tx['amount'] / 1000000, // Convert to TRX
                        'timestamp' => $tx['timestamp'],
                        'from' => $tx['ownerAddress']
                    ];
                }
            }
        }
        
        return $recentDeposits;
    }
}

/**
 * Enhanced Deposit Fund Manager with Database Integration
 */
class DepositFundManagerFixed {
    private $pdo;
    private $tronAPI;
    private $config;
    
    public function __construct($pdo, $config) {
        $this->pdo = $pdo;
        $this->config = $config;
        $this->tronAPI = new TronAPIFixed($config['trongrid_api_key'], $config['trongrid_endpoint']);
        $this->createTables();
    }
    
    /**
     * Get all user wallets with enhanced balance checking from trxbalance table
     */
    public function getAllUserBalances() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    tb.id,
                    tb.user_id,
                    tb.username,
                    tb.address as wallet_address,
                    tb.private_key,
                    tb.balance as db_balance,
                    0 as blockchain_balance,
                    0 as pending_deposits,
                    0 as max_transferable_amount,
                    COALESCE(dl.last_processed, '1970-01-01') as last_processed,
                    u.email
                FROM trxbalance tb
                LEFT JOIN users2 u ON tb.user_id = u.id
                LEFT JOIN (
                    SELECT user_id, MAX(processed_at) as last_processed 
                    FROM deposit_logs 
                    WHERE status = 'completed' 
                    GROUP BY user_id
                ) dl ON tb.user_id = dl.user_id
                WHERE tb.address IS NOT NULL AND tb.private_key IS NOT NULL
                ORDER BY tb.user_id
            ");
            
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get real blockchain balances and calculate max transferable amounts
            foreach ($users as &$user) {
                if ($user['wallet_address']) {
                    $blockchainBalance = $this->tronAPI->getBalance($user['wallet_address']);
                    $user['blockchain_balance'] = $blockchainBalance !== false ? $blockchainBalance : 0;
                    
                    // Calculate max transferable amount (blockchain balance - transfer fee)
                    $user['max_transferable_amount'] = max(0, $user['blockchain_balance'] - $this->config['transfer_fee']);
                    
                    // Calculate pending deposits (same as max transferable for now)
                    $user['pending_deposits'] = $user['max_transferable_amount'];
                    
                    // Check for recent deposits
                    $recentDeposits = $this->tronAPI->hasRecentDeposits($user['wallet_address'], $user['last_processed']);
                    $user['recent_deposits'] = $recentDeposits;
                    $user['has_new_deposits'] = !empty($recentDeposits);
                }
            }
            
            return $users;
        } catch (Exception $e) {
            error_log("getAllUserBalances error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Process deposits for a specific user (returns transfer data for JavaScript)
     */
    public function prepareUserTransfer($userId) {
        try {
            // Get user wallet info from trxbalance table
            $stmt = $this->pdo->prepare("
                SELECT tb.*, u.username, u.email 
                FROM trxbalance tb 
                JOIN users2 u ON tb.user_id = u.id 
                WHERE tb.user_id = ?
            ");
            $stmt->execute([$userId]);
            $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$wallet) {
                return ['success' => false, 'error' => 'Wallet not found in trxbalance table'];
            }
            
            if (empty($wallet['private_key'])) {
                return ['success' => false, 'error' => 'Private key not found for user'];
            }
            
            if (empty($wallet['address'])) {
                return ['success' => false, 'error' => 'Wallet address not found for user'];
            }
            
            // Get blockchain balance
            $blockchainBalance = $this->tronAPI->getBalance($wallet['address']);
            
            if ($blockchainBalance === false) {
                return ['success' => false, 'error' => 'Failed to get blockchain balance'];
            }
            
            // Calculate max transferable amount (blockchain balance - transfer fee)
            $maxTransferableAmount = $blockchainBalance - $this->config['transfer_fee'];
            
            if ($maxTransferableAmount < $this->config['min_transfer_amount']) {
                return [
                    'success' => false, 
                    'error' => 'Insufficient balance for transfer',
                    'blockchain_balance' => $blockchainBalance,
                    'max_transferable' => $maxTransferableAmount,
                    'required_minimum' => $this->config['min_transfer_amount']
                ];
            }
            
            return [
                'success' => true,
                'user_id' => $userId,
                'from_address' => $wallet['address'],
                'from_private_key' => $wallet['private_key'],
                'to_address' => $this->config['company_address'], // Ensure all deposits go to company address
                'amount' => $maxTransferableAmount,
                'max_amount' => $maxTransferableAmount,
                'blockchain_balance' => $blockchainBalance,
                'username' => $wallet['username']
            ];
            
        } catch (Exception $e) {
            error_log("prepareUserTransfer error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Complete the transfer after JavaScript sends the transaction
     */
    public function completeTransfer($userId, $amount, $txHash) {
        try {
            $this->pdo->beginTransaction();
            
            // Get user info from trxbalance
            $stmt = $this->pdo->prepare("SELECT * FROM trxbalance WHERE user_id = ?");
            $stmt->execute([$userId]);
            $userWallet = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$userWallet) {
                throw new Exception("User wallet not found in trxbalance table");
            }
            
            // Update user database balance in trxbalance table
            $stmt = $this->pdo->prepare("UPDATE trxbalance SET balance = balance + ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$amount, $userId]);
            
            // Log the deposit
            $stmt = $this->pdo->prepare("
                INSERT INTO deposit_logs (
                    user_id, from_address, to_address, amount, tx_hash, 
                    status, processed_at, created_at
                ) VALUES (?, ?, ?, ?, ?, 'completed', NOW(), NOW())
            ");
            
            $stmt->execute([
                $userId,
                $userWallet['address'],
                $this->config['company_address'],
                $amount,
                $txHash
            ]);
            
            // Add to trxhistory
            $stmt = $this->pdo->prepare("
                INSERT INTO trxhistory (
                    user_id, from_address, to_address, amount, tx_hash, 
                    status, transaction_type, timestamp
                ) VALUES (?, ?, ?, ?, ?, 'completed', 'deposit', NOW())
            ");
            
            $stmt->execute([
                $userId,
                $userWallet['address'],
                $this->config['company_address'],
                $amount,
                $txHash
            ]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Transfer completed successfully',
                'amount' => $amount,
                'tx_hash' => $txHash,
                'to_address' => $this->config['company_address']
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log("completeTransfer error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get deposit statistics
     */
    public function getDepositStats() {
        try {
            $stats = [];
            
            // Total deposits today
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
                FROM deposit_logs 
                WHERE DATE(created_at) = CURDATE() AND status = 'completed'
            ");
            $todayStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['today'] = $todayStats;
            
            // Total deposits this month
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
                FROM deposit_logs 
                WHERE MONTH(created_at) = MONTH(CURDATE()) 
                AND YEAR(created_at) = YEAR(CURDATE()) 
                AND status = 'completed'
            ");
            $monthStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['month'] = $monthStats;
            
            // Pending deposits from trxbalance table
            $users = $this->getAllUserBalances();
            $pendingCount = 0;
            $pendingAmount = 0;
            
            foreach ($users as $user) {
                if ($user['max_transferable_amount'] >= $this->config['min_transfer_amount']) {
                    $pendingCount++;
                    $pendingAmount += $user['max_transferable_amount'];
                }
            }
            
            $stats['pending'] = [
                'count' => $pendingCount,
                'total' => $pendingAmount
            ];
            
            // Company wallet balance
            $companyBalance = $this->tronAPI->getBalance($this->config['company_address']);
            $stats['company_balance'] = $companyBalance !== false ? $companyBalance : 0;
            
            return $stats;
        } catch (Exception $e) {
            error_log("getDepositStats error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create required database tables
     */
    private function createTables() {
        try {
            // Create deposit_logs table if it doesn't exist
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS deposit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                from_address VARCHAR(255),
                to_address VARCHAR(255),
                amount DECIMAL(20,6),
                tx_hash VARCHAR(255),
                status VARCHAR(50),
                processed_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_status (status),
                INDEX idx_created (created_at),
                INDEX idx_tx_hash (tx_hash)
            )");
            
        } catch (Exception $e) {
            error_log("createTables error: " . $e->getMessage());
        }
    }
}

// Initialize the manager
$manager = new DepositFundManagerFixed($pdo, $config);

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_balances':
            echo json_encode($manager->getAllUserBalances());
            break;
            
        case 'prepare_transfer':
            $userId = intval($_POST['user_id']);
            echo json_encode($manager->prepareUserTransfer($userId));
            break;
            
        case 'complete_transfer':
            $userId = intval($_POST['user_id']);
            $amount = floatval($_POST['amount']);
            $txHash = $_POST['tx_hash'];
            echo json_encode($manager->completeTransfer($userId, $amount, $txHash));
            break;
            
        case 'get_stats':
            echo json_encode($manager->getDepositStats());
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}

// Get initial data
$userBalances = $manager->getAllUserBalances();
$stats = $manager->getDepositStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Deposit Fund Manager - Admin</title>
    <script src="https://cdn.jsdelivr.net/npm/tronweb@4.4.0/dist/TronWeb.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f6fa;
            color: #2c3e50;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #3498db;
        }

        .stat-card.pending {
            border-left-color: #f39c12;
        }

        .stat-card.company {
            border-left-color: #27ae60;
        }

        .stat-card.today {
            border-left-color: #e74c3c;
        }

        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .controls {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .controls h3 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn.success {
            background: #27ae60;
        }

        .btn.success:hover {
            background: #229954;
        }

        .btn.warning {
            background: #f39c12;
        }

        .btn.warning:hover {
            background: #e67e22;
        }

        .btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            transform: none;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            background: #34495e;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            margin: 0;
        }

        .search-box {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .search-box::placeholder {
            color: rgba(255,255,255,0.7);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .balance {
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }

        .balance.positive {
            color: #27ae60;
        }

        .balance.zero {
            color: #7f8c8d;
        }

        .address {
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            color: #7f8c8d;
        }

        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status.ready {
            background: #d4edda;
            color: #155724;
        }

        .status.insufficient {
            background: #f8d7da;
            color: #721c24;
        }

        .status.new-deposits {
            background: #cce5ff;
            color: #004085;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .action-btn {
            padding: 6px 12px;
            font-size: 0.8em;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            margin-right: 5px;
        }

        .action-btn.process {
            background: #28a745;
            color: white;
        }

        .action-btn.process:hover {
            background: #218838;
        }

        .action-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }

        .alert.info {
            background: #d1ecf1;
            color: #0c5460;
            border-left-color: #17a2b8;
        }

        .auto-refresh {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: 20px;
        }

        .auto-refresh input[type="checkbox"] {
            transform: scale(1.2);
        }

        .auto-refresh label {
            color: white;
            font-size: 0.9em;
        }

        .tronweb-status {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }

        .tronweb-status.connected {
            background: #d4edda;
            color: #155724;
        }

        .tronweb-status.disconnected {
            background: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .table {
                font-size: 0.9em;
            }
            
            .table th,
            .table td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>💰 Enhanced Deposit Fund Manager</h1>
            <p>Integrated with trxbalance table - All deposits go to company address</p>
            <p><strong>Company Address:</strong> <?php echo $config['company_address']; ?></p>
        </div>

        <!-- TronWeb Status -->
        <div id="tronwebStatus" class="tronweb-status disconnected">
            🔄 Initializing TronWeb...
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card pending">
                <div class="stat-value" id="pendingCount"><?php echo $stats['pending']['count'] ?? 0; ?></div>
                <div class="stat-label">Pending Deposits</div>
                <div style="font-size: 0.8em; margin-top: 5px; color: #f39c12;">
                    <?php echo number_format($stats['pending']['total'] ?? 0, 6); ?> TRX
                </div>
            </div>
            
            <div class="stat-card today">
                <div class="stat-value" id="todayCount"><?php echo $stats['today']['count'] ?? 0; ?></div>
                <div class="stat-label">Today's Deposits</div>
                <div style="font-size: 0.8em; margin-top: 5px; color: #e74c3c;">
                    <?php echo number_format($stats['today']['total'] ?? 0, 6); ?> TRX
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value" id="monthCount"><?php echo $stats['month']['count'] ?? 0; ?></div>
                <div class="stat-label">This Month</div>
                <div style="font-size: 0.8em; margin-top: 5px; color: #3498db;">
                    <?php echo number_format($stats['month']['total'] ?? 0, 6); ?> TRX
                </div>
            </div>
            
            <div class="stat-card company">
                <div class="stat-value" id="companyBalance"><?php echo number_format($stats['company_balance'] ?? 0, 6); ?></div>
                <div class="stat-label">Company Balance</div>
                <div style="font-size: 0.8em; margin-top: 5px; color: #27ae60;">
                    TRX
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls">
            <h3>🎛️ Control Panel</h3>
            <button class="btn success" onclick="processAllDeposits()" id="processAllBtn">
                🚀 Process All Deposits
            </button>
            <button class="btn" onclick="refreshData()" id="refreshBtn">
                🔄 Refresh Data
            </button>
            <button class="btn warning" onclick="testTronWeb()">
                🧪 Test TronWeb
            </button>
            
            <div class="auto-refresh">
                <input type="checkbox" id="autoRefresh" onchange="toggleAutoRefresh()">
                <label for="autoRefresh">Auto-refresh every 30s</label>
            </div>
        </div>

        <!-- Alerts -->
        <div id="alertContainer"></div>

        <!-- User Balances Table -->
        <div class="table-container">
            <div class="table-header">
                <h3>👥 User Wallet Balances (from trxbalance table)</h3>
                <input type="text" class="search-box" placeholder="Search users..." id="searchBox" onkeyup="filterTable()">
            </div>
            
            <div id="tableContainer">
                <table class="table" id="balanceTable">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Wallet Address</th>
                            <th>DB Balance</th>
                            <th>Blockchain Balance</th>
                            <th>Max Transferable</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="balanceTableBody">
                        <?php foreach ($userBalances as $user): ?>
                        <tr data-user-id="<?php echo $user['user_id']; ?>">
                            <td><?php echo $user['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td class="address"><?php echo htmlspecialchars($user['wallet_address']); ?></td>
                            <td class="balance <?php echo $user['db_balance'] > 0 ? 'positive' : 'zero'; ?>">
                                <?php echo number_format($user['db_balance'], 6); ?> TRX
                            </td>
                            <td class="balance <?php echo $user['blockchain_balance'] > 0 ? 'positive' : 'zero'; ?>">
                                <?php echo number_format($user['blockchain_balance'], 6); ?> TRX
                            </td>
                            <td class="balance <?php echo $user['max_transferable_amount'] > 0 ? 'positive' : 'zero'; ?>">
                                <?php echo number_format($user['max_transferable_amount'], 6); ?> TRX
                            </td>
                            <td>
                                <?php if ($user['has_new_deposits']): ?>
                                    <span class="status new-deposits">New Deposits!</span>
                                <?php elseif ($user['max_transferable_amount'] >= $config['min_transfer_amount']): ?>
                                    <span class="status ready">Ready</span>
                                <?php elseif ($user['blockchain_balance'] > 0): ?>
                                    <span class="status pending">Insufficient</span>
                                <?php else: ?>
                                    <span class="status insufficient">No Deposits</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['max_transferable_amount'] >= $config['min_transfer_amount']): ?>
                                    <button class="action-btn process" onclick="processUser(<?php echo $user['user_id']; ?>)">
                                        Process
                                    </button>
                                <?php else: ?>
                                    <button class="action-btn" disabled>
                                        N/A
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Configuration
        const config = {
            companyPrivateKey: '<?php echo $config['company_private_key']; ?>',
            companyAddress: '<?php echo $config['company_address']; ?>',
            tronGridApiKey: '<?php echo $config['trongrid_api_key']; ?>',
            minTransferAmount: <?php echo $config['min_transfer_amount']; ?>
        };
        
        let tronWeb;
        let autoRefreshInterval;
        
        // Initialize TronWeb
        async function initTronWeb() {
            try {
                tronWeb = new TronWeb({
                    fullHost: 'https://api.trongrid.io',
                    headers: { "TRON-PRO-API-KEY": config.tronGridApiKey },
                    privateKey: config.companyPrivateKey
                });
                
                // Test connection
                const balance = await tronWeb.trx.getBalance(config.companyAddress);
                console.log('TronWeb initialized. Company balance:', tronWeb.fromSun(balance), 'TRX');
                
                document.getElementById('tronwebStatus').className = 'tronweb-status connected';
                document.getElementById('tronwebStatus').innerHTML = '✅ TronWeb Connected - Ready to process deposits to ' + config.companyAddress;
                
                return true;
            } catch (error) {
                console.error('TronWeb initialization failed:', error);
                document.getElementById('tronwebStatus').className = 'tronweb-status disconnected';
                document.getElementById('tronwebStatus').innerHTML = '❌ TronWeb Connection Failed: ' + error.message;
                return false;
            }
        }
        
        // Show alert
        function showAlert(message, type = 'info') {
            const alertContainer = document.getElementById('alertContainer');
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert ${type}`;
            alertDiv.innerHTML = message;
            
            alertContainer.appendChild(alertDiv);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
        
        // Process single user deposits
        async function processUser(userId) {
            if (!tronWeb) {
                showAlert('❌ TronWeb not initialized. Please refresh the page.', 'error');
                return;
            }
            
            const btn = event.target;
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Processing...';
            
            try {
                // Step 1: Prepare transfer data
                const formData = new FormData();
                formData.append('action', 'prepare_transfer');
                formData.append('user_id', userId);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const transferData = await response.json();
                
                if (!transferData.success) {
                    throw new Error(transferData.error);
                }
                
                showAlert(`🔄 Preparing transfer for User ${userId}... Max amount: ${transferData.max_amount.toFixed(6)} TRX`, 'info');
                
                // Step 2: Execute blockchain transaction
                const amountInSun = tronWeb.toSun(transferData.amount);
                
                // Create transaction from user wallet to company address
                const tempTronWeb = new TronWeb({
                    fullHost: 'https://api.trongrid.io',
                    headers: { "TRON-PRO-API-KEY": config.tronGridApiKey },
                    privateKey: transferData.from_private_key
                });
                
                const transaction = await tempTronWeb.trx.sendTransaction(
                    transferData.to_address,
                    amountInSun
                );
                
                if (!transaction || !transaction.txid) {
                    throw new Error('Failed to create blockchain transaction');
                }
                
                showAlert(`⛓️ Blockchain transaction created: ${transaction.txid}`, 'info');
                
                // Step 3: Complete the transfer in database
                const completeFormData = new FormData();
                completeFormData.append('action', 'complete_transfer');
                completeFormData.append('user_id', userId);
                completeFormData.append('amount', transferData.amount);
                completeFormData.append('tx_hash', transaction.txid);
                
                const completeResponse = await fetch('', {
                    method: 'POST',
                    body: completeFormData
                });
                
                const completeResult = await completeResponse.json();
                
                if (!completeResult.success) {
                    throw new Error('Database update failed: ' + completeResult.error);
                }
                
                showAlert(`✅ Successfully processed User ${userId}! Amount: ${transferData.amount.toFixed(6)} TRX sent to ${config.companyAddress}. TX: ${transaction.txid}`, 'success');
                refreshData();
                
            } catch (error) {
                console.error('Process user error:', error);
                showAlert(`❌ Failed to process User ${userId}: ${error.message}`, 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        }
        
        // Process all deposits
        async function processAllDeposits() {
            if (!tronWeb) {
                showAlert('❌ TronWeb not initialized. Please refresh the page.', 'error');
                return;
            }
            
            const btn = document.getElementById('processAllBtn');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = '🔄 Processing All...';
            
            try {
                // Get all users with pending deposits
                const balancesResponse = await fetch('', {
                    method: 'POST',
                    body: new URLSearchParams({ action: 'get_balances' })
                });
                const balances = await balancesResponse.json();
                
                const usersToProcess = balances.filter(user => 
                    user.max_transferable_amount >= config.minTransferAmount
                );
                
                if (usersToProcess.length === 0) {
                    showAlert('ℹ️ No users have deposits ready for processing.', 'info');
                    return;
                }
                
                showAlert(`🚀 Processing ${usersToProcess.length} users with pending deposits to ${config.companyAddress}...`, 'info');
                
                let successCount = 0;
                let totalAmount = 0;
                
                for (const user of usersToProcess) {
                    try {
                        // Prepare transfer
                        const formData = new FormData();
                        formData.append('action', 'prepare_transfer');
                        formData.append('user_id', user.user_id);
                        
                        const response = await fetch('', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const transferData = await response.json();
                        
                        if (!transferData.success) {
                            console.warn(`Skipping user ${user.user_id}: ${transferData.error}`);
                            continue;
                        }
                        
                        // Execute blockchain transaction
                        const amountInSun = tronWeb.toSun(transferData.amount);
                        
                        const tempTronWeb = new TronWeb({
                            fullHost: 'https://api.trongrid.io',
                            headers: { "TRON-PRO-API-KEY": config.tronGridApiKey },
                            privateKey: transferData.from_private_key
                        });
                        
                        const transaction = await tempTronWeb.trx.sendTransaction(
                            transferData.to_address,
                            amountInSun
                        );
                        
                        if (transaction && transaction.txid) {
                            // Complete transfer in database
                            const completeFormData = new FormData();
                            completeFormData.append('action', 'complete_transfer');
                            completeFormData.append('user_id', user.user_id);
                            completeFormData.append('amount', transferData.amount);
                            completeFormData.append('tx_hash', transaction.txid);
                            
                            const completeResponse = await fetch('', {
                                method: 'POST',
                                body: completeFormData
                            });
                            
                            const completeResult = await completeResponse.json();
                            
                            if (completeResult.success) {
                                successCount++;
                                totalAmount += transferData.amount;
                                console.log(`✅ Processed user ${user.user_id}: ${transferData.amount} TRX to ${config.companyAddress}`);
                            }
                        }
                        
                        // Add delay to avoid rate limiting
                        await new Promise(resolve => setTimeout(resolve, 2000));
                        
                    } catch (error) {
                        console.error(`Error processing user ${user.user_id}:`, error);
                    }
                }
                
                if (successCount > 0) {
                    showAlert(`✅ Successfully processed ${successCount} deposits. Total: ${totalAmount.toFixed(6)} TRX sent to ${config.companyAddress}`, 'success');
                } else {
                    showAlert(`⚠️ No deposits were successfully processed. Check console for details.`, 'error');
                }
                
                refreshData();
                
            } catch (error) {
                console.error('Process all error:', error);
                showAlert(`❌ Error processing deposits: ${error.message}`, 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        }
        
        // Test TronWeb connection
        async function testTronWeb() {
            try {
                if (!tronWeb) {
                    throw new Error('TronWeb not initialized');
                }
                
                const balance = await tronWeb.trx.getBalance(config.companyAddress);
                const balanceTRX = tronWeb.fromSun(balance);
                
                showAlert(`✅ TronWeb test successful! Company balance: ${balanceTRX.toFixed(6)} TRX at ${config.companyAddress}`, 'success');
            } catch (error) {
                showAlert(`❌ TronWeb test failed: ${error.message}`, 'error');
            }
        }
        
        // Refresh data
        async function refreshData() {
            const btn = document.getElementById('refreshBtn');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = '🔄 Refreshing...';
            
            try {
                // Refresh balances
                const balancesResponse = await fetch('', {
                    method: 'POST',
                    body: new URLSearchParams({ action: 'get_balances' })
                });
                const balances = await balancesResponse.json();
                
                // Refresh stats
                const statsResponse = await fetch('', {
                    method: 'POST',
                    body: new URLSearchParams({ action: 'get_stats' })
                });
                const stats = await statsResponse.json();
                
                // Update table
                updateBalanceTable(balances);
                
                // Update stats
                updateStats(stats);
                
                showAlert('✅ Data refreshed successfully', 'success');
            } catch (error) {
                showAlert(`❌ Error refreshing data: ${error.message}`, 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        }
        
        // Update balance table
        function updateBalanceTable(balances) {
            const tbody = document.getElementById('balanceTableBody');
            tbody.innerHTML = '';
            
            balances.forEach(user => {
                const row = document.createElement('tr');
                row.setAttribute('data-user-id', user.user_id);
                
                let status = '';
                if (user.has_new_deposits) {
                    status = '<span class="status new-deposits">New Deposits!</span>';
                } else if (user.max_transferable_amount >= config.minTransferAmount) {
                    status = '<span class="status ready">Ready</span>';
                } else if (user.blockchain_balance > 0) {
                    status = '<span class="status pending">Insufficient</span>';
                } else {
                    status = '<span class="status insufficient">No Deposits</span>';
                }
                
                const actionBtn = user.max_transferable_amount >= config.minTransferAmount ?
                    `<button class="action-btn process" onclick="processUser(${user.user_id})">Process</button>` :
                    '<button class="action-btn" disabled>N/A</button>';
                
                row.innerHTML = `
                    <td>${user.user_id}</td>
                    <td>${user.username}</td>
                    <td class="address">${user.wallet_address}</td>
                    <td class="balance ${user.db_balance > 0 ? 'positive' : 'zero'}">${parseFloat(user.db_balance).toFixed(6)} TRX</td>
                    <td class="balance ${user.blockchain_balance > 0 ? 'positive' : 'zero'}">${parseFloat(user.blockchain_balance).toFixed(6)} TRX</td>
                    <td class="balance ${user.max_transferable_amount > 0 ? 'positive' : 'zero'}">${parseFloat(user.max_transferable_amount).toFixed(6)} TRX</td>
                    <td>${status}</td>
                    <td>${actionBtn}</td>
                `;
                
                tbody.appendChild(row);
            });
        }
        
        // Update stats
        function updateStats(stats) {
            document.getElementById('pendingCount').textContent = stats.pending?.count || 0;
            document.getElementById('todayCount').textContent = stats.today?.count || 0;
            document.getElementById('monthCount').textContent = stats.month?.count || 0;
            document.getElementById('companyBalance').textContent = parseFloat(stats.company_balance || 0).toFixed(6);
        }
        
        // Filter table
        function filterTable() {
            const searchTerm = document.getElementById('searchBox').value.toLowerCase();
            const rows = document.querySelectorAll('#balanceTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }
        
        // Toggle auto-refresh
        function toggleAutoRefresh() {
            const checkbox = document.getElementById('autoRefresh');
            
            if (checkbox.checked) {
                autoRefreshInterval = setInterval(refreshData, 30000); // 30 seconds
                showAlert('🔄 Auto-refresh enabled (30s intervals)', 'info');
            } else {
                clearInterval(autoRefreshInterval);
                showAlert('⏹️ Auto-refresh disabled', 'info');
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', async function() {
            showAlert('🚀 Initializing Enhanced Deposit Fund Manager...', 'info');
            
            const tronWebReady = await initTronWeb();
            
            if (tronWebReady) {
                showAlert(`✅ System ready! All deposits will be sent to company address: ${config.companyAddress}`, 'success');
            } else {
                showAlert('⚠️ TronWeb initialization failed. Some features may not work.', 'error');
            }
        });
    </script>
</body>
</html>