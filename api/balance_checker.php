<?php
session_start();
require_once "../connect/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get current balance from database
    $stmt = $pdo->prepare("SELECT balance FROM trxbalance WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $current_balance = $stmt->fetchColumn();
    
    if ($current_balance === false) {
        echo json_encode(['success' => false, 'error' => 'Wallet not found']);
        exit;
    }
    
    // Get TRX price
    function getTRXPrice() {
        $api_url = 'https://api.api-ninjas.com/v1/cryptoprice?symbol=TRXUSDT';
        $api_key = 'jRN/iU++CJrVw0zkBf9tBg==ekPzRifWfQ8jCTFe';
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $api_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'X-Api-Key: ' . $api_key,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);
        
        if ($error) {
            return 0.20; // Fallback price
        }
        
        $data = json_decode($response, true);
        return isset($data['price']) ? floatval($data['price']) : 0.20;
    }
    
    $trx_price = getTRXPrice();
    $usd_value = $current_balance * $trx_price;
    
    echo json_encode([
        'success' => true,
        'balance' => floatval($current_balance),
        'usd_value' => $usd_value,
        'trx_price' => $trx_price,
        'balance_updated' => false, // Set to true if balance changed
        'new_balance' => floatval($current_balance)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
