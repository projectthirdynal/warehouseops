#!/bin/bash
# Quick App Server Deployment Script
# Run this on Proxmox to deploy both app servers

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}App Server Deployment${NC}"
echo -e "${GREEN}================================${NC}"
echo ""

# Load configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="$(dirname "$SCRIPT_DIR")/config.env"

if [ -f "$CONFIG_FILE" ]; then
    source "$CONFIG_FILE"
else
    echo -e "${RED}Error: Configuration file not found${NC}"
    # Fallback if config not found (though it should be there)
    SSH_USER="it-admin"
fi

# App Server IPs
APP1_IP="192.168.0.33"
APP2_IP="192.168.0.34"

deploy_app_server() {
    local SERVER_IP=$1
    local SERVER_NAME=$2
    
    echo -e "${YELLOW}Deploying ${SERVER_NAME} (${SERVER_IP})...${NC}"

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
    
    # Copy Laravel application
    echo "  - Copying Laravel application..."
    $SSH_CMD $SSH_USER@${SERVER_IP} "mkdir -p /tmp/laravel"
    $SCP_CMD -r /tmp/laravel/* $SSH_USER@${SERVER_IP}:/tmp/laravel/
    
    # Copy configuration and setup script
    echo "  - Copying config and setup script..."
    $SCP_CMD /tmp/deployment/config.env $SSH_USER@${SERVER_IP}:/tmp/
    $SCP_CMD /tmp/deployment/scripts/app-server-setup.sh $SSH_USER@${SERVER_IP}:/tmp/
    
    # Run setup script
    echo "  - Running setup script (this will take 5-7 minutes)..."
    $SSH_CMD $SSH_USER@${SERVER_IP} "echo $SSH_PASSWORD | sudo -S bash /tmp/app-server-setup.sh"
    
    # Verify
    echo "  - Verifying installation..."
    $SSH_CMD $SSH_USER@${SERVER_IP} "curl -s http://localhost/health" && echo -e "${GREEN}  ✓ ${SERVER_NAME} is healthy!${NC}" || echo -e "${RED}  ✗ ${SERVER_NAME} health check failed${NC}"
    
    echo ""
}

# Check if database is set up
echo -e "${YELLOW}Checking database connectivity...${NC}"
if timeout 1 bash -c 'cat < /dev/tcp/192.168.0.36/5432' &>/dev/null; then
    echo -e "${GREEN}✓ Database server is reachable${NC}"
else
    echo -e "${RED}⚠ Warning: Database server may not be set up yet or is unreachable${NC}"
    echo "Check connectivity to 192.168.0.36:5432"
    echo "Proceeding anyway..."
fi
echo ""

# Deploy App Server 1
deploy_app_server $APP1_IP "App Server 1"

# Deploy App Server 2
deploy_app_server $APP2_IP "App Server 2"

# Final verification
echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}Deployment Complete!${NC}"
echo -e "${GREEN}================================${NC}"
echo ""
echo "Testing load balancer..."
curl -s http://192.168.0.35/health && echo -e "${GREEN}✓ Load balancer is now working!${NC}" || echo -e "${RED}✗ Load balancer still showing errors${NC}"

echo ""
echo "Access your application at: http://192.168.0.35"
echo "HAProxy stats: http://192.168.0.35:8404/stats"
