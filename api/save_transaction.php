<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

require_once "../connect/db.php";

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || !isset($data['from_address']) || !isset($data['to_address']) || 
    !isset($data['amount']) || !isset($data['tx_hash']) || !isset($data['status'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$user_id = $data['user_id'];
$username = $data['username'] ?? '';
$from_address = $data['from_address'];
$to_address = $data['to_address'];
$amount = floatval($data['amount']);
$tx_hash = $data['tx_hash'];
$status = $data['status'];

if ($user_id != $_SESSION['user_id']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'User ID mismatch']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO trxhistory 
        (user_id, username, from_address, to_address, amount, tx_hash, status, timestamp) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        $user_id,
        $username,
        $from_address,
        $to_address,
        $amount,
        $tx_hash,
        $status
    ]);
    
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Transaction saved successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Failed to save transaction']);
    }
} catch (Exception $e) {
    error_log("Transaction save error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>