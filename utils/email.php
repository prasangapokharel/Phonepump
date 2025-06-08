<?php
// Email utility functions with working PHPMailer integration

// Try to include PHPMailer if available
$phpmailer_loaded = false;

// Method 1: Try composer autoload
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        $phpmailer_loaded = true;
    }
}

// Method 2: Try direct PHPMailer files
if (!$phpmailer_loaded && file_exists(__DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        $phpmailer_loaded = true;
    }
}

class EmailService {
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $from_email;
    private $from_name;
    private $use_phpmailer;
    
    public function __construct() {
        // Configure SMTP settings - Update these with your actual SMTP settings
        $this->smtp_host = 'smtp.hostinger.com';
        $this->smtp_port = 465; // SSL port
        $this->smtp_username = 'phn@phonesium.space';
        $this->smtp_password = 'Y1]r&ePF~/k';
        $this->from_email = 'phn@phonesium.space';
        $this->from_name = 'TRON Wallet';
        
        // Check if PHPMailer is available
        global $phpmailer_loaded;
        $this->use_phpmailer = $phpmailer_loaded && class_exists('PHPMailer\\PHPMailer\\PHPMailer');
        
        // Log initialization
        error_log("EmailService initialized. PHPMailer available: " . ($this->use_phpmailer ? 'Yes' : 'No'));
    }
    
    public function sendOTP($to_email, $otp, $purpose = 'withdrawal') {
        $subject = 'TRON Wallet - Verification Code';
        $message = $this->getOTPTemplate($otp, $purpose);
        
        // Log the attempt
        error_log("Attempting to send OTP to: $to_email, OTP: $otp, Purpose: $purpose");
        
        $result = $this->sendEmail($to_email, $subject, $message);
        
        // Log the result
        error_log("OTP Email result for $to_email: " . ($result ? 'SUCCESS' : 'FAILED'));
        
        return $result;
    }
    
    public function sendWelcomeEmail($to_email, $username) {
        $subject = 'Welcome to TRON Wallet';
        $message = $this->getWelcomeTemplate($username);
        
        error_log("Sending welcome email to: $to_email");
        return $this->sendEmail($to_email, $subject, $message);
    }
    
    public function sendTransactionNotification($to_email, $type, $amount, $tx_id) {
        $subject = 'TRON Wallet - Transaction Notification';
        $message = $this->getTransactionTemplate($type, $amount, $tx_id);
        
        error_log("Sending transaction notification to: $to_email, Type: $type, Amount: $amount");
        return $this->sendEmail($to_email, $subject, $message);
    }
    
    private function sendEmail($to, $subject, $message) {
        if ($this->use_phpmailer) {
            return $this->sendWithPHPMailer($to, $subject, $message);
        } else {
            return $this->sendWithNativePHP($to, $subject, $message);
        }
    }
    
    private function sendWithPHPMailer($to, $subject, $message) {
        try {
            // Create PHPMailer instance using full class name
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Enable verbose debug output (remove in production)
            $mail->SMTPDebug = 0; // Set to 2 for debugging
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer Debug: $str");
            };
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // SSL encryption
            $mail->Port = $this->smtp_port;
            
            // Disable SSL verification for development (remove in production)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Set timeout
            $mail->Timeout = 30;
            
            // Recipients
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = strip_tags($message); // Plain text version
            
            // Send the email
            $result = $mail->send();
            
            if ($result) {
                error_log("PHPMailer: Email sent successfully to $to");
                return true;
            } else {
                error_log("PHPMailer: Failed to send email to $to");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $e->getMessage());
            if (isset($mail)) {
                error_log("PHPMailer ErrorInfo: " . $mail->ErrorInfo);
            }
            return false;
        }
    }
    
    private function sendWithNativePHP($to, $subject, $message) {
        try {
            error_log("Using native PHP mail for: $to");
            
            // Fallback to native PHP mail with SMTP headers
            $headers = array();
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-type: text/html; charset=UTF-8";
            $headers[] = "From: {$this->from_name} <{$this->from_email}>";
            $headers[] = "Reply-To: {$this->from_email}";
            $headers[] = "X-Mailer: PHP/" . phpversion();
            $headers[] = "X-Priority: 1";
            
            $result = mail($to, $subject, $message, implode("\r\n", $headers));
            
            if ($result) {
                error_log("Native PHP Mail: Email sent successfully to $to");
                return true;
            } else {
                error_log("Native PHP Mail: Failed to send email to $to");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Native PHP Mail Error: " . $e->getMessage());
            return false;
        }
    }
    
    // Test email connectivity
    public function testConnection() {
        if (!$this->use_phpmailer) {
            return array('success' => false, 'message' => 'PHPMailer not available, using native PHP mail');
        }
        
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $this->smtp_port;
            $mail->Timeout = 10;
            
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Test connection without sending
            if ($mail->smtpConnect()) {
                $mail->smtpClose();
                return array('success' => true, 'message' => 'SMTP connection successful');
            } else {
                return array('success' => false, 'message' => 'SMTP connection failed');
            }
            
        } catch (Exception $e) {
            return array('success' => false, 'message' => 'SMTP connection failed: ' . $e->getMessage());
        }
    }
    
    // Test sending a simple email
    public function testEmail($to_email) {
        $subject = 'TRON Wallet - Test Email';
        $message = $this->getTestTemplate();
        
        error_log("Testing email to: $to_email");
        return $this->sendEmail($to_email, $subject, $message);
    }
    
    private function getOTPTemplate($otp, $purpose) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
                    background-color: #0C0C0E; 
                    color: #FFFFFF; 
                    margin: 0; 
                    padding: 0;
                    line-height: 1.6;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    padding: 20px; 
                }
                .header { 
                    background-color: #1A1A1D; 
                    padding: 30px 20px; 
                    text-align: center; 
                    border-radius: 8px 8px 0 0; 
                }
                .content { 
                    background-color: #1A1A1D; 
                    padding: 30px; 
                    border-radius: 0 0 8px 8px; 
                }
                .otp { 
                    font-size: 36px; 
                    font-weight: bold; 
                    color: #FFD700; 
                    text-align: center; 
                    padding: 25px; 
                    background-color: #0C0C0E; 
                    border-radius: 8px; 
                    margin: 25px 0; 
                    letter-spacing: 8px;
                    border: 2px solid #FFD700;
                }
                .footer { 
                    text-align: center; 
                    margin-top: 20px; 
                    color: #AAAAAA; 
                    font-size: 12px; 
                }
                .warning {
                    background-color: #FF4C4C;
                    color: white;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 20px 0;
                    text-align: center;
                    font-weight: bold;
                }
                .logo {
                    font-size: 24px;
                    font-weight: bold;
                    margin-bottom: 10px;
                }
                ul {
                    text-align: left;
                    padding-left: 20px;
                }
                li {
                    margin-bottom: 8px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>🔐 TRON Wallet</div>
                    <h1 style='margin: 0; color: #FFD700;'>Security Verification</h1>
                </div>
                <div class='content'>
                    <h2 style='color: #FFFFFF; margin-top: 0;'>Verification Code Required</h2>
                    <p>Your verification code for <strong>" . ucfirst($purpose) . "</strong> is:</p>
                    <div class='otp'>$otp</div>
                    <div class='warning'>
                        ⚠️ This code expires in 5 minutes
                    </div>
                    <p><strong>Security Notice:</strong></p>
                    <ul>
                        <li>Never share this code with anyone</li>
                        <li>Our team will never ask for this code</li>
                        <li>If you didn't request this, please ignore this email</li>
                        <li>This code can only be used once</li>
                    </ul>
                    <p style='margin-top: 30px; padding: 15px; background-color: #0C0C0E; border-radius: 5px; font-size: 14px;'>
                        <strong>Time:</strong> " . date('M j, Y H:i:s T') . "<br>
                        <strong>Purpose:</strong> " . ucfirst($purpose) . " verification
                    </p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " TRON Wallet. All rights reserved.</p>
                    <p>This is an automated message, please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getTestTemplate() {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    background-color: #0C0C0E; 
                    color: #FFFFFF; 
                    margin: 0; 
                    padding: 20px;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    background-color: #1A1A1D;
                    padding: 30px;
                    border-radius: 8px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <h1>🧪 TRON Wallet Test Email</h1>
                <p>This is a test email to verify that the email system is working correctly.</p>
                <p><strong>Time:</strong> " . date('M j, Y H:i:s T') . "</p>
                <p>If you received this email, the email configuration is working properly!</p>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getWelcomeTemplate($username) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
                    background-color: #0C0C0E; 
                    color: #FFFFFF; 
                    margin: 0; 
                    padding: 0; 
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    padding: 20px; 
                }
                .header { 
                    background-color: #1A1A1D; 
                    padding: 30px 20px; 
                    text-align: center; 
                    border-radius: 8px 8px 0 0; 
                }
                .content { 
                    background-color: #1A1A1D; 
                    padding: 30px; 
                    border-radius: 0 0 8px 8px; 
                }
                .button { 
                    background-color: #FFD700; 
                    color: #000000; 
                    padding: 15px 30px; 
                    text-decoration: none; 
                    border-radius: 6px; 
                    display: inline-block; 
                    margin: 20px 0;
                    font-weight: bold;
                }
                .footer { 
                    text-align: center; 
                    margin-top: 20px; 
                    color: #AAAAAA; 
                    font-size: 12px; 
                }
                ul {
                    padding-left: 20px;
                }
                li {
                    margin-bottom: 8px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin: 0; color: #FFD700;'>🎉 Welcome to TRON Wallet</h1>
                </div>
                <div class='content'>
                    <h2 style='color: #FFFFFF; margin-top: 0;'>Hello $username!</h2>
                    <p>Welcome to TRON Wallet - your secure gateway to the TRON ecosystem.</p>
                    <p>You can now:</p>
                    <ul>
                        <li>✅ Create and manage your TRON wallet</li>
                        <li>💸 Send and receive TRX</li>
                        <li>📊 Monitor real-time market prices</li>
                        <li>📈 Track your transaction history</li>
                        <li>🔒 Secure transactions with email verification</li>
                    </ul>
                    <p style='margin-top: 30px;'>Get started by creating your first wallet:</p>
                    <a href='#' class='button'>Access Your Wallet</a>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " TRON Wallet. All rights reserved.</p>
                    <p>Need help? Contact our support team.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getTransactionTemplate($type, $amount, $tx_id) {
        $title = $type === 'send' ? 'Transaction Sent' : 'Transaction Received';
        $color = $type === 'send' ? '#FF4C4C' : '#00FF7F';
        $icon = $type === 'send' ? '📤' : '📥';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
                    background-color: #0C0C0E; 
                    color: #FFFFFF; 
                    margin: 0; 
                    padding: 0; 
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    padding: 20px; 
                }
                .header { 
                    background-color: #1A1A1D; 
                    padding: 30px 20px; 
                    text-align: center; 
                    border-radius: 8px 8px 0 0; 
                }
                .content { 
                    background-color: #1A1A1D; 
                    padding: 30px; 
                    border-radius: 0 0 8px 8px; 
                }
                .amount { 
                    font-size: 28px; 
                    font-weight: bold; 
                    color: $color; 
                    text-align: center; 
                    margin: 25px 0; 
                    padding: 20px;
                    background-color: #0C0C0E;
                    border-radius: 8px;
                }
                .tx-details { 
                    background-color: #0C0C0E; 
                    padding: 20px; 
                    border-radius: 8px; 
                    margin: 20px 0; 
                }
                .footer { 
                    text-align: center; 
                    margin-top: 20px; 
                    color: #AAAAAA; 
                    font-size: 12px; 
                }
                .detail-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 10px;
                    padding: 5px 0;
                    border-bottom: 1px solid #333;
                }
                .detail-label {
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin: 0; color: #FFD700;'>$icon $title</h1>
                </div>
                <div class='content'>
                    <div class='amount'>$amount TRX</div>
                    <div class='tx-details'>
                        <div class='detail-row'>
                            <span class='detail-label'>Transaction ID:</span>
                            <span>$tx_id</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Date:</span>
                            <span>" . date('M j, Y H:i:s T') . "</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Status:</span>
                            <span style='color: #00FF7F;'>✅ Completed</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Network:</span>
                            <span>TRON (TRX)</span>
                        </div>
                    </div>
                    <p>You can view this transaction in your wallet dashboard.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " TRON Wallet. All rights reserved.</p>
                    <p>This is an automated notification.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

// Function to create EmailService instance (for backward compatibility)
function createEmailService() {
    return new EmailService();
}

// Test function to verify email setup
function testEmailSetup($test_email = null) {
    $emailService = new EmailService();
    
    echo "Testing Email Configuration...\n";
    echo "================================\n";
    
    // Test connection
    $connectionTest = $emailService->testConnection();
    echo "Connection Test: " . ($connectionTest['success'] ? 'PASS' : 'FAIL') . "\n";
    echo "Message: " . $connectionTest['message'] . "\n\n";
    
    // Test email sending if test email provided
    if ($test_email) {
        echo "Testing email sending to: $test_email\n";
        $emailTest = $emailService->testEmail($test_email);
        echo "Email Test: " . ($emailTest ? 'PASS' : 'FAIL') . "\n\n";
        
        // Test OTP sending
        echo "Testing OTP sending...\n";
        $otpTest = $emailService->sendOTP($test_email, '123456', 'test');
        echo "OTP Test: " . ($otpTest ? 'PASS' : 'FAIL') . "\n";
    }
    
    return $emailService;
}
?>