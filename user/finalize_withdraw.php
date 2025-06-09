<?php
// Ensure clean output
ob_start();

// Set proper headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Start session
session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Include database connection
require_once "../connect/db.php";

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get and validate input
$input_raw = file_get_contents('php://input');
$input = json_decode($input_raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input: ' . json_last_error_msg()]);
    exit;
}

// Validate required fields
$required_fields = ['withdrawal_id', 'tx_hash'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        http_response_code(400);
        ob_clean();
        echo json_encode(['success' => false, 'error' => "Missing or empty required field: $field"]);
        exit;
    }
}

$withdrawal_id = intval($input['withdrawal_id']);
$tx_hash = trim($input['tx_hash']);

// Validate inputs
if ($withdrawal_id <= 0) {
    http_response_code(400);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid withdrawal ID']);
    exit;
}

if (strlen($tx_hash) < 10) {
    http_response_code(400);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid transaction hash']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Check if trxhistory table exists, create if not
    $stmt = $pdo->query("SHOW TABLES LIKE 'trxhistory'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE trxhistory (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            from_address VARCHAR(255),
            to_address VARCHAR(255),
            amount DECIMAL(20,6),
            tx_hash VARCHAR(255),
            status VARCHAR(50),
            transaction_type VARCHAR(50),
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_tx_hash (tx_hash),
            INDEX idx_status (status)
        )");
    }

    // Get withdrawal details and verify ownership
    $stmt = $pdo->prepare("SELECT * FROM withdrawal_requests WHERE id = ? AND user_id = ?");
    $stmt->execute([$withdrawal_id, $_SESSION['user_id']]);
    $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$withdrawal) {
        throw new Exception("Withdrawal not found or access denied");
    }

    if ($withdrawal['status'] !== 'pending') {
        // If already completed, just return success
        if ($withdrawal['status'] === 'completed') {
            $pdo->commit();
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Withdrawal already completed',
                'tx_hash' => $withdrawal['tx_hash'] ?: $tx_hash,
                'status' => 'already_completed'
            ]);
            exit;
        }
        
        throw new Exception("Withdrawal is not in pending status. Current status: " . $withdrawal['status']);
    }

    // Update withdrawal status to completed
    $stmt = $pdo->prepare("UPDATE withdrawal_requests SET status = 'completed', tx_hash = ?, processed_at = NOW() WHERE id = ?");
    $update_result = $stmt->execute([$tx_hash, $withdrawal_id]);

    if (!$update_result) {
        throw new Exception("Failed to update withdrawal status");
    }

    // Check if this transaction already exists in history
    $stmt = $pdo->prepare("SELECT id FROM trxhistory WHERE tx_hash = ? AND user_id = ?");
    $stmt->execute([$tx_hash, $withdrawal['user_id']]);
    $existing_history = $stmt->fetch();

    if (!$existing_history) {
        // Add to transaction history
        $stmt = $pdo->prepare("INSERT INTO trxhistory (user_id, from_address, to_address, amount, tx_hash, status, transaction_type, timestamp) VALUES (?, ?, ?, ?, ?, 'completed', 'withdrawal', NOW())");
        $history_result = $stmt->execute([
            $withdrawal['user_id'], 
            $withdrawal['from_address'], 
            $withdrawal['to_address'], 
            $withdrawal['amount'], 
            $tx_hash
        ]);

        if (!$history_result) {
            throw new Exception("Failed to create transaction history");
        }
    }

    // Commit transaction
    $pdo->commit();

    // Clean output buffer and send response
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Withdrawal finalized successfully',
        'tx_hash' => $tx_hash,
        'withdrawal_id' => $withdrawal_id,
        'amount' => $withdrawal['amount'],
        'status' => 'completed'
    ]);

} catch (Exception $e) {
    // Rollback transaction
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }

    // Log error
    error_log("Finalize withdrawal error: " . $e->getMessage());

    // Clean output and send error response
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'withdrawal_id' => $withdrawal_id ?? null
    ]);
}

// Ensure clean exit
exit;
?>