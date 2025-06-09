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
    /* Global styles for app container */
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
    
    /* App container with proper constraints */
    .app {
        min-height: 100vh;
        max-width: 430px; /* iPhone 14 Pro Max width */
        margin: 0 auto;
        background: #000;
        padding-bottom: 85px;
        position: relative;
        border-left: 1px solid #222;
        border-right: 1px solid #222;
    }
    
    /* Main content containers with app-friendly sizing */
    .assets-container,
    .trade-container,
    .launch-container,
    .dashboard-container {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 16px 20px;
        padding-bottom: 30px;
    }
    
    /* Bottom Navigation - App-friendly design */
    .bottom-nav {
        position: fixed;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 100%;
        max-width: 430px; /* Match app container */
        background: rgba(17, 17, 17, 0.95);
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 8px 16px 12px 16px;
        border-top: 1px solid #333;
        z-index: 1000;
        height: 70px;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-left: 1px solid #222;
        border-right: 1px solid #222;
    }
    
    .nav-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #666;
        text-decoration: none;
        font-size: 10px;
        font-weight: 500;
        padding: 6px 12px;
        flex: 1;
        transition: all 0.2s ease;
        border-radius: 12px;
        position: relative;
        min-height: 50px;
    }
    
    /* Removed active background - only color change */
    .nav-btn.active {
        color: #fff;
    }
    
    .nav-btn:hover {
        color: #ccc;
    }
    
    /* Active indicator dot instead of background */
    .nav-btn.active::after {
        content: '';
        position: absolute;
        bottom: 2px;
        left: 50%;
        transform: translateX(-50%);
        width: 4px;
        height: 4px;
        background: #fff;
        border-radius: 50%;
    }
    
    .nav-icon {
        width: 22px;
        height: 22px;
        margin-bottom: 4px;
        stroke-width: 1.5;
        transition: all 0.2s ease;
    }
    
    .nav-btn.active .nav-icon {
        stroke-width: 2;
    }
    
    .nav-btn span {
        font-size: 10px;
        font-weight: 500;
        letter-spacing: 0.2px;
        text-align: center;
    }
    
    /* Header styles for app */
    .header {
        background: rgba(17, 17, 17, 0.95);
        padding: 12px 20px;
        border-bottom: 1px solid #333;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 100;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }
    
    .logo {
        font-size: 18px;
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
        font-size: 13px;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    
    .action-btn:hover {
        background: #f0f0f0;
        transform: scale(0.98);
    }
    
    /* Mobile-first responsive design */
    @media (max-width: 430px) {
        .app {
            max-width: 100vw;
            border-left: none;
            border-right: none;
        }
        
        .bottom-nav {
            max-width: 100vw;
            border-left: none;
            border-right: none;
            padding: 6px 12px 10px 12px;
        }
        
        .assets-container,
        .trade-container,
        .launch-container,
        .dashboard-container {
            padding: 12px 16px;
            padding-bottom: 25px;
        }
        
        .nav-btn {
            padding: 4px 8px;
            min-height: 48px;
        }
        
        .nav-icon {
            width: 20px;
            height: 20px;
            margin-bottom: 3px;
        }
        
        .nav-btn span {
            font-size: 9px;
        }
    }
    
    @media (max-width: 375px) {
        .app {
            padding-bottom: 80px;
        }
        
        .assets-container,
        .trade-container,
        .launch-container,
        .dashboard-container {
            padding: 10px 14px;
            padding-bottom: 20px;
        }
        
        .bottom-nav {
            height: 65px;
            padding: 4px 8px 8px 8px;
        }
        
        .nav-btn {
            padding: 3px 6px;
            min-height: 45px;
        }
        
        .nav-icon {
            width: 18px;
            height: 18px;
            margin-bottom: 2px;
        }
        
        .nav-btn span {
            font-size: 8px;
        }
        
        .header {
            padding: 10px 16px;
        }
        
        .logo {
            font-size: 16px;
        }
        
        .action-btn {
            padding: 6px 12px;
            font-size: 12px;
        }
    }
    
    /* Landscape orientation adjustments */
    @media (orientation: landscape) and (max-height: 500px) {
        .bottom-nav {
            height: 55px;
            padding: 4px 16px 6px 16px;
        }
        
        .nav-btn {
            min-height: 40px;
        }
        
        .nav-icon {
            width: 18px;
            height: 18px;
        }
        
        .nav-btn span {
            font-size: 8px;
        }
        
        .app {
            padding-bottom: 65px;
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
        margin-bottom: 15px;
    }
    
    /* Safe area for devices with notches */
    @supports (padding-bottom: env(safe-area-inset-bottom)) {
        .bottom-nav {
            padding-bottom: calc(12px + env(safe-area-inset-bottom));
        }
        
        .app {
            padding-bottom: calc(85px + env(safe-area-inset-bottom));
        }
    }
    
    /* Touch optimizations */
    .nav-btn {
        -webkit-tap-highlight-color: transparent;
        user-select: none;
        touch-action: manipulation;
    }
    
    /* Focus states for accessibility */
    .nav-btn:focus {
        outline: 2px solid rgba(255, 255, 255, 0.5);
        outline-offset: 2px;
    }
    
    /* Loading states */
    .nav-btn.loading {
        opacity: 0.6;
        pointer-events: none;
    }
    
    /* Prevent content overflow */
    .app > * {
        max-width: 100%;
        overflow-x: hidden;
    }
    
    /* Ensure proper spacing for different content types */
    .content-wrapper {
        padding-bottom: 20px;
    }
    
    /* Dark theme optimizations */
    @media (prefers-color-scheme: dark) {
        .bottom-nav {
            background: rgba(17, 17, 17, 0.98);
            border-top-color: #333;
        }
        
        .app {
            background: #000;
        }
    }
    
    /* High contrast mode support */
    @media (prefers-contrast: high) {
        .nav-btn.active {
            color: #fff;
            font-weight: 600;
        }
        
        .nav-btn.active::after {
            width: 6px;
            height: 6px;
        }
    }
    
    /* Reduced motion support */
    @media (prefers-reduced-motion: reduce) {
        .nav-btn,
        .nav-icon,
        .action-btn {
            transition: none;
        }
        
        html {
            scroll-behavior: auto;
        }
    }
</style>

<script>
// Enhanced navigation with app-friendly features
document.addEventListener('DOMContentLoaded', function() {
    const navBtns = document.querySelectorAll('.nav-btn');
    
    // Add smooth navigation transitions
    navBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Add loading state
            this.classList.add('loading');
            
            // Reset after navigation
            setTimeout(() => {
                this.classList.remove('loading');
            }, 300);
        });
    });
    
    // Handle back button navigation
    window.addEventListener('popstate', function() {
        updateActiveNav();
    });
    
    // Update active navigation state
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
    
    // Add haptic feedback to nav button clicks
    navBtns.forEach(btn => {
        btn.addEventListener('click', addHapticFeedback);
    });
    
    // Handle orientation changes
    window.addEventListener('orientationchange', function() {
        setTimeout(() => {
            // Recalculate layout after orientation change
            window.scrollTo(0, window.scrollY);
        }, 100);
    });
    
    // Optimize for app-like behavior
    let lastScrollTop = 0;
    const bottomNav = document.querySelector('.bottom-nav');
    
    // Optional: Hide nav on scroll down, show on scroll up (uncomment if needed)
    /*
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            // Scrolling down
            bottomNav.style.transform = 'translateX(-50%) translateY(100%)';
        } else {
            // Scrolling up
            bottomNav.style.transform = 'translateX(-50%) translateY(0)';
        }
        
        lastScrollTop = scrollTop;
    }, { passive: true });
    */
    
    // Ensure proper app container sizing
    function adjustAppContainer() {
        const app = document.querySelector('.app');
        if (app) {
            const viewportWidth = window.innerWidth;
            if (viewportWidth > 430) {
                app.style.maxWidth = '430px';
                app.style.margin = '0 auto';
            } else {
                app.style.maxWidth = '100vw';
                app.style.margin = '0';
            }
        }
    }
    
    // Adjust on load and resize
    adjustAppContainer();
    window.addEventListener('resize', adjustAppContainer);
    
    // Prevent zoom on double tap
    let lastTouchEnd = 0;
    document.addEventListener('touchend', function(event) {
        const now = (new Date()).getTime();
        if (now - lastTouchEnd <= 300) {
            event.preventDefault();
        }
        lastTouchEnd = now;
    }, false);
});

// Service Worker registration for app-like behavior (optional)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        // Uncomment if you have a service worker
        // navigator.serviceWorker.register('/sw.js');
    });
}
</script>