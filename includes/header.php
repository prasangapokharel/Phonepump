<?php
// Get user info if logged in
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;

// Get user wallet if logged in
$wallet = null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM trxbalance WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $wallet = $stmt->fetch();
}
?>

<header style="background: #111; border-bottom: 1px solid #333; padding: 16px 0;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <a href="/" style="color: #fff; text-decoration: none; font-size: 20px; font-weight: 700;">
                TronPump
            </a>
            
            <nav style="display: flex; gap: 20px;">
                <a href="/" style="color: #999; text-decoration: none; font-size: 14px; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#999'">Home</a>
                <a href="/user/trade.php" style="color: #999; text-decoration: none; font-size: 14px; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#999'">Trade</a>
                <a href="/user/launch.php" style="color: #999; text-decoration: none; font-size: 14px; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#999'">Launch</a>
                <a href="/user/assets.php" style="color: #999; text-decoration: none; font-size: 14px; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#999'">Assets</a>
            </nav>
        </div>
        
        <div style="display: flex; align-items: center; gap: 12px;">
            <?php if ($user_id): ?>
                <span style="color: #999; font-size: 14px;">
                    <?php echo number_format($wallet ? $wallet['balance'] : 0, 2); ?> TRX
                </span>
                <a href="/user/assets.php" style="background: #fff; color: #000; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px;">
                    <?php echo htmlspecialchars($username); ?>
                </a>
            <?php else: ?>
                <a href="/login.php" style="background: #fff; color: #000; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px;">
                    Connect Wallet
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<style>
@media (max-width: 768px) {
    header nav {
        display: none !important;
    }
    
    header > div {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
    }
    
    header > div > div:first-child {
        justify-content: center;
    }
    
    header > div > div:last-child {
        justify-content: center;
    }
}
</style>
