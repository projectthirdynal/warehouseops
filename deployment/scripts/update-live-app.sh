#!/bin/bash
# scripts/update-live-app.sh
# Run this on the App Server to apply changes from /tmp/laravel to the live site

set -e

APP_DIR="/var/www/waybill"
TMP_DIR="/tmp/laravel"

echo "========================================"
echo "Updating Waybill Application"
echo "========================================"

if [ ! -d "$TMP_DIR" ]; then
    echo "Error: Source directory $TMP_DIR not found."
    echo "Please SCP the laravel codebase to $TMP_DIR first."
    exit 1
fi

echo "1. Copying files to $APP_DIR..."
# Copy all files, including hidden ones
sudo cp -r $TMP_DIR/. $APP_DIR/

echo "2. Setting permissions..."
sudo chown -R www-data:www-data $APP_DIR
sudo chmod -R 775 $APP_DIR/storage $APP_DIR/bootstrap/cache

echo "3. Clearing caches..."
cd $APP_DIR
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear

echo "4. Running migrations..."
sudo -u www-data php artisan migrate --force

echo "5. Restarting PHP-FPM..."
if systemctl is-active --quiet php8.4-fpm; then
    sudo systemctl restart php8.4-fpm
else
    echo "PHP 8.4 FPM not running, attempting to restart default..."
    sudo systemctl restart php*-fpm || true
fi

echo "========================================"
echo "Update Complete!"
echo "========================================"
