#!/bin/bash
set -e

# Define App Servers by IP
APP_SERVERS=("192.168.120.33" "192.168.120.37")
REMOTE_USER="it-admin"
SOURCE_DIR="/tmp/laravel_rbac"
APP_DIR="/var/www/waybill"

echo "=== Starting Proxmox -> App Server Deployment ==="

for SERVER in "${APP_SERVERS[@]}"; do
    echo ""
    echo "Processing Server: $SERVER"
    echo "----------------------------------------"
    
    # Create temp dir
    echo "Creating temp directory on $SERVER..."
    ssh $REMOTE_USER@$SERVER "mkdir -p /tmp/rbac_deploy"
    
    # Copy files
    echo "Copying files to $SERVER..."
    scp -r $SOURCE_DIR/* $REMOTE_USER@$SERVER:/tmp/rbac_deploy/
    
    # Move files and set permissions
    echo "Applying updates on $SERVER..."
    ssh $REMOTE_USER@$SERVER "
        sudo cp /tmp/rbac_deploy/AuthController.php $APP_DIR/app/Http/Controllers/
        sudo cp /tmp/rbac_deploy/SettingsController.php $APP_DIR/app/Http/Controllers/
        sudo cp /tmp/rbac_deploy/DashboardController.php $APP_DIR/app/Http/Controllers/
        sudo cp /tmp/rbac_deploy/WaybillController.php $APP_DIR/app/Http/Controllers/
        sudo cp /tmp/rbac_deploy/CheckRole.php $APP_DIR/app/Http/Middleware/
        sudo cp /tmp/rbac_deploy/User.php $APP_DIR/app/Models/
        
        # Ensure directories exist
        sudo mkdir -p $APP_DIR/resources/views/auth
        sudo mkdir -p $APP_DIR/resources/views/settings
        
        sudo cp -r /tmp/rbac_deploy/auth/* $APP_DIR/resources/views/auth/
        sudo cp -r /tmp/rbac_deploy/settings/* $APP_DIR/resources/views/settings/
        sudo cp /tmp/rbac_deploy/app.blade.php $APP_DIR/resources/views/layouts/
        sudo cp /tmp/rbac_deploy/web.php $APP_DIR/routes/
        sudo cp /tmp/rbac_deploy/app.php $APP_DIR/bootstrap/
        sudo cp /tmp/rbac_deploy/0001_01_01_000000_create_users_table.php $APP_DIR/database/migrations/
        sudo cp /tmp/rbac_deploy/UserSeeder.php $APP_DIR/database/seeders/
        sudo cp /tmp/rbac_deploy/DatabaseSeeder.php $APP_DIR/database/seeders/
        
        # Fix Ownership
        sudo chown -R www-data:www-data $APP_DIR
        
        # Clean up temp files
        rm -rf /tmp/rbac_deploy
        
        # Clear caches
        cd $APP_DIR
        echo 'Clearing caches...'
        sudo -u www-data php artisan view:clear
        sudo -u www-data php artisan route:clear
        sudo -u www-data php artisan config:clear
    "
done

# Run migrations on primary only
echo ""
echo "=== Running Database Migrations (Primary Server Only) ==="
ssh $REMOTE_USER@192.168.120.33 "cd $APP_DIR && sudo -u www-data php artisan migrate --force && sudo -u www-data php artisan db:seed --force"

echo ""
echo "=== Deployment Complete! ==="
