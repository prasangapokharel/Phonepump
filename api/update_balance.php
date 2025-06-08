<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

require_once "../connect/db.php";

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || !isset($data['deduct_amount'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$user_id = $data['user_id'];
$deduct_amount = floatval($data['deduct_amount']);

if ($user_id != $_SESSION['user_id']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'User ID mismatch']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE trxbalance SET balance = balance - ? WHERE user_id = ?");
    $result = $stmt->execute([$deduct_amount, $user_id]);
    
    if ($result) {
        $stmt = $pdo->prepare("SELECT balance FROM trxbalance WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $new_balance = $stmt->fetchColumn();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Balance updated successfully',
            'new_balance' => $new_balance
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Failed to update balance']);
    }
} catch (Exception $e) {
    error_log("Balance update error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>