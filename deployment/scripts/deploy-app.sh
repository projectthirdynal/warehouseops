#!/bin/bash
#######################################################################################
# Application Deployment Script
# Use this script to deploy updates to the Laravel application
#######################################################################################

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Load configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="$(dirname "$SCRIPT_DIR")/config.env"

if [ -f "$CONFIG_FILE" ]; then
    source "$CONFIG_FILE"
else
    echo -e "${RED}Error: Configuration file not found${NC}"
    exit 1
fi

APP_DIR="/var/www/waybill"
APP_SERVERS=("$APP1_VM_IP" "$APP2_VM_IP")

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Deploying Waybill Application Update${NC}"
echo -e "${GREEN}========================================${NC}"

#######################
# Deploy to Server
#######################
deploy_to_server() {
    local SERVER_IP=$1
    local SERVER_NAME=$2
    
    echo -e "${YELLOW}Deploying to ${SERVER_NAME} (${SERVER_IP})...${NC}"

    # Prepare SSH command prefix
    local SSH_PREFIX=""
    if [ -n "$SSH_PASSWORD" ]; then
        if command -v sshpass &> /dev/null; then
            SSH_PREFIX="sshpass -p $SSH_PASSWORD"
        else
            echo -e "${YELLOW}Warning: SSH_PASSWORD set but sshpass not found. Install it for password automation.${NC}"
        fi
    fi

    local SSH_CMD="$SSH_PREFIX ssh $SSH_OPTIONS"
    local RSYNC_RSH="ssh $SSH_OPTIONS"
    
    # Define remote sudo command with password pipe
    # We use single quotes for the password to handle special characters slightly better, 
    # but be aware that strict quoting over SSH is complex.
    local SUDO_PASS_CMD="echo '$SSH_PASSWORD' | sudo -S"

    if [ -n "$SSH_PASSWORD" ] && command -v sshpass &> /dev/null; then
        # For rsync, we need to wrap the whole command or use sshpass
        # Simplest is to just use sshpass before rsync
        :
    fi
    
    # Put application in maintenance mode
    $SSH_CMD $SSH_USER@$SERVER_IP "cd $APP_DIR && $SUDO_PASS_CMD -u www-data php artisan down" || true
    
    # Take ownership of the directory to allow rsync to write
    echo -e "${YELLOW}Fixing permissions for deployment...${NC}"
    $SSH_CMD $SSH_USER@$SERVER_IP "$SUDO_PASS_CMD chown -R $SSH_USER:$SSH_USER $APP_DIR"
    
    # Copy application files (excluding .env and storage)
    # Note: We copy as SSH user first
    if [ -n "$SSH_PASSWORD" ] && command -v sshpass &> /dev/null; then
        sshpass -p "$SSH_PASSWORD" rsync -avz --delete \
            -e "$RSYNC_RSH" \
            --exclude='.env' \
            --exclude='storage/logs/*' \
            --exclude='storage/app/*' \
            --exclude='storage/framework/cache/*' \
            --exclude='storage/framework/sessions/*' \
            --exclude='storage/framework/views/*' \
            --exclude='node_modules' \
            --exclude='.git' \
            "$APP_SOURCE_PATH/" $SSH_USER@$SERVER_IP:$APP_DIR/
    else
        rsync -avz --delete \
            -e "$RSYNC_RSH" \
            --exclude='.env' \
            --exclude='storage/logs/*' \
            --exclude='storage/app/*' \
            --exclude='storage/framework/cache/*' \
            --exclude='storage/framework/sessions/*' \
            --exclude='storage/framework/views/*' \
            --exclude='node_modules' \
            --exclude='.git' \
            "$APP_SOURCE_PATH/" $SSH_USER@$SERVER_IP:$APP_DIR/
    fi
    
    # Fix ownership immediately after copy
    $SSH_CMD $SSH_USER@$SERVER_IP "$SUDO_PASS_CMD chown -R www-data:www-data $APP_DIR"
    
    # Install dependencies as www-data
    $SSH_CMD $SSH_USER@$SERVER_IP "cd $APP_DIR && $SUDO_PASS_CMD -u www-data composer install --no-dev --optimize-autoloader"
    
    # Run migrations as www-data
    $SSH_CMD $SSH_USER@$SERVER_IP "cd $APP_DIR && $SUDO_PASS_CMD -u www-data php artisan migrate --force"
    
    # Clear and rebuild cache as www-data
    $SSH_CMD $SSH_USER@$SERVER_IP "cd $APP_DIR && $SUDO_PASS_CMD -u www-data php artisan config:cache"
    $SSH_CMD $SSH_USER@$SERVER_IP "cd $APP_DIR && $SUDO_PASS_CMD -u www-data php artisan route:cache"
    $SSH_CMD $SSH_USER@$SERVER_IP "cd $APP_DIR && $SUDO_PASS_CMD -u www-data php artisan view:cache"
    
    # Ensure storage permissions are correct
    $SSH_CMD $SSH_USER@$SERVER_IP "$SUDO_PASS_CMD chmod -R 775 $APP_DIR/storage $APP_DIR/bootstrap/cache"
    $SSH_CMD $SSH_USER@$SERVER_IP "$SUDO_PASS_CMD chown -R www-data:www-data $APP_DIR/storage $APP_DIR/bootstrap/cache"
    
    # Restart services
    $SSH_CMD $SSH_USER@$SERVER_IP "$SUDO_PASS_CMD systemctl restart php8.4-fpm"
    $SSH_CMD $SSH_USER@$SERVER_IP "$SUDO_PASS_CMD systemctl restart waybill-worker"
    
    # Bring application up as www-data
    $SSH_CMD $SSH_USER@$SERVER_IP "cd $APP_DIR && $SUDO_PASS_CMD -u www-data php artisan up"
    
    echo -e "${GREEN}✓ Deployment to ${SERVER_NAME} complete${NC}"
}


#######################
# Main Deployment
#######################
main() {
    echo "This will deploy the application from: $APP_SOURCE_PATH"
    echo "To servers:"
    echo "  - App Server 1: $APP1_VM_IP"
    echo "  - App Server 2: $APP2_VM_IP"
    echo ""
    read -p "Continue? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 0
    fi
    
    # Deploy to app server 1
    deploy_to_server "$APP1_VM_IP" "App Server 1"
    
    # Wait a bit before deploying to second server
    echo "Waiting 10 seconds before deploying to second server..."
    sleep 10
    
    # Deploy to app server 2
    deploy_to_server "$APP2_VM_IP" "App Server 2"
    
    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}Deployment Complete!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    echo "Application is now live at: http://$LB_VM_IP"
}

main "$@"
