<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

require_once "../connect/db.php";

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['clear_withdrawal_data']) && $data['clear_withdrawal_data']) {
    // Clear withdrawal session data
    unset($_SESSION['withdrawal_data']);
    
    // Clear OTP from database
    $stmt = $pdo->prepare("UPDATE users2 SET otp = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Session data cleared']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>