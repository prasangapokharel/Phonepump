<!-- Bottom Navigation -->
<div class="bottom-nav">
    <a href="../user/dashboard.php" class="nav-btn <?php echo (strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false) ? 'active' : ''; ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
        <span>Home</span>
    </a>
    <a href="../user/trade.php" class="nav-btn <?php echo (strpos($_SERVER['PHP_SELF'], 'trade.php') !== false) ? 'active' : ''; ?>">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
        </svg>
        <span>Trade</span>
    </a>
    <a href="../user/launch.php" class="nav-btn <?php echo (strpos($_SERVER['PHP_SELF'], 'launch.php') !== false) ? 'active' : ''; ?>">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694 4.125-8.25 4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
        </svg>
        <span>Launch</span>
    </a>
    <a href="../user/assets.php" class="nav-btn <?php echo (strpos($_SERVER['PHP_SELF'], 'assets.php') !== false) ? 'active' : ''; ?>">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3" />
        </svg>
        <span>Assets</span>
    </a>
</div>

<style>
    /* Global styles for all pages */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    html, body {
        height: 100%;
        overflow-x: hidden;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: #000;
        color: #fff;
        line-height: 1.4;
    }
    
    /* Hide scrollbars but keep functionality */
    body {
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* Internet Explorer 10+ */
    }
    
    body::-webkit-scrollbar {
        width: 0;
        height: 0;
        background: transparent; /* Chrome/Safari/Webkit */
    }
    
    /* Ensure all containers have proper spacing for bottom nav */
    .app {
        min-height: 100vh;
        background: #000;
        padding-bottom: 100px; /* Increased from 80px to 100px */
        position: relative;
    }
    
    /* Main content containers */
    .assets-container,
    .trade-container,
    .launch-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 16px;
        padding-bottom: 40px; /* Increased from 20px to 40px */
    }
    
    /* Bottom Navigation */
    .bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #111;
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 12px 0 8px 0;
        border-top: 1px solid #333;
        z-index: 1000;
        height: 70px;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    
    .nav-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #666;
        text-decoration: none;
        font-size: 11px;
        font-weight: 500;
        padding: 4px 8px;
        width: 25%;
        transition: all 0.2s ease;
        border-radius: 8px;
    }
    
    .nav-btn.active {
        color: #fff;
        background: rgba(255, 255, 255, 0.1);
    }
    
    .nav-btn:hover {
        color: #ccc;
    }
    
    .nav-icon {
        width: 24px;
        height: 24px;
        margin-bottom: 4px;
        stroke-width: 1.5;
    }
    
    .nav-btn span {
        font-size: 11px;
        font-weight: 500;
        letter-spacing: 0.3px;
    }
    
    /* Header styles */
    .header {
        background: #111;
        padding: 16px 20px;
        border-bottom: 1px solid #333;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 100;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    
    .logo {
        font-size: 20px;
        font-weight: 600;
        color: #fff;
    }
    
    .action-btn {
        background: #fff;
        color: #000;
        padding: 8px 16px;
        border: none;
        font-weight: 500;
        text-decoration: none;
        font-size: 14px;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    
    .action-btn:hover {
        background: #f0f0f0;
    }
    
    /* Responsive design */
    @media (max-width: 480px) {
        .assets-container,
        .trade-container,
        .launch-container {
            padding: 12px;
            padding-bottom: 50px; /* Increased for mobile */
        }
        
        .header {
            padding: 12px 16px;
        }
        
        .logo {
            font-size: 18px;
        }
        
        .action-btn {
            padding: 6px 12px;
            font-size: 13px;
        }
        
        .bottom-nav {
            height: 65px;
            padding: 8px 0 6px 0;
        }
        
        .app {
            padding-bottom: 110px; /* Increased for mobile */
        }
        
        .nav-icon {
            width: 22px;
            height: 22px;
            margin-bottom: 3px;
        }
        
        .nav-btn span {
            font-size: 10px;
        }
        
        .nav-btn {
            padding: 3px 6px;
        }
    }
    
    @media (max-width: 360px) {
        .app {
            padding-bottom: 120px; /* Even more space for smaller screens */
        }
        
        .assets-container,
        .trade-container,
        .launch-container {
            padding-bottom: 60px;
        }
    }
    
    /* Smooth scrolling */
    html {
        scroll-behavior: smooth;
    }
    
    /* Modal and overlay z-index management */
    .trade-modal,
    .modal {
        z-index: 2000;
    }
    
    /* Ensure content doesn't get cut off */
    .token-grid,
    .token-list,
    .transactions-list {
        margin-bottom: 20px;
    }
    
    /* Safe area for devices with notches */
    @supports (padding-bottom: env(safe-area-inset-bottom)) {
        .bottom-nav {
            padding-bottom: calc(8px + env(safe-area-inset-bottom));
        }
        
        .app {
            padding-bottom: calc(100px + env(safe-area-inset-bottom));
        }
    }
    
    /* Loading states and animations */
    .nav-btn {
        -webkit-tap-highlight-color: transparent;
        user-select: none;
    }
    
    /* Focus states for accessibility */
    .nav-btn:focus {
        outline: 2px solid #fff;
        outline-offset: 2px;
    }
    
    /* Dark theme optimizations */
    @media (prefers-color-scheme: dark) {
        .bottom-nav {
            background: #111;
            border-top-color: #333;
        }
    }
</style>

<script>
// Add smooth navigation transitions
document.addEventListener('DOMContentLoaded', function() {
    // Add active state management
    const navBtns = document.querySelectorAll('.nav-btn');
    
    navBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Add loading state
            this.style.opacity = '0.7';
            
            // Reset after navigation
            setTimeout(() => {
                this.style.opacity = '1';
            }, 200);
        });
    });
    
    // Handle back button navigation
    window.addEventListener('popstate', function() {
        // Update active states based on current URL
        updateActiveNav();
    });
    
    function updateActiveNav() {
        const currentPath = window.location.pathname;
        navBtns.forEach(btn => {
            const href = btn.getAttribute('href');
            if (currentPath.includes(href.replace('../', ''))) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }
});

// Prevent scroll bounce on iOS
document.addEventListener('touchmove', function(e) {
    if (e.target.closest('.bottom-nav')) {
        e.preventDefault();
    }
}, { passive: false });

// Add haptic feedback for supported devices
function addHapticFeedback() {
    if ('vibrate' in navigator) {
        navigator.vibrate(10);
    }
}

// Add to nav button clicks
document.querySelectorAll('.nav-btn').forEach(btn => {
    btn.addEventListener('click', addHapticFeedback);
});
</script>
