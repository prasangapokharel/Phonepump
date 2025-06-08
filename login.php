<?php
// Start session
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: user/dashboard.php");
    exit;
}

// Include database connection
require_once "connect/db.php";

$error = "";

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request";
    } else {
        // Get form data
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        
        // Validate input
        if (empty($username) || empty($password)) {
            $error = "Username and password are required";
        } else {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, username, password FROM users2 WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // Update last login date
                $stmt = $pdo->prepare("UPDATE users2 SET last_login_date = CURRENT_DATE() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Redirect to dashboard
                header("Location: user/dashboard.php");
                exit;
            } else {
                $error = "Invalid username or password";
            }
        }
    }
}

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TRON Wallet</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        background: {
                            dark: '#0C0C0E',
                            card: '#1A1A1D'
                        },
                        accent: {
                            green: '#00FF7F',
                            red: '#FF4C4C',
                            yellow: '#FFD700'
                        },
                        text: {
                            primary: '#FFFFFF',
                            secondary: '#AAAAAA'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #0C0C0E;
            color: #FFFFFF;
            font-family: 'Arial', sans-serif;
        }
        .hero-pattern {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%23222' fill-opacity='0.4'%3E%3Cpath opacity='.5' d='M96 95h4v1h-4v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9zm-1 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9z'/%3E%3Cpath d='M6 5V0H5v5H0v1h5v94h1V6h94V5H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
    </style>
</head>
<body class="min-h-screen hero-pattern">
    <div class="container mx-auto px-4 py-12">
        <header class="flex justify-between items-center mb-12">
            <div class="flex items-center">
                <svg class="w-10 h-10 text-accent-yellow" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M15 9L9 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9 9L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <a href="index.php" class="text-2xl font-bold ml-2">TRON Wallet</a>
            </div>
        </header>
        
        <main class="flex justify-center">
            <div class="bg-background-card p-8 rounded-xl shadow-lg max-w-md w-full">
                <h2 class="text-2xl font-bold mb-6 text-center">Login to Your Wallet</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="bg-red-800 bg-opacity-50 text-white p-3 rounded-lg mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="mb-4">
                        <label for="username" class="block text-text-secondary mb-1">Username</label>
                        <input type="text" id="username" name="username" class="w-full bg-background-dark border border-gray-700 rounded-lg px-4 py-2 focus:outline-none focus:border-accent-yellow" required>
                    </div>
                    
                    <div class="mb-6">
                        <label for="password" class="block text-text-secondary mb-1">Password</label>
                        <input type="password" id="password" name="password" class="w-full bg-background-dark border border-gray-700 rounded-lg px-4 py-2 focus:outline-none focus:border-accent-yellow" required>
                    </div>
                    
                    <button type="submit" class="w-full bg-accent-yellow text-black py-3 rounded-lg font-medium hover:bg-opacity-80 transition">Login</button>
                </form>
                
                <div class="mt-4 text-center text-text-secondary">
                    Don't have an account? <a href="register.php" class="text-accent-yellow hover:underline">Register</a>
                </div>
            </div>
        </main>
        
    </div>
</body>
</html>
