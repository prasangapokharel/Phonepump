<?php
// API endpoint for wallet operations
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Start session
session_start();

// Include database connection
require_once "../connect/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'balance':
            // Get user balance
            $stmt = $pdo->prepare("SELECT balance, address FROM trxbalance WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $wallet = $stmt->fetch();
            
            if ($wallet) {
                echo json_encode([
                    'success' => true,
                    'balance' => floatval($wallet['balance']),
                    'address' => $wallet['address'],
                    'usd_value' => floatval($wallet['balance']) * 0.20
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Wallet not found']);
            }
            break;
            
        case 'transactions':
            // Get recent transactions
            $limit = intval($_GET['limit'] ?? 10);
            $stmt = $pdo->prepare("SELECT * FROM trxhistory WHERE user_id = ? ORDER BY timestamp DESC LIMIT ?");
            $stmt->execute([$user_id, $limit]);
            $transactions = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'transactions' => $transactions
            ]);
            break;
            
        case 'validate_address':
            // Validate TRON address
            $address = $_GET['address'] ?? '';
            
            // Basic TRON address validation
            $is_valid = (strlen($address) === 34 && substr($address, 0, 1) === 'T');
            
            echo json_encode([
                'success' => true,
                'valid' => $is_valid
            ]);
            break;
            
        case 'check_username':
            // Check if username exists for transfer
            $username = $_GET['username'] ?? '';
            
            $stmt = $pdo->prepare("SELECT u.username, tb.address FROM users2 u JOIN trxbalance tb ON u.id = tb.user_id WHERE u.username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'exists' => $user ? true : false,
                'username' => $user ? $user['username'] : null
            ]);
            break;
            
        case 'market_data':
            // Get market data
            $stmt = $pdo->prepare("SELECT * FROM crypto_prices ORDER BY current_price DESC");
            $stmt->execute();
            $prices = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $prices,
                'last_updated' => date('Y-m-d H:i:s')
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
