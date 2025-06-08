<?php
// API endpoint for address validation
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once "../components/wallet_generator.php";

$address = $_GET['address'] ?? $_POST['address'] ?? '';

if (empty($address)) {
    echo json_encode([
        'success' => false,
        'valid' => false,
        'error' => 'Address parameter required'
    ]);
    exit;
}

try {
    $isValid = TronWalletGenerator::validateAddress($address);
    
    echo json_encode([
        'success' => true,
        'valid' => $isValid,
        'address' => $address,
        'type' => $isValid ? 'TRON' : 'Invalid'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'valid' => false,
        'error' => $e->getMessage()
    ]);
}
?>
