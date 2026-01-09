#!/bin/bash
# Deploy to Load Balancer
# This script deploys the Load Balancer configuration

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="$(dirname "$SCRIPT_DIR")/config.env"

if [ -f "$CONFIG_FILE" ]; then
    source "$CONFIG_FILE"
else
    echo -e "${RED}Error: Configuration file not found${NC}"
    exit 1
fi

LB_IP="${LB_VM_IP}"

echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}Load Balancer Deployment${NC}"
echo -e "${GREEN}================================${NC}"
echo ""

echo -e "${YELLOW}Deploying to Load Balancer (${LB_IP})...${NC}"

# Prepare SSH commands
SSH_PREFIX=""
SCP_CMD="scp $SSH_OPTIONS"
SSH_CMD="ssh $SSH_OPTIONS"

if [ -n "$SSH_PASSWORD" ]; then
    if command -v sshpass &> /dev/null; then
        SSH_PREFIX="sshpass -p $SSH_PASSWORD"
        SCP_CMD="sshpass -p $SSH_PASSWORD scp $SSH_OPTIONS"
        SSH_CMD="sshpass -p $SSH_PASSWORD ssh $SSH_OPTIONS"
    else
        echo -e "${YELLOW}Warning: SSH_PASSWORD set but sshpass not found.${NC}"
    fi
fi

# Copy configuration and setup script
echo "  - Copying config and setup script..."
$SCP_CMD /tmp/config.env $SSH_USER@${LB_IP}:/tmp/
$SCP_CMD /home/it-admin/Documents/v4/v4/deployment/scripts/loadbalancer-setup.sh $SSH_USER@${LB_IP}:/tmp/

# Run setup script
echo "  - Running setup script..."
$SSH_CMD $SSH_USER@${LB_IP} "echo $SSH_PASSWORD | sudo -S bash /tmp/loadbalancer-setup.sh"

echo -e "${GREEN}✓ Load Balancer deployment initiated${NC}"
