<?php
session_start();
require_once "connect/db.php";

// Get all tokens with their data
$stmt = $pdo->prepare("
    SELECT t.*, bc.current_progress, bc.graduation_threshold, bc.real_trx_reserves,
           u.username as creator_username,
           (SELECT COUNT(DISTINCT user_id) FROM token_balances WHERE token_id = t.id) as holder_count,
           (SELECT SUM(trx_amount) FROM token_transactions WHERE token_id = t.id AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as volume_24h,
           (SELECT price_per_token FROM token_transactions WHERE token_id = t.id AND status = 'confirmed' 
            AND created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY created_at DESC LIMIT 1) as price_24h_ago
    FROM tokens t
    LEFT JOIN bonding_curves bc ON t.id = bc.token_id
    LEFT JOIN users2 u ON t.creator_id = u.id
    WHERE t.status = 'active'
    ORDER BY t.launch_time DESC
");
$stmt->execute();
$tokens = $stmt->fetchAll();

// Calculate price changes
foreach ($tokens as &$token) {
    $price_24h_ago = floatval($token['price_24h_ago'] ?? $token['current_price']);
    if ($price_24h_ago > 0) {
        $token['price_change_24h'] = (($token['current_price'] - $price_24h_ago) / $price_24h_ago) * 100;
    } else {
        $token['price_change_24h'] = 0;
    }
    
    // Calculate progress percentage
    $token['progress_percentage'] = min(100, ($token['real_trx_reserves'] / $token['graduation_threshold']) * 100);
}

// Sort options
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'launch_time';
$sort_dir = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'asc' : 'desc';

// Filter options
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
if ($filter === 'graduated') {
    $tokens = array_filter($tokens, function($token) {
        return $token['is_graduated'] == 1;
    });
} elseif ($filter === 'trending') {
    usort($tokens, function($a, $b) {
        return $b['volume_24h'] <=> $a['volume_24h'];
    });
    $tokens = array_slice($tokens, 0, 10);
}

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
if (!empty($search)) {
    $tokens = array_filter($tokens, function($token) use ($search) {
        return (stripos($token['name'], $search) !== false || 
                stripos($token['symbol'], $search) !== false ||
                stripos($token['description'], $search) !== false);
    });
}

// Pagination
$tokens_per_page = 12;
$total_tokens = count($tokens);
$total_pages = ceil($total_tokens / $tokens_per_page);
$current_page = isset($_GET['page']) ? max(1, min($total_pages, intval($_GET['page']))) : 1;
$offset = ($current_page - 1) * $tokens_per_page;
$tokens_to_display = array_slice($tokens, $offset, $tokens_per_page);

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TronPump - Token Trading Platform</title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #000;
            color: #fff;
            line-height: 1.4;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Search and filters */
        .search-section {
            margin-bottom: 30px;
        }

        .search-container {
            position: relative;
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            max-width: 400px;
            background: #111;
            border: 1px solid #333;
            color: #fff;
            padding: 12px 16px 12px 40px;
            border-radius: 8px;
            font-size: 16px;
        }

        .search-input:focus {
            outline: none;
            border-color: #fff;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: #999;
        }

        .filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-btn {
            background: #111;
            border: 1px solid #333;
            color: #999;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: #222;
            color: #fff;
            border-color: #fff;
        }

        /* Token grid */
        .token-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .token-card {
            background: #111;
            border: 1px solid #333;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.2s;
        }

        .token-card:hover {
            transform: translateY(-2px);
            border-color: #555;
        }

        .token-image-container {
            height: 200px;
            background: #222;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .token-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .token-placeholder {
            font-size: 48px;
            color: #666;
            font-weight: bold;
        }

        .price-change {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .price-up {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }

        .price-down {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .price-neutral {
            background: rgba(156, 163, 175, 0.2);
            color: #9ca3af;
        }

        .token-content {
            padding: 20px;
        }

        .token-creator {
            color: #999;
            font-size: 12px;
            margin-bottom: 8px;
        }

        .token-name {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .token-symbol {
            color: #999;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .token-description {
            color: #ccc;
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .token-progress {
            margin-bottom: 16px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 12px;
        }

        .progress-label {
            color: #999;
        }

        .progress-value {
            color: #fff;
            font-weight: 600;
        }

        .progress-bar {
            height: 4px;
            background: #333;
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #fff;
            border-radius: 2px;
            transition: width 0.3s;
        }

        .token-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .token-stat {
            background: #222;
            padding: 8px;
            border-radius: 6px;
            text-align: center;
        }

        .stat-label {
            color: #999;
            font-size: 11px;
            margin-bottom: 2px;
        }

        .stat-value {
            color: #fff;
            font-weight: 600;
            font-size: 14px;
        }

        .token-actions {
            display: flex;
            gap: 8px;
        }

        .token-btn {
            flex: 1;
            padding: 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .buy-btn {
            background: #fff;
            color: #000;
        }

        .buy-btn:hover {
            background: #f0f0f0;
        }

        .chart-btn {
            background: transparent;
            color: #fff;
            border: 1px solid #333;
        }

        .chart-btn:hover {
            background: #222;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 40px 0;
        }

        .page-btn {
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #111;
            border: 1px solid #333;
            border-radius: 6px;
            color: #999;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }

        .page-btn:hover {
            background: #222;
            color: #fff;
        }

        .page-btn.active {
            background: #fff;
            color: #000;
            border-color: #fff;
        }

        .page-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 16px;
            color: #666;
        }

        /* Icons */
        .icon {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        .icon-sm {
            width: 14px;
            height: 14px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 16px;
            }

            .token-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .filters {
                flex-direction: column;
                align-items: stretch;
            }

            .search-input {
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <!-- Search and Filters -->
        <div class="search-section">
            <div class="search-container">
                <svg class="search-icon icon" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <form method="GET" action="index.php">
                    <input type="text" name="search" class="search-input" placeholder="Search tokens..." value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>

            <div class="filters">
                <a href="index.php" class="filter-btn <?php echo empty($filter) ? 'active' : ''; ?>">All</a>
                <a href="index.php?filter=trending" class="filter-btn <?php echo $filter === 'trending' ? 'active' : ''; ?>">Trending</a>
                <a href="index.php?filter=graduated" class="filter-btn <?php echo $filter === 'graduated' ? 'active' : ''; ?>">Graduated</a>
                
                <a href="index.php?sort=market_cap&dir=desc<?php echo !empty($filter) ? '&filter=' . $filter : ''; ?>" class="filter-btn">Market Cap</a>
                <a href="index.php?sort=volume_24h&dir=desc<?php echo !empty($filter) ? '&filter=' . $filter : ''; ?>" class="filter-btn">Volume</a>
                <a href="index.php?sort=launch_time&dir=desc<?php echo !empty($filter) ? '&filter=' . $filter : ''; ?>" class="filter-btn">Latest</a>
            </div>
        </div>

        <!-- Token Grid -->
        <div class="token-grid">
            <?php if (empty($tokens_to_display)): ?>
                <div style="grid-column: 1 / -1;">
                    <div class="empty-state">
                        <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                        <h3>No tokens found</h3>
                        <p>Try adjusting your search or filters</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($tokens_to_display as $token): ?>
                    <div class="token-card">
                        <div class="token-image-container">
                            <?php if ($token['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($token['image_url']); ?>" alt="<?php echo htmlspecialchars($token['name']); ?>" class="token-image">
                            <?php else: ?>
                                <div class="token-placeholder">
                                    <?php echo substr($token['symbol'], 0, 2); ?>
                                </div>
                            <?php endif; ?>

                            <div class="price-change <?php 
                                if ($token['price_change_24h'] > 0) echo 'price-up';
                                elseif ($token['price_change_24h'] < 0) echo 'price-down';
                                else echo 'price-neutral';
                            ?>">
                                <?php if ($token['price_change_24h'] > 0): ?>
                                    <svg class="icon icon-sm" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" />
                                    </svg>
                                <?php elseif ($token['price_change_24h'] < 0): ?>
                                    <svg class="icon icon-sm" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3" />
                                    </svg>
                                <?php else: ?>
                                    <svg class="icon icon-sm" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                                    </svg>
                                <?php endif; ?>
                                <?php echo number_format(abs($token['price_change_24h']), 1); ?>%
                            </div>
                        </div>

                        <div class="token-content">
                            <div class="token-creator">
                                Created by: <?php echo htmlspecialchars($token['creator_username']); ?>
                            </div>

                            <h3 class="token-name"><?php echo htmlspecialchars($token['name']); ?></h3>
                            <div class="token-symbol"><?php echo htmlspecialchars($token['symbol']); ?></div>

                            <div class="token-description">
                                <?php echo htmlspecialchars($token['description'] ?: 'No description available.'); ?>
                            </div>

                            <div class="token-progress">
                                <div class="progress-header">
                                    <span class="progress-label">Bonding Curve</span>
                                    <span class="progress-value"><?php echo number_format($token['progress_percentage'], 1); ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $token['progress_percentage']; ?>%"></div>
                                </div>
                            </div>

                            <div class="token-stats">
                                <div class="token-stat">
                                    <div class="stat-label">Price</div>
                                    <div class="stat-value"><?php echo number_format($token['current_price'], 6); ?> TRX</div>
                                </div>
                                <div class="token-stat">
                                    <div class="stat-label">Market Cap</div>
                                    <div class="stat-value"><?php echo number_format($token['market_cap'], 0); ?> TRX</div>
                                </div>
                                <div class="token-stat">
                                    <div class="stat-label">Holders</div>
                                    <div class="stat-value"><?php echo number_format($token['holder_count']); ?></div>
                                </div>
                                <div class="token-stat">
                                    <div class="stat-label">Volume (24h)</div>
                                    <div class="stat-value"><?php echo number_format($token['volume_24h'] ?? 0, 0); ?> TRX</div>
                                </div>
                            </div>

                            <div class="token-actions">
                                <a href="user/order.php?token_id=<?php echo $token['id']; ?>" class="token-btn buy-btn">
                                    <svg class="icon icon-sm" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                                    </svg>
                                    Trade
                                </a>
                                <a href="token.php?id=<?php echo $token['id']; ?>" class="token-btn chart-btn">
                                    <svg class="icon icon-sm" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                    </svg>
                                    Chart
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <a href="?page=1<?php echo !empty($sort_by) ? '&sort=' . $sort_by : ''; ?><?php echo !empty($filter) ? '&filter=' . $filter : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-btn <?php echo $current_page == 1 ? 'disabled' : ''; ?>">
                    <svg class="icon icon-sm" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m18.75 4.5-7.5 7.5 7.5 7.5m-6-15L5.25 12l7.5 7.5" />
                    </svg>
                </a>
                <a href="?page=<?php echo max(1, $current_page - 1); ?><?php echo !empty($sort_by) ? '&sort=' . $sort_by : ''; ?><?php echo !empty($filter) ? '&filter=' . $filter : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-btn <?php echo $current_page == 1 ? 'disabled' : ''; ?>">
                    <svg class="icon icon-sm" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </a>

                <?php
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);

                for ($i = $start_page; $i <= $end_page; $i++) {
                    echo '<a href="?page=' . $i . (!empty($sort_by) ? '&sort=' . $sort_by : '') . (!empty($filter) ? '&filter=' . $filter : '') . (!empty($search) ? '&search=' . urlencode($search) : '') . '" class="page-btn ' . ($current_page == $i ? 'active' : '') . '">' . $i . '</a>';
                }
                ?>

                <a href="?page=<?php echo min($total_pages, $current_page + 1); ?><?php echo !empty($sort_by) ? '&sort=' . $sort_by : ''; ?><?php echo !empty($filter) ? '&filter=' . $filter : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-btn <?php echo $current_page == $total_pages ? 'disabled' : ''; ?>">
                    <svg class="icon icon-sm" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
                <a href="?page=<?php echo $total_pages; ?><?php echo !empty($sort_by) ? '&sort=' . $sort_by : ''; ?><?php echo !empty($filter) ? '&filter=' . $filter : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-btn <?php echo $current_page == $total_pages ? 'disabled' : ''; ?>">
                    <svg class="icon icon-sm" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
