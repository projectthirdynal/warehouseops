#!/bin/bash
# scripts/cleanup-app-server.sh
# Run this on the App Server to clean up previous deployments for a fresh start

set -e

echo "========================================"
echo "Cleaning up App Server for Fresh Deployment"
echo "========================================"

# Check if running as root or with sudo rights
if [ "$EUID" -ne 0 ]; then
    echo "Please run as root or with sudo"
    exit 1
fi

echo "1. Stopping services..."
systemctl stop nginx || true
systemctl stop php*-fpm || true
systemctl stop waybill-worker || true

echo "2. Removing existing application directory..."
if [ -d "/var/www/waybill" ]; then
    rm -rf /var/www/waybill
    echo "   Removed /var/www/waybill"
else
    echo "   /var/www/waybill does not exist"
fi

echo "3. Removing temporary files..."
if [ -d "/tmp/laravel" ]; then
    rm -rf /tmp/laravel
    echo "   Removed /tmp/laravel"
else
    echo "   /tmp/laravel does not exist"
fi

# Clean up other temp artifacts if needed
rm -f /tmp/config.env
rm -f /tmp/app-server-setup.sh
rm -f /tmp/update-live-app.sh
rm -rf /tmp/deployment
rm -rf /tmp/scripts

echo "========================================"
echo "Cleanup Complete! Server is ready for fresh files."
echo "========================================"
