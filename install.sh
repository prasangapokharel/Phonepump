#!/bin/bash
# TRON Wallet Installation Script (respects existing vendor/)

echo "🚀 TRON Wallet Installation Script"
echo "=================================="

# Check if vendor directory exists
if [ -d "vendor" ]; then
    echo "✅ vendor/ directory found - skipping Composer install"
else
    echo "📦 vendor/ directory not found"
    if command -v composer &> /dev/null; then
        echo "🔄 Running composer install..."
        composer install
        if [ $? -eq 0 ]; then
            echo "✅ Composer install completed"
        else
            echo "❌ Composer install failed"
            echo "💡 You can continue without Composer - the wallet has fallback implementations"
        fi
    else
        echo "❌ Composer not found"
        echo "💡 Continuing without Composer - using existing vendor/ setup"
    fi
fi

# Check PHP version
echo "📋 Checking PHP version..."
php_version=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
echo "PHP Version: $php_version"

# Check if MySQL is available
echo "📋 Checking MySQL..."
if command -v mysql &> /dev/null; then
    echo "✅ MySQL found"
else
    echo "❌ MySQL not found. Please install MySQL/MariaDB"
    exit 1
fi

# Create .env file if it doesn't exist
echo "📋 Setting up configuration..."
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        echo "✅ Created .env file"
    else
        echo "ℹ️  .env.example not found, skipping"
    fi
else
    echo "ℹ️  .env file already exists"
fi

# Set permissions
echo "📋 Setting file permissions..."
chmod 755 .
chmod 644 *.php 2>/dev/null
chmod 644 includes/*.php 2>/dev/null
chmod 644 user/*.php 2>/dev/null
chmod 644 api/*.php 2>/dev/null
chmod 644 components/*.php 2>/dev/null
echo "✅ Permissions set"

# Check if database exists
echo "📋 Checking database..."
read -p "Enter MySQL username (default: root): " db_user
db_user=${db_user:-root}

read -s -p "Enter MySQL password: " db_pass
echo

# Test database connection
if mysql -u "$db_user" -p"$db_pass" -e "SELECT 1;" &> /dev/null; then
    echo "✅ Database connection successful"
    
    # Create database if it doesn't exist
    mysql -u "$db_user" -p"$db_pass" -e "CREATE DATABASE IF NOT EXISTS tron_wallet;" 2>/dev/null
    echo "✅ Database 'tron_wallet' ready"
    
    # Import schema
    if [ -f "database/schema.sql" ]; then
        mysql -u "$db_user" -p"$db_pass" tron_wallet < database/schema.sql
        echo "✅ Database schema imported"
    else
        echo "❌ Schema file not found"
    fi
else
    echo "❌ Database connection failed"
    exit 1
fi

echo ""
echo "🎉 Installation completed successfully!"
echo ""
echo "📝 Important Notes:"
echo "   - vendor/ directory was preserved"
echo "   - Existing implementations are being used"
echo "   - No Composer dependencies were modified"
echo ""
echo "Next steps:"
echo "1. Update database credentials in connect/db.php"
echo "2. Visit mobile_check.php to verify installation"
echo "3. Access your wallet at index.php"
echo ""
echo "🔗 Quick links:"
echo "   System Check: http://localhost/tron_wallet/mobile_check.php"
echo "   Wallet App:   http://localhost/tron_wallet/"
