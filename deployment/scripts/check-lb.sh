#!/bin/bash
#######################################################################################
# Check Load Balancer Status
# Restarts HAProxy and verifies connection to App Servers
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

check_lb() {
    local LB_IP=$1
    local APP1=$2
    local APP2=$3
    
    echo -e "${YELLOW}Connecting to Load Balancer (${LB_IP})...${NC}"

    # Prepare SSH command prefix
    local SSH_PREFIX=""
    if [ -n "$SSH_PASSWORD" ]; then
        if command -v sshpass &> /dev/null; then
            SSH_PREFIX="sshpass -p $SSH_PASSWORD"
        fi
    fi

    local SSH_CMD="$SSH_PREFIX ssh $SSH_OPTIONS"
    local SUDO_PASS_CMD="echo '$SSH_PASSWORD' | sudo -S"

    # 1. Restart HAProxy
    echo "  - Restarting HAProxy..."
    $SSH_CMD $SSH_USER@$LB_IP "$SUDO_PASS_CMD systemctl restart haproxy" && echo -e "${GREEN}    ✓ HAProxy Restarted${NC}" || echo -e "${RED}    ✗ Failed to restart HAProxy${NC}"
    
    # 2. Check HAProxy Status
    echo "  - Checking HAProxy Status..."
    if $SSH_CMD $SSH_USER@$LB_IP "$SUDO_PASS_CMD systemctl is-active --quiet haproxy"; then
        echo -e "${GREEN}    ✓ HAProxy is Active${NC}"
    else
        echo -e "${RED}    ✗ HAProxy is NOT Active${NC}"
        exit 1
    fi

    # 3. Connectivity Check to App Servers FROM LB
    echo "  - Verifying connectivity from LB to App Servers..."
    
    # Check App 1
    if $SSH_CMD $SSH_USER@$LB_IP "nc -z -v -w 2 $APP1 80" 2>&1 | grep -q "succeeded"; then
         echo -e "${GREEN}    ✓ Connection to App Server 1 ($APP1:80) successful${NC}"
    else
         # Try curl if nc fails or isn't installed
         if $SSH_CMD $SSH_USER@$LB_IP "curl -s --head --request GET http://$APP1 | grep '200 OK' > /dev/null"; then
             echo -e "${GREEN}    ✓ Connection to App Server 1 ($APP1:80) successful (via curl)${NC}"
         else
             echo -e "${RED}    ✗ Could not connect to App Server 1 ($APP1)${NC}"
         fi
    fi

    # Check App 2
    if $SSH_CMD $SSH_USER@$LB_IP "nc -z -v -w 2 $APP2 80" 2>&1 | grep -q "succeeded"; then
         echo -e "${GREEN}    ✓ Connection to App Server 2 ($APP2:80) successful${NC}"
    else
         if $SSH_CMD $SSH_USER@$LB_IP "curl -s --head --request GET http://$APP2 | grep '200 OK' > /dev/null"; then
             echo -e "${GREEN}    ✓ Connection to App Server 2 ($APP2:80) successful (via curl)${NC}"
         else
             echo -e "${RED}    ✗ Could not connect to App Server 2 ($APP2)${NC}"
         fi
    fi
    
    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}Load Balancer Verification Complete${NC}"
    echo -e "${GREEN}========================================${NC}"
}

main() {
    # If variables typically come from env, we use them. 
    # Fallback to hardcoded if env vars are missing from the sourced config for this specific run context
    # Assuming CONFIG_FILE has them based on previous turns.
    
    # User specified LB is 192.168.120.38
    # App servers are .33 and .37 from previous context
    
    check_lb "${LB_VM_IP:-192.168.120.38}" "${APP1_VM_IP:-192.168.120.33}" "${APP2_VM_IP:-192.168.120.37}"
}

main "$@"
