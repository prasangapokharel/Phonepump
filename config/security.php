<?php
// Security configuration and functions

// CSRF Protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting
function checkRateLimit($action, $limit = 5, $window = 300) {
    $key = $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $file = sys_get_temp_dir() . '/rate_limit_' . md5($key);
    
    $attempts = [];
    if (file_exists($file)) {
        $attempts = json_decode(file_get_contents($file), true) ?: [];
    }
    
    // Clean old attempts
    $now = time();
    $attempts = array_filter($attempts, function($timestamp) use ($now, $window) {
        return ($now - $timestamp) < $window;
    });
    
    if (count($attempts) >= $limit) {
        return false;
    }
    
    $attempts[] = $now;
    file_put_contents($file, json_encode($attempts));
    
    return true;
}

// Input validation
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

function validatePassword($password) {
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

// Encryption helpers
function encryptData($data, $key) {
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function decryptData($encryptedData, $key) {
    $data = base64_decode($encryptedData);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
}

// Audit logging
function logAuditEvent($user_id, $action, $details = null, $ip = null) {
    global $pdo;
    
    $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, $ip, $user_agent]);
    } catch (Exception $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}

// Security headers
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' cdn.tailwindcss.com cdn.jsdelivr.net; style-src \'self\' \'unsafe-inline\' cdn.tailwindcss.com; img-src \'self\' data:;');
}

// Call security headers on every page load
setSecurityHeaders();
?>
