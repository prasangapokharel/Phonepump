<?php
// Cron job to update cryptocurrency prices
require_once "../connect/db.php";

// Mock price update function
function updateCryptoPrices() {
    global $pdo;
    
    // Mock price data - in production, fetch from real API like CoinGecko
    $prices = [
        'BTC' => ['price' => 90709.85 + rand(-1000, 1000), 'change' => rand(-500, 500) / 100],
        'ETH' => ['price' => 3107.75 + rand(-100, 100), 'change' => rand(-300, 300) / 100],
        'TRX' => ['price' => 0.20 + rand(-5, 5) / 1000, 'change' => rand(-200, 200) / 100],
        'BNB' => ['price' => 626.47 + rand(-50, 50), 'change' => rand(-200, 200) / 100],
        'SOL' => ['price' => 231.45 + rand(-20, 20), 'change' => rand(-300, 300) / 100],
        'ADA' => ['price' => 0.72 + rand(-5, 5) / 100, 'change' => rand(-400, 400) / 100],
        'DOGE' => ['price' => 0.37 + rand(-2, 2) / 100, 'change' => rand(-300, 300) / 100],
        'XRP' => ['price' => 1.09 + rand(-10, 10) / 100, 'change' => rand(-250, 250) / 100],
        'AVAX' => ['price' => 36.55 + rand(-3, 3), 'change' => rand(-350, 350) / 100],
        'LINK' => ['price' => 14.09 + rand(-1, 1), 'change' => rand(-250, 250) / 100]
    ];
    
    try {
        foreach ($prices as $symbol => $data) {
            $stmt = $pdo->prepare("
                INSERT INTO crypto_prices (symbol, current_price, price_change_24h, created_at) 
                VALUES (?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE 
                current_price = VALUES(current_price), 
                price_change_24h = VALUES(price_change_24h), 
                created_at = VALUES(created_at)
            ");
            
            $stmt->execute([$symbol, $data['price'], $data['change']]);
        }
        
        echo "Prices updated successfully at " . date('Y-m-d H:i:s') . "\n";
        
    } catch (Exception $e) {
        echo "Error updating prices: " . $e->getMessage() . "\n";
    }
}

// Run the update
updateCryptoPrices();
?>
