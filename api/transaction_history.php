<?php
session_start();
require_once "../connect/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

try {
    // Get recent transactions from both TRX history and token transactions
    $stmt = $pdo->prepare("
        (SELECT 
            'trx' as type,
            amount,
            status,
            timestamp,
            tx_hash,
            from_address,
            to_address
        FROM trxhistory 
        WHERE user_id = ?)
        UNION ALL
        (SELECT 
            'token' as type,
            CONCAT(token_amount, ' tokens') as amount,
            'confirmed' as status,
            created_at as timestamp,
            transaction_hash as tx_hash,
            '' as from_address,
            '' as to_address
        FROM transactions 
        WHERE user_id = ?)
        ORDER BY timestamp DESC 
        LIMIT ?
    ");
    $stmt->execute([$user_id, $user_id, $limit]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'transactions' => $transactions
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
