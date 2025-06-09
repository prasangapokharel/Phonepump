<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once "../connect/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input: ' . json_last_error_msg()]);
    exit;
}

$required_fields = ['user_id', 'to_address', 'amount', 'total_deduction', 'from_address'];
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
$from_address = trim($input['from_address']);

if ($user_id !== $_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'User ID mismatch']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Check current user balance with FOR UPDATE to prevent race conditions
    $stmt = $pdo->prepare("SELECT balance FROM trxbalance WHERE user_id = ? FOR UPDATE");
    $stmt->execute([$user_id]);
    $current_balance = $stmt->fetchColumn();
    
    if ($current_balance === false) {
        throw new Exception("User balance not found");
    }
    
    // Convert to float for proper comparison
    $current_balance = floatval($current_balance);
    
    if ($current_balance < $total_deduction) {
        throw new Exception("Insufficient balance. Current: " . number_format($current_balance, 6) . " TRX, Required: " . number_format($total_deduction, 6) . " TRX");
    }
    
    // Deduct balance immediately
    $stmt = $pdo->prepare("UPDATE trxbalance SET balance = balance - ? WHERE user_id = ?");
    $update_result = $stmt->execute([$total_deduction, $user_id]);
    
    if (!$update_result) {
        throw new Exception("Failed to update user balance");
    }
    
    // Verify the update worked
    $stmt = $pdo->prepare("SELECT balance FROM trxbalance WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $new_balance = $stmt->fetchColumn();
    
    if ($new_balance === false) {
        throw new Exception("Failed to verify balance update");
    }
    
    $new_balance = floatval($new_balance);
    
    // Check if withdrawal_requests table exists, create if not
    try {
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
                status VARCHAR(50) DEFAULT 'pending',
                tx_hash VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                processed_at TIMESTAMP NULL,
                INDEX idx_user_status (user_id, status),
                INDEX idx_status (status)
            )");
        }
    } catch (Exception $e) {
        // Table might already exist, continue
        error_log("Table creation warning: " . $e->getMessage());
    }
    
    // Create withdrawal record with pending status
    $withdrawal_fee = $total_deduction - $amount;
    $stmt = $pdo->prepare("INSERT INTO withdrawal_requests (user_id, from_address, to_address, amount, fee, total_amount, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
    $withdrawal_result = $stmt->execute([$user_id, $from_address, $to_address, $amount, $withdrawal_fee, $total_deduction]);
    
    if (!$withdrawal_result) {
        throw new Exception("Failed to create withdrawal record");
    }
    
    $withdrawal_id = $pdo->lastInsertId();
    
    if (!$withdrawal_id) {
        throw new Exception("Failed to get withdrawal ID");
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Balance deducted successfully',
        'withdrawal_id' => $withdrawal_id,
        'new_balance' => $new_balance,
        'amount_deducted' => $total_deduction,
        'previous_balance' => $current_balance
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Deduct balance error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>