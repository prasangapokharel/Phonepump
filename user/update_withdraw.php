<?php
// Set content type to JSON
header('Content-Type: application/json');

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Include database connection
require_once "../connect/db.php";

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input_raw = file_get_contents('php://input');
$input = json_decode($input_raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input: ' . json_last_error_msg()]);
    exit;
}

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Empty JSON input']);
    exit;
}

// Validate required fields
$required_fields = ['user_id', 'to_address', 'amount', 'total_deduction', 'tx_hash', 'from_address'];
foreach ($required_fields as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
        exit;
    }
}

$user_id = intval($input['user_id']);
$to_address = trim($input['to_address']);
$amount = floatval($input['amount']);
$total_deduction = floatval($input['total_deduction']);
$tx_hash = trim($input['tx_hash']);
$from_address = trim($input['from_address']);

// Verify user ID matches session
if ($user_id !== $_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'User ID mismatch']);
    exit;
}

try {
    // Begin database transaction
    $pdo->beginTransaction();
    
    // Check current user balance
    $stmt = $pdo->prepare("SELECT balance FROM trxbalance WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $current_balance = $stmt->fetchColumn();
    
    if ($current_balance === false) {
        throw new Exception("User balance not found");
    }
    
    if ($current_balance < $total_deduction) {
        throw new Exception("Insufficient balance. Current: $current_balance, Required: $total_deduction");
    }
    
    // Update user balance
    $stmt = $pdo->prepare("UPDATE trxbalance SET balance = balance - ? WHERE user_id = ?");
    $update_result = $stmt->execute([$total_deduction, $user_id]);
    
    if (!$update_result) {
        throw new Exception("Failed to update user balance");
    }
    
    // Check if tables exist and create them if they don't
    try {
        // Check if trxhistory table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'trxhistory'");
        if ($stmt->rowCount() == 0) {
            // Create trxhistory table
            $pdo->exec("CREATE TABLE trxhistory (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                from_address VARCHAR(255),
                to_address VARCHAR(255),
                amount DECIMAL(20,6),
                tx_hash VARCHAR(255),
                status VARCHAR(50),
                transaction_type VARCHAR(50),
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        }
        
        // Check if withdrawal_requests table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'withdrawal_requests'");
        if ($stmt->rowCount() == 0) {
            // Create withdrawal_requests table
            $pdo->exec("CREATE TABLE withdrawal_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                from_address VARCHAR(255),
                to_address VARCHAR(255),
                amount DECIMAL(20,6),
                fee DECIMAL(20,6),
                total_amount DECIMAL(20,6),
                status VARCHAR(50),
                tx_hash VARCHAR(255),
                processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        }
    } catch (Exception $e) {
        // Tables might already exist, continue
    }
    
    // Record transaction in history
    $stmt = $pdo->prepare("INSERT INTO trxhistory (user_id, from_address, to_address, amount, tx_hash, status, transaction_type, timestamp) VALUES (?, ?, ?, ?, ?, 'completed', 'withdrawal', NOW())");
    $history_result = $stmt->execute([$user_id, $from_address, $to_address, $amount, $tx_hash]);
    
    if (!$history_result) {
        throw new Exception("Failed to record transaction history");
    }
    
    // Record withdrawal request
    $stmt = $pdo->prepare("INSERT INTO withdrawal_requests (user_id, from_address, to_address, amount, fee, total_amount, status, tx_hash, processed_at) VALUES (?, ?, ?, ?, ?, ?, 'completed', ?, NOW())");
    $withdrawal_fee = $total_deduction - $amount;
    $request_result = $stmt->execute([$user_id, $from_address, $to_address, $amount, $withdrawal_fee, $total_deduction, $tx_hash]);
    
    if (!$request_result) {
        throw new Exception("Failed to record withdrawal request");
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Get updated balance
    $stmt = $pdo->prepare("SELECT balance FROM trxbalance WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $new_balance = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'message' => 'Withdrawal processed successfully',
        'tx_hash' => $tx_hash,
        'new_balance' => $new_balance,
        'amount_sent' => $amount,
        'fee_deducted' => $withdrawal_fee,
        'total_deducted' => $total_deduction
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Withdrawal database update error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>