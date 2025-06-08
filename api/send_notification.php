<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

require_once "../connect/db.php";
require_once "../utils/email.php";

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || !isset($data['type']) || !isset($data['amount']) || !isset($data['tx_hash'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$user_id = $data['user_id'];
$type = $data['type'];
$amount = $data['amount'];
$tx_hash = $data['tx_hash'];

if ($user_id != $_SESSION['user_id']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'User ID mismatch']);
    exit;
}

try {
    // Get user email
    $stmt = $pdo->prepare("SELECT email FROM users2 WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_email = $stmt->fetchColumn();
    
    if ($user_email) {
        $emailService = new EmailService();
        $result = $emailService->sendTransactionNotification($user_email, $type, $amount, $tx_hash);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $result, 'message' => $result ? 'Notification sent' : 'Failed to send notification']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'User email not found']);
    }
} catch (Exception $e) {
    error_log("Notification error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Error sending notification: ' . $e->getMessage()]);
}
?>