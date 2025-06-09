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

// Get TRX price from cache if available
$trx_price = 0.067; // Default fallback price
if (function_exists('getTRXPriceWithCache') && isset($httpClient) && isset($cache)) {
    $trx_price = getTRXPriceWithCache($httpClient, $cache);
}
?>

<header class="main-header">
    <div class="header-container">
        <div class="header-left">
            <a href="/" class="logo">
                <img src="/assets/image/logo.png" alt="Phonesium Logo" class="logo-icon">
                <span>Phonesium</span>
            </a>
            
            <nav class="main-nav">
                <a href="/" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Home</a>
                <a href="/user/trade.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'trade.php' ? 'active' : ''; ?>">Trade</a>
                <a href="/user/launch.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'launch.php' ? 'active' : ''; ?>">Launch</a>
                <a href="/user/assets.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'assets.php' ? 'active' : ''; ?>">Assets</a>
            </nav>
        </div>
        
        <div class="header-right">
            <?php if ($user_id): ?>
                <div class="user-balance">
                    <div class="balance-amount"><?php echo number_format($wallet ? $wallet['balance'] : 0, 2); ?> TRX</div>
                    <div class="balance-usd">≈ $<?php echo number_format(($wallet ? $wallet['balance'] : 0) * $trx_price, 2); ?></div>
                </div>
                <div class="user-menu">
                    <a href="/user/assets.php" class="user-button">
                        <div class="user-avatar">
                            <?php echo substr($username, 0, 1); ?>
                        </div>
                        <span class="username"><?php echo htmlspecialchars($username); ?></span>
                    </a>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="/login.php" class="login-button">Login</a>
                    <a href="/signup.php" class="signup-button">Sign Up</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Mobile Navigation -->
    <div class="mobile-nav-container">
        <div class="mobile-nav">
            <a href="/" class="mobile-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9 22V12h6v10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Home</span>
            </a>
            <a href="/user/trade.php" class="mobile-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'trade.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 20V10M18 20V4M6 20v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Trade</span>
            </a>
            <a href="/user/launch.php" class="mobile-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'launch.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Launch</span>
            </a>
            <a href="/user/assets.php" class="mobile-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'assets.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 5h-7l-2-3H5C3.9 2 3 2.9 3 4v16c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Assets</span>
            </a>
            <?php if ($user_id): ?>
                <a href="/user/profile.php" class="mobile-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <div class="mobile-avatar">
                        <?php echo substr($username, 0, 1); ?>
                    </div>
                    <span>Profile</span>
                </a>
            <?php else: ?>
                <a href="/login.php" class="mobile-nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Login</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<style>
/* Premium Header Styles */
.main-header {
    background: rgba(17, 17, 17, 0.8);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(51, 51, 51, 0.8);
    position: sticky;
    top: 0;
    z-index: 1000;
    padding: 16px 0;
    transition: all 0.3s ease;
}

.header-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 40px;
}

.logo {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #fff;
    text-decoration: none;
    font-size: 22px;
    font-weight: 700;
    transition: opacity 0.2s;
}

.logo:hover {
    opacity: 0.9;
}

.logo-icon {
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: inline-block;
    width: 24px;
    height: 24px;
}

.main-nav {
    display: flex;
    gap: 24px;
}

.nav-link {
    color: #999;
    text-decoration: none;
    font-size: 15px;
    font-weight: 500;
    padding: 6px 0;
    position: relative;
    transition: color 0.2s;
}

.nav-link:hover {
    color: #fff;
}

.nav-link.active {
    color: #fff;
}

.nav-link.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, #fff, rgba(255,255,255,0.5));
    border-radius: 1px;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.user-balance {
    text-align: right;
    margin-right: 8px;
}

.balance-amount {
    color: #fff;
    font-weight: 600;
    font-size: 14px;
    font-family: 'SF Mono', Monaco, monospace;
}

.balance-usd {
    color: #666;
    font-size: 12px;
}

.user-menu {
    position: relative;
}

.user-button {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 40px;
    padding: 6px 12px 6px 6px;
    color: #fff;
    text-decoration: none;
    transition: all 0.2s;
}

.user-button:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.2);
}

.user-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
    color: #fff;
}

.username {
    font-size: 14px;
    font-weight: 500;
    max-width: 100px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.auth-buttons {
    display: flex;
    gap: 8px;
}

.login-button {
    padding: 8px 16px;
    border-radius: 6px;
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #fff;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
}

.login-button:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.3);
}

.signup-button {
    padding: 8px 16px;
    border-radius: 6px;
    background: #fff;
    color: #000;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.signup-button:hover {
    background: rgba(255, 255, 255, 0.9);
}

/* Mobile Navigation */
.mobile-nav-container {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background: rgba(17, 17, 17, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-top: 1px solid rgba(51, 51, 51, 0.8);
    padding: 8px 0;
}

.mobile-nav {
    display: flex;
    justify-content: space-around;
    align-items: center;
}

.mobile-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    color: #999;
    text-decoration: none;
    font-size: 12px;
    padding: 8px;
    border-radius: 8px;
    transition: all 0.2s;
}

.mobile-nav-item.active {
    color: #fff;
}

.mobile-nav-item svg {
    width: 20px;
    height: 20px;
}

.mobile-avatar {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 12px;
    color: #fff;
}

/* Responsive Styles */
@media (max-width: 768px) {
    .main-nav {
        display: none;
    }
    
    .mobile-nav-container {
        display: block;
    }
    
    .header-container {
        padding: 0 16px;
    }
    
    .user-balance {
        display: none;
    }
    
    .username {
        display: none;
    }
    
    .user-button {
        padding: 6px;
    }
    
    .auth-buttons {
        gap: 6px;
    }
    
    .login-button, .signup-button {
        padding: 6px 12px;
        font-size: 13px;
    }
}

@media (max-width: 480px) {
    .logo span {
        display: none;
    }
    
    .header-left {
        gap: 20px;
    }
}
</style>
