#!/bin/bash
set -e

# Local paths
LOCAL_ROOT="/home/it-admin/Documents/v4/v4/laravel"
PROXMOX_HOST="root@192.168.120.155"
PROXMOX_TMP="/tmp/laravel_rbac"
SCRIPT_DIR="$(dirname "$0")"
DEPLOY_PKG="/tmp/rbac_deploy_pkg.tar.gz"

echo "=== STEP 1: Creating Deployment Package ==="
# Create a temporary directory to assemble the package
PKG_DIR="/tmp/rbac_package_build"
rm -rf $PKG_DIR
mkdir -p $PKG_DIR/files

# Copy files to package dir
echo "Collecting files..."
cp "$LOCAL_ROOT/app/Http/Controllers/AuthController.php" $PKG_DIR/files/
cp "$LOCAL_ROOT/app/Http/Controllers/SettingsController.php" $PKG_DIR/files/
cp "$LOCAL_ROOT/app/Http/Controllers/DashboardController.php" $PKG_DIR/files/
cp "$LOCAL_ROOT/app/Http/Controllers/WaybillController.php" $PKG_DIR/files/
cp "$LOCAL_ROOT/app/Http/Middleware/CheckRole.php" $PKG_DIR/files/
cp "$LOCAL_ROOT/app/Models/User.php" $PKG_DIR/files/

mkdir -p $PKG_DIR/files/auth
cp -r "$LOCAL_ROOT/resources/views/auth/"* $PKG_DIR/files/auth/

mkdir -p $PKG_DIR/files/settings
cp -r "$LOCAL_ROOT/resources/views/settings/"* $PKG_DIR/files/settings/

cp "$LOCAL_ROOT/resources/views/layouts/app.blade.php" $PKG_DIR/files/
cp "$LOCAL_ROOT/routes/web.php" $PKG_DIR/files/
cp "$LOCAL_ROOT/bootstrap/app.php" $PKG_DIR/files/
cp "$LOCAL_ROOT/database/migrations/0001_01_01_000000_create_users_table.php" $PKG_DIR/files/
cp "$LOCAL_ROOT/database/seeders/UserSeeder.php" $PKG_DIR/files/
cp "$LOCAL_ROOT/database/seeders/DatabaseSeeder.php" $PKG_DIR/files/

# Copy helper script
cp "$SCRIPT_DIR/apply_update.sh" $PKG_DIR/

# Create Tarball
echo "Compressing package..."
cd $PKG_DIR
tar -czf $DEPLOY_PKG .
echo "Package created at $DEPLOY_PKG"

echo ""
echo "=== STEP 2: Deploying to Proxmox Host ==="

# Create temp dir on Proxmox
echo "Cleaning remote temp directory on Proxmox..."
ssh $PROXMOX_HOST "rm -rf $PROXMOX_TMP && mkdir -p $PROXMOX_TMP"

# Copy package to Proxmox
echo "Copying deployment package (ONE FILE)..."
scp $DEPLOY_PKG $PROXMOX_HOST:$PROXMOX_TMP/

# Extract on Proxmox
echo "Extracting package on Proxmox..."
ssh $PROXMOX_HOST "cd $PROXMOX_TMP && tar -xzf rbac_deploy_pkg.tar.gz && chmod +x apply_update.sh"

echo "Step 2 Complete: Package ready on Proxmox"

echo ""
echo "=== STEP 3: Triggering Deployment from Proxmox to App Servers ==="
echo "NOTE: You will be prompted for the 'it-admin' password for each app server."
echo "Connecting to Proxmox..."

# Execute the helper script on Proxmox with TTY allocation (-t)
ssh -t $PROXMOX_HOST "bash $PROXMOX_TMP/apply_update.sh"

echo ""
echo "Deployment Process Finished."
