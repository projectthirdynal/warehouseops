#!/bin/bash
#######################################################################################
# Restart Application Services
# Restarts Nginx, PHP-FPM, and Queue Workers on all App Servers
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

restart_services() {
    local SERVER_IP=$1
    local SERVER_NAME=$2
    
    echo -e "${YELLOW}Restarting services on ${SERVER_NAME} (${SERVER_IP})...${NC}"

    # Prepare SSH command prefix
    local SSH_PREFIX=""
    if [ -n "$SSH_PASSWORD" ]; then
        if command -v sshpass &> /dev/null; then
            SSH_PREFIX="sshpass -p $SSH_PASSWORD"
        fi
    fi

    local SSH_CMD="$SSH_PREFIX ssh $SSH_OPTIONS"
    
    # Define remote sudo command with password pipe
    local SUDO_PASS_CMD="echo '$SSH_PASSWORD' | sudo -S"

    # Restart Nginx
    echo "  - Restarting Nginx..."
    $SSH_CMD $SSH_USER@$SERVER_IP "$SUDO_PASS_CMD systemctl restart nginx"
    
    # Restart PHP-FPM
    echo "  - Restarting PHP-FPM..."
    $SSH_CMD $SSH_USER@$SERVER_IP "$SUDO_PASS_CMD systemctl restart php8.4-fpm"
    
    # Restart Queue Worker
    echo "  - Restarting Queue Worker..."
    $SSH_CMD $SSH_USER@$SERVER_IP "$SUDO_PASS_CMD systemctl restart waybill-worker"

    echo -e "${GREEN}✓ Services restarted on ${SERVER_NAME}${NC}"
}

main() {
    echo "This will restart services (Nginx, PHP, Worker) on:"
    echo "  - App Server 1: $APP1_VM_IP"
    echo "  - App Server 2: $APP2_VM_IP"
    echo ""
    
    restart_services "$APP1_VM_IP" "App Server 1"
    restart_services "$APP2_VM_IP" "App Server 2"
    
    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}All Services Restarted Successfully${NC}"
    echo -e "${GREEN}========================================${NC}"
}

main "$@"
