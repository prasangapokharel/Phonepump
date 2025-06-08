<?php
use PHPUnit\Framework\TestCase;

class WalletTest extends TestCase
{
    private $pdo;
    
    protected function setUp(): void
    {
        // Set up test database connection
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create test tables
        $this->createTestTables();
    }
    
    private function createTestTables()
    {
        $sql = "
        CREATE TABLE users2 (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            PH_id VARCHAR(20) UNIQUE NOT NULL
        );
        
        CREATE TABLE trxbalance (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            private_key TEXT NOT NULL,
            address VARCHAR(34) UNIQUE NOT NULL,
            username VARCHAR(50) NOT NULL,
            balance DECIMAL(20,6) DEFAULT 0.000000
        );
        ";
        
        $this->pdo->exec($sql);
    }
    
    public function testWalletGeneration()
    {
        require_once __DIR__ . '/../components/wallet_generator.php';
        
        $wallet = TronWalletGenerator::generateWallet();
        
        $this->assertTrue($wallet['success']);
        $this->assertNotEmpty($wallet['address']);
        $this->assertNotEmpty($wallet['private_key']);
        $this->assertStringStartsWith('T', $wallet['address']);
        $this->assertEquals(34, strlen($wallet['address']));
    }
    
    public function testAddressValidation()
    {
        require_once __DIR__ . '/../components/wallet_generator.php';
        
        // Valid TRON address
        $this->assertTrue(TronWalletGenerator::validateAddress('TLyqzVGLV1srkB7dToTAEqgDSfPtXRJZYH'));
        
        // Invalid addresses
        $this->assertFalse(TronWalletGenerator::validateAddress('invalid'));
        $this->assertFalse(TronWalletGenerator::validateAddress('1BvBMSEYstWetqTFn5Au4m4GFg7xJaNVN2')); // Bitcoin address
        $this->assertFalse(TronWalletGenerator::validateAddress('')); // Empty
    }
    
    public function testUserRegistration()
    {
        $username = 'testuser';
        $email = 'test@example.com';
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $ph_id = substr(md5(uniqid(rand(), true)), 0, 10);
        
        $stmt = $this->pdo->prepare("INSERT INTO users2 (username, email, password, PH_id) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$username, $email, $password, $ph_id]);
        
        $this->assertTrue($result);
        
        // Verify user was created
        $stmt = $this->pdo->prepare("SELECT * FROM users2 WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        $this->assertNotEmpty($user);
        $this->assertEquals($username, $user['username']);
        $this->assertEquals($email, $user['email']);
    }
    
    public function testBalanceOperations()
    {
        // Create test user
        $stmt = $this->pdo->prepare("INSERT INTO users2 (username, email, password, PH_id) VALUES (?, ?, ?, ?)");
        $stmt->execute(['testuser', 'test@example.com', 'password', 'test123']);
        $user_id = $this->pdo->lastInsertId();
        
        // Create wallet
        $stmt = $this->pdo->prepare("INSERT INTO trxbalance (user_id, private_key, address, username, balance) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, 'test_private_key', 'TLyqzVGLV1srkB7dToTAEqgDSfPtXRJZYH', 'testuser', 100.50]);
        
        // Test balance retrieval
        $stmt = $this->pdo->prepare("SELECT balance FROM trxbalance WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $balance = $stmt->fetchColumn();
        
        $this->assertEquals(100.50, $balance);
        
        // Test balance update
        $stmt = $this->pdo->prepare("UPDATE trxbalance SET balance = balance - ? WHERE user_id = ?");
        $stmt->execute([10.25, $user_id]);
        
        $stmt = $this->pdo->prepare("SELECT balance FROM trxbalance WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $new_balance = $stmt->fetchColumn();
        
        $this->assertEquals(90.25, $new_balance);
    }
}
?>
