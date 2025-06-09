<?php
/**
 * Cache setup script for shared hosting environments
 * Run this once to create the necessary cache directories
 */

// Define cache directories
$cacheDirs = [
    '../cache',
    '../cache/trades_api',
    '../cache/chart_api',
    '../cache/trade_api',
    '../cache/rate_limits'
];

// Create directories with proper permissions
foreach ($cacheDirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "Created directory: $dir<br>";
        } else {
            echo "Failed to create directory: $dir<br>";
        }
    } else {
        echo "Directory already exists: $dir<br>";
    }
}

// Create .htaccess to protect cache files
$htaccess = <<<EOT
# Protect cache files
<FilesMatch "\.(cache|limit)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Disable directory listing
Options -Indexes
EOT;

file_put_contents('../cache/.htaccess', $htaccess);
echo "Created .htaccess protection file<br>";

// Test cache write
$testFile = '../cache/test.cache';
$testData = ['test' => true, 'time' => time()];
if (file_put_contents($testFile, serialize($testData))) {
    echo "Cache write test: SUCCESS<br>";
    @unlink($testFile); // Clean up test file
} else {
    echo "Cache write test: FAILED - Check directory permissions<br>";
}

echo "<br>Cache setup complete!";
?>
