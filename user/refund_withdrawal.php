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
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

if (!isset($input['withdrawal_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing withdrawal_id']);
    exit;
}

$withdrawal_id = intval($input['withdrawal_id']);

try {
    $pdo->beginTransaction();
    
    // Check if trxhistory table exists, create if not
    try {
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
    } catch (Exception $e) {
        // Table might already exist, continue
    }
    
    // Get withdrawal details and verify ownership
    $stmt = $pdo->prepare("SELECT * FROM withdrawal_requests WHERE id = ? AND user_id = ?");
    $stmt->execute([$withdrawal_id, $_SESSION['user_id']]);
    $withdrawal = $stmt->fetch();
    
    if (!$withdrawal) {
        throw new Exception("Withdrawal not found or access denied");
    }
    
    if ($withdrawal['status'] !== 'pending') {
        throw new Exception("Withdrawal is not in pending status");
    }
    
    // Refund the amount to user's balance
    $stmt = $pdo->prepare("UPDATE trxbalance SET balance = balance + ? WHERE user_id = ?");
    $refund_result = $stmt->execute([$withdrawal['total_amount'], $withdrawal['user_id']]);
    
    if (!$refund_result) {
        throw new Exception("Failed to refund user balance");
    }
    
    // Update withdrawal status to failed
    $stmt = $pdo->prepare("UPDATE withdrawal_requests SET status = 'failed', processed_at = NOW() WHERE id = ?");
    $update_result = $stmt->execute([$withdrawal_id]);
    
    if (!$update_result) {
        throw new Exception("Failed to update withdrawal status");
    }
    
    // Add to transaction history
    $stmt = $pdo->prepare("INSERT INTO trxhistory (user_id, from_address, to_address, amount, tx_hash, status, transaction_type, timestamp) VALUES (?, ?, ?, ?, ?, 'failed', 'withdrawal_refund', NOW())");
    $history_result = $stmt->execute([$withdrawal['user_id'], $withdrawal['from_address'], $withdrawal['to_address'], $withdrawal['amount'], 'REFUND_' . $withdrawal_id]);
    
    if (!$history_result) {
        throw new Exception("Failed to create refund history");
    }
    
    // Get new balance
    $stmt = $pdo->prepare("SELECT balance FROM trxbalance WHERE user_id = ?");
    $stmt->execute([$withdrawal['user_id']]);
    $new_balance = $stmt->fetchColumn();
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Withdrawal refunded successfully',
        'new_balance' => $new_balance,
        'refunded_amount' => $withdrawal['total_amount']
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Refund withdrawal error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>