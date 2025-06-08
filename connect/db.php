<?php
// Database connection configuration
try {
    $host = 'localhost';
    $dbname = 'tron_wallet';
    $username = 'root';
    $password = '';
    
    // Create PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Prevent SQL injection
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    // Log error instead of displaying it to users in production
    error_log("Database Connection Error: " . $e->getMessage());
    die("Connection failed. Please try again later.");
}

// Security function to sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to generate a secure random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Function to verify CSRF token
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}
