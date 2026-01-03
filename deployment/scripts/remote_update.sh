#!/bin/bash
set -e
APP_DIR="/var/www/waybill"
echo "[INFO] Deploying code..."
cp -r /tmp/laravel/. $APP_DIR/
chown -R www-data:www-data $APP_DIR

echo "[INFO] Installing dependencies..."
cd $APP_DIR
sudo -u www-data composer install --no-dev --optimize-autoloader

echo "[INFO] Running migrations and clear cache..."
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

echo "[INFO] Restarting workers..."
systemctl restart waybill-worker
echo "[INFO] Deployment Complete"
