#!/bin/bash

# RSS Manager - PHP Deployment Script
# This script helps deploy the PHP version of RSS Manager

set -e

echo "=============================================="
echo "  RSS Manager - PHP Deployment Script"
echo "=============================================="
echo ""

# Get current directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo "Working directory: $SCRIPT_DIR"
echo ""

# Step 1: Backup
echo "Step 1: Creating backups..."
BACKUP_DIR="backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

if [ -f ".htaccess" ]; then
    cp .htaccess "$BACKUP_DIR/.htaccess"
    echo "  ✓ Backed up .htaccess"
fi

if [ -f "passenger_wsgi.py" ]; then
    cp passenger_wsgi.py "$BACKUP_DIR/passenger_wsgi.py"
    echo "  ✓ Backed up passenger_wsgi.py"
fi

if [ -f "rss_feeds.db" ]; then
    cp rss_feeds.db "$BACKUP_DIR/rss_feeds.db"
    echo "  ✓ Backed up database"
fi

echo "  ✓ Backups saved in: $BACKUP_DIR"
echo ""

# Step 2: Deploy PHP configuration
echo "Step 2: Deploying PHP configuration..."

if [ -f ".htaccess_php" ]; then
    cp .htaccess_php .htaccess
    echo "  ✓ Deployed .htaccess for PHP"
else
    echo "  ✗ .htaccess_php not found!"
    exit 1
fi
echo ""

# Step 3: Set permissions
echo "Step 3: Setting file permissions..."

chmod 644 .htaccess
chmod 644 *.php 2>/dev/null || true
chmod 755 includes/ api/ cron/ 2>/dev/null || true

# Create logs directory if it doesn't exist
mkdir -p logs
chmod 755 logs
chmod 666 logs/* 2>/dev/null || true

# Database permissions
if [ -f "rss_feeds.db" ]; then
    chmod 666 rss_feeds.db
    echo "  ✓ Set database permissions"
fi

echo "  ✓ Permissions set"
echo ""

# Step 4: Initialize database
echo "Step 4: Initializing application..."

if command -v php &> /dev/null; then
    php init.php
else
    echo "  ⚠️  PHP command not found. Please run 'php init.php' manually."
fi
echo ""

# Step 5: Display next steps
echo "=============================================="
echo "  Deployment Complete!"
echo "=============================================="
echo ""
echo "Next steps:"
echo "1. Test the application: https://dusselle.fr/login.php"
echo "2. Login with default credentials:"
echo "   Username: admin"
echo "   Password: admin123"
echo "3. Change admin password immediately!"
echo "4. Set up cron job:"
echo "   crontab -e"
echo "   Add: */30 * * * * /usr/bin/php $SCRIPT_DIR/cron/update_feeds.php >> $SCRIPT_DIR/logs/cron.log 2>&1"
echo ""
echo "Rollback if needed:"
echo "   cp $BACKUP_DIR/.htaccess .htaccess"
echo "   # Then restart via cPanel"
echo ""
echo "Happy RSS reading! 📰"
echo ""
