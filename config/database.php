<?php
// Enhanced database configuration with environment variables
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'tron_wallet';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? '';
    
    // Create PDO instance with better error handling
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Test connection
    $pdo->query("SELECT 1");
    
} catch(PDOException $e) {
    // Log error securely
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Show user-friendly error in development
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die("Service temporarily unavailable. Please try again later.");
    }
}

// Enhanced security functions
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Database health check function
function checkDatabaseHealth() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT 1");
        return $stmt !== false;
    } catch (Exception $e) {
        return false;
    }
}
?>
