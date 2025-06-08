# TRON Wallet Mobile App

A secure, mobile-optimized TRON wallet application built with PHP and Tailwind CSS.

## 🚀 Quick Start

### Option 1: Use Existing Setup
If you already have a `vendor/` directory:
\`\`\`bash
1. Import database/schema.sql into MySQL
2. Update connect/db.php with your database credentials
3. Visit mobile_check.php to verify installation
4. Access your wallet at index.php
\`\`\`

### Option 2: Fresh Installation
\`\`\`bash
# Clone/download the project
# Run installation script
chmod +x install.sh
./install.sh

# Or install manually:
composer install  # Only if vendor/ doesn't exist
mysql -u root -p tron_wallet < database/schema.sql
\`\`\`

## 📱 Features

- **Secure Wallet Generation** - Create TRON wallets with private keys
- **Internal Transfers** - Send TRX using usernames
- **Deposit & Withdraw** - QR code deposits, secure withdrawals
- **Mobile Optimized** - Responsive design, bottom navigation
- **Email Verification** - OTP-based withdrawal security
- **Market Data** - Real-time cryptocurrency prices
- **Admin Dashboard** - Transaction management

## 🔧 Requirements

- PHP 7.4+ (works with existing vendor/ setup)
- MySQL/MariaDB
- Web server (Apache/Nginx)

## 📁 Project Structure

\`\`\`
tron_wallet/
├── connect/db.php          # Database connection
├── user/                   # User dashboard & features
├── includes/               # Mobile UI components
├── api/                    # API endpoints
├── components/             # Wallet generation
├── vendor/                 # Dependencies (preserved)
├── database/schema.sql     # Database structure
└── mobile_check.php        # System verification
\`\`\`

## 🛠️ Installation Notes

- **vendor/ directory is preserved** - No modifications to existing dependencies
- **Fallback implementations** - Works even without Composer
- **Mobile-first design** - Optimized for mobile devices
- **Secure by default** - CSRF protection, input sanitization

## 🔒 Security Features

- Password hashing with PHP's password_hash()
- CSRF token protection
- Input sanitization
- Rate limiting
- Audit logging
- Email verification for withdrawals

## 📞 Support

If you encounter issues:
1. Run `mobile_check.php` to diagnose problems
2. Check database connection in `connect/db.php`
3. Verify file permissions
4. Ensure MySQL is running

## 🎯 Quick Links

- **System Check**: `/mobile_check.php`
- **Installation Guide**: `/install.php`
- **Wallet App**: `/index.php`
- **Admin Panel**: `/admin/` (after setup)
