<?php
// Installation script for TRON Wallet
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRON Wallet - Installation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        background: { dark: '#0C0C0E', card: '#1A1A1D' },
                        accent: { green: '#00FF7F', red: '#FF4C4C', yellow: '#FFD700' },
                        text: { primary: '#FFFFFF', secondary: '#AAAAAA' }
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #0C0C0E; color: #FFFFFF; font-family: 'Arial', sans-serif; }
    </style>
</head>
<body class="min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold mb-2">TRON Wallet Installation</h1>
                <p class="text-text-secondary">Follow these steps to set up your TRON wallet</p>
            </div>
            
            <div class="space-y-6">
                <!-- Step 1: Dependencies -->
                <div class="bg-background-card p-6 rounded-xl shadow-lg">
                    <h2 class="text-xl font-bold mb-4 flex items-center">
                        <span class="bg-accent-yellow text-black rounded-full w-8 h-8 flex items-center justify-center mr-3 text-sm font-bold">1</span>
                        Install Dependencies (Skip if vendor/ exists)
                    </h2>
                    <p class="text-text-secondary mb-4">If you don't have the vendor/ directory, run:</p>
                    <div class="bg-background-dark p-4 rounded-lg">
                        <code class="text-accent-green">
                            composer install
                        </code>
                    </div>
                    <div class="mt-4 p-4 bg-accent-green bg-opacity-10 border border-accent-green rounded-lg">
                        <p class="text-accent-green font-medium">ℹ️ If vendor/ directory exists, skip this step.</p>
                    </div>
                </div>
                
                <!-- Step 2: Database -->
                <div class="bg-background-card p-6 rounded-xl shadow-lg">
                    <h2 class="text-xl font-bold mb-4 flex items-center">
                        <span class="bg-accent-yellow text-black rounded-full w-8 h-8 flex items-center justify-center mr-3 text-sm font-bold">2</span>
                        Setup Database
                    </h2>
                    <p class="text-text-secondary mb-4">Create the database and import the schema:</p>
                    <div class="bg-background-dark p-4 rounded-lg mb-4">
                        <code class="text-accent-green">
                            # Create database<br>
                            mysql -u root -p -e "CREATE DATABASE tron_wallet"<br><br>
                            # Import schema<br>
                            mysql -u root -p tron_wallet < database/schema.sql
                        </code>
                    </div>
                    <p class="text-text-secondary">Or use phpMyAdmin to import <code class="text-accent-yellow">database/schema.sql</code></p>
                </div>
                
                <!-- Step 3: Configuration -->
                <div class="bg-background-card p-6 rounded-xl shadow-lg">
                    <h2 class="text-xl font-bold mb-4 flex items-center">
                        <span class="bg-accent-yellow text-black rounded-full w-8 h-8 flex items-center justify-center mr-3 text-sm font-bold">3</span>
                        Configure Database Connection
                    </h2>
                    <p class="text-text-secondary mb-4">Update database settings in <code class="text-accent-yellow">connect/db.php</code>:</p>
                    <div class="bg-background-dark p-4 rounded-lg">
                        <code class="text-accent-green">
                            $host = 'localhost';<br>
                            $dbname = 'tron_wallet';<br>
                            $username = 'root';<br>
                            $password = 'your_password';
                        </code>
                    </div>
                </div>
                
                <!-- Step 4: Test -->
                <div class="bg-background-card p-6 rounded-xl shadow-lg">
                    <h2 class="text-xl font-bold mb-4 flex items-center">
                        <span class="bg-accent-yellow text-black rounded-full w-8 h-8 flex items-center justify-center mr-3 text-sm font-bold">4</span>
                        Test Installation
                    </h2>
                    <p class="text-text-secondary mb-4">Run the system check to verify everything works:</p>
                    <div class="flex gap-4">
                        <a href="mobile_check.php" class="bg-accent-yellow text-black px-6 py-3 rounded-lg font-medium hover:bg-opacity-80 transition">
                            Run System Check
                        </a>
                        <a href="index.php" class="bg-accent-green text-black px-6 py-3 rounded-lg font-medium hover:bg-opacity-80 transition">
                            Go to Wallet
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Troubleshooting -->
            <div class="mt-12">
                <h2 class="text-2xl font-bold mb-6 text-center">Troubleshooting</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-background-card p-4 rounded-lg">
                        <h3 class="font-bold mb-2 text-accent-red">Composer Issues</h3>
                        <p class="text-text-secondary text-sm mb-2">If you get dependency conflicts:</p>
                        <div class="bg-background-dark p-2 rounded text-xs">
                            <code class="text-accent-green">
                                # Use existing vendor/ directory<br>
                                # Don't run composer install
                            </code>
                        </div>
                    </div>
                    <div class="bg-background-card p-4 rounded-lg">
                        <h3 class="font-bold mb-2 text-accent-yellow">Database Connection</h3>
                        <p class="text-text-secondary text-sm mb-2">If database connection fails:</p>
                        <div class="bg-background-dark p-2 rounded text-xs">
                            <code class="text-accent-green">
                                # Check connect/db.php<br>
                                # Verify MySQL is running<br>
                                # Check credentials
                            </code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
