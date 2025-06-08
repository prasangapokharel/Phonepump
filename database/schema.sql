-- Enhanced database schema for TRON Wallet Mobile App
-- Run this script to set up the complete database structure

-- Create database
CREATE DATABASE IF NOT EXISTS tron_wallet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tron_wallet;

-- Users table with enhanced security
CREATE TABLE IF NOT EXISTS users2 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    PH_id VARCHAR(20) UNIQUE NOT NULL,
    otp VARCHAR(6) NULL,
    otp_expires_at TIMESTAMP NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(32) NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    last_login_date DATE NULL,
    last_login_ip VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_ph_id (PH_id)
);

-- TRX Balance table with enhanced tracking
CREATE TABLE IF NOT EXISTS trxbalance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    private_key TEXT NOT NULL,
    public_key TEXT NULL,
    address VARCHAR(34) UNIQUE NOT NULL,
    username VARCHAR(50) NOT NULL,
    balance DECIMAL(20,6) DEFAULT 0.000000,
    frozen_balance DECIMAL(20,6) DEFAULT 0.000000,
    status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
    mnemonic TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users2(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_address (address),
    INDEX idx_username (username)
);

-- Transaction history with enhanced details
CREATE TABLE IF NOT EXISTS trxhistory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    from_address VARCHAR(34) NOT NULL,
    to_address VARCHAR(34) NOT NULL,
    amount DECIMAL(20,6) NOT NULL,
    fee DECIMAL(20,6) DEFAULT 0.000000,
    tx_hash VARCHAR(64) NOT NULL,
    block_number BIGINT NULL,
    status ENUM('send', 'receive', 'pending', 'failed') NOT NULL,
    transaction_type ENUM('transfer', 'deposit', 'withdrawal', 'fee') DEFAULT 'transfer',
    confirmation_count INT DEFAULT 0,
    gas_used BIGINT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users2(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_tx_hash (tx_hash),
    INDEX idx_from_address (from_address),
    INDEX idx_to_address (to_address),
    INDEX idx_timestamp (timestamp),
    INDEX idx_status (status)
);

-- Withdrawal transactions tracking
CREATE TABLE IF NOT EXISTS trxtransactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    txid VARCHAR(64) UNIQUE NOT NULL,
    amount DECIMAL(20,6) NOT NULL,
    fee DECIMAL(20,6) DEFAULT 1.500000,
    to_address VARCHAR(34) NOT NULL,
    status ENUM('Pending', 'Processing', 'Completed', 'Failed', 'Cancelled') DEFAULT 'Pending',
    admin_notes TEXT NULL,
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users2(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users2(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_txid (txid),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Cryptocurrency prices for market data
CREATE TABLE IF NOT EXISTS crypto_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) UNIQUE NOT NULL,
    name VARCHAR(50) NOT NULL,
    current_price DECIMAL(20,8) NOT NULL,
    price_change_24h DECIMAL(10,4) DEFAULT 0.0000,
    price_change_percentage_24h DECIMAL(10,4) DEFAULT 0.0000,
    market_cap BIGINT NULL,
    volume_24h BIGINT NULL,
    circulating_supply BIGINT NULL,
    total_supply BIGINT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_symbol (symbol),
    INDEX idx_last_updated (last_updated)
);

-- Audit logs for security tracking
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    session_id VARCHAR(128) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users2(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at)
);

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'moderator',
    permissions JSON NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
);

-- System settings
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_setting_key (setting_key)
);

-- Insert default cryptocurrency prices
INSERT INTO crypto_prices (symbol, name, current_price, price_change_24h, price_change_percentage_24h) VALUES
('TRX', 'TRON', 0.200000, -0.0032, -1.56),
('BTC', 'Bitcoin', 90709.85000000, -408.15, -0.45),
('ETH', 'Ethereum', 3107.75000000, -80.25, -2.53),
('BNB', 'BNB', 626.47000000, 12.34, 2.01),
('SOL', 'Solana', 231.45000000, -5.67, -2.39),
('ADA', 'Cardano', 0.72000000, 0.02, 2.85),
('DOGE', 'Dogecoin', 0.37000000, -0.01, -2.63),
('XRP', 'XRP', 1.09000000, 0.03, 2.83),
('AVAX', 'Avalanche', 36.55000000, -1.23, -3.26),
('LINK', 'Chainlink', 14.09000000, 0.45, 3.30)
ON DUPLICATE KEY UPDATE 
current_price = VALUES(current_price),
price_change_24h = VALUES(price_change_24h),
price_change_percentage_24h = VALUES(price_change_percentage_24h);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('withdrawal_fee', '1.5', 'Default withdrawal fee in TRX'),
('min_withdrawal', '5.0', 'Minimum withdrawal amount in TRX'),
('max_withdrawal', '10000.0', 'Maximum withdrawal amount in TRX'),
('maintenance_mode', 'false', 'Enable/disable maintenance mode'),
('registration_enabled', 'true', 'Enable/disable new user registration'),
('email_verification_required', 'false', 'Require email verification for new accounts'),
('two_factor_required', 'false', 'Require 2FA for all accounts'),
('max_login_attempts', '5', 'Maximum failed login attempts before lockout'),
('lockout_duration', '900', 'Account lockout duration in seconds'),
('session_timeout', '3600', 'Session timeout in seconds')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Create default admin user (password: admin123)
INSERT INTO admin_users (username, email, password, role) VALUES
('admin', 'admin@tronwallet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin')
ON DUPLICATE KEY UPDATE password = VALUES(password);
