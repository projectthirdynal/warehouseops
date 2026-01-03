#!/bin/bash
# scripts/fresh-install.sh
# Automated Fresh Start Deployment
# Warning: This will wipe specific directories on the App Servers

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}Fresh Start Deployment${NC}"
echo -e "${GREEN}================================${NC}"
echo ""

# Load configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="$(dirname "$SCRIPT_DIR")/config.env"

if [ -f "$CONFIG_FILE" ]; then
    source "$CONFIG_FILE"
else
    echo -e "${RED}Error: Configuration file not found${NC}"
    # Fallback default user
    SSH_USER="it-admin"
fi

# App Server IPs
APP1_IP="192.168.120.33"
APP2_IP="192.168.120.37"

confirm_reset() {
    echo -e "${RED}WARNING: This will DELETE the application directory (/var/www/waybill) on:${NC}"
    echo -e "  - App Server 1: $APP1_IP"
    echo -e "  - App Server 2: $APP2_IP"
    echo ""
    echo "Database data will NOT be deleted."
    echo ""
    read -p "Are you sure you want to proceed with a FRESH INSTALL? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Aborted."
        exit 0
    fi
}

reset_vm() {
    local SERVER_IP=$1
    local SERVER_NAME=$2
    
    echo -e "${YELLOW}Processing ${SERVER_NAME} (${SERVER_IP})...${NC}"

    # Prepare SSH commands
    local SSH_PREFIX=""
    local SCP_CMD="scp $SSH_OPTIONS"
    local SSH_CMD="ssh $SSH_OPTIONS"
    
    if [ -n "$SSH_PASSWORD" ]; then
        if command -v sshpass &> /dev/null; then
            SSH_PREFIX="sshpass -p $SSH_PASSWORD"
            SCP_CMD="sshpass -p $SSH_PASSWORD scp $SSH_OPTIONS"
            SSH_CMD="sshpass -p $SSH_PASSWORD ssh $SSH_OPTIONS"
        else
            echo -e "${YELLOW}Warning: SSH_PASSWORD set but sshpass not found.${NC}"
        fi
    fi

    # 1. Cleanup
    echo "  - Cleaning up previous installation..."
    # Ensure cleanup script is there (copy it again to be sure)
    $SCP_CMD /tmp/deployment/scripts/cleanup-app-server.sh $SSH_USER@${SERVER_IP}:/tmp/
    $SSH_CMD $SSH_USER@${SERVER_IP} "sudo bash /tmp/cleanup-app-server.sh"

    # 2. Copy New Files
    echo "  - Copying Laravel application..."
    $SCP_CMD -r /tmp/laravel/* $SSH_USER@${SERVER_IP}:/tmp/laravel/
    
    echo "  - Copying config and setup script..."
    $SCP_CMD /tmp/deployment/config.env $SSH_USER@${SERVER_IP}:/tmp/
    $SCP_CMD /tmp/deployment/scripts/app-server-setup.sh $SSH_USER@${SERVER_IP}:/tmp/
    
    # 3. Setup
    echo "  - Running setup script (this will take 5-7 minutes)..."
    $SSH_CMD $SSH_USER@${SERVER_IP} "sudo bash /tmp/app-server-setup.sh"
    
    # 4. Verify
    echo "  - Verifying installation..."
    $SSH_CMD $SSH_USER@${SERVER_IP} "curl -s http://localhost/health" && echo -e "${GREEN}  ✓ ${SERVER_NAME} is healthy!${NC}" || echo -e "${RED}  ✗ ${SERVER_NAME} health check failed${NC}"
    
    echo ""
}

# Main Execution
confirm_reset

# Check Database Connectivity first
echo -e "${YELLOW}Checking database connectivity...${NC}"
if timeout 1 bash -c 'cat < /dev/tcp/192.168.120.36/5432' &>/dev/null; then
    echo -e "${GREEN}✓ Database server is reachable${NC}"
else
    echo -e "${RED}⚠ Warning: Database server may not be set up yet or is unreachable${NC}"
    echo "Check connectivity to 192.168.120.36:5432"
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi
echo ""

# Process Setup
reset_vm $APP1_IP "App Server 1"
reset_vm $APP2_IP "App Server 2"

echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}Fresh Installation Complete!${NC}"
echo -e "${GREEN}================================${NC}"
echo ""
echo "Test URL: http://192.168.120.38"
