#!/bin/bash
#######################################################################################
# VM Verification Script
# Verifies that all VMs are created and accessible
#######################################################################################

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

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}VM Verification${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

#######################
# Check VM Status
#######################
check_vm_status() {
    local VMID=$1
    local VM_NAME=$2
    
    echo -e "${YELLOW}Checking VM $VMID ($VM_NAME)...${NC}"
    
    if qm status $VMID &> /dev/null; then
        STATUS=$(qm status $VMID | awk '{print $2}')
        if [ "$STATUS" = "running" ]; then
            echo -e "${GREEN}  ✓ VM is running${NC}"
        else
            echo -e "${RED}  ✗ VM is $STATUS${NC}"
        fi
        
        # Show resource allocation
        qm config $VMID | grep -E "cores|memory|net0"
    else
        echo -e "${RED}  ✗ VM does not exist${NC}"
    fi
    echo ""
}

#######################
# Check Network Connectivity
#######################
check_network() {
    local IP=$1
    local NAME=$2
    
    echo -e "${YELLOW}Checking network connectivity to $NAME ($IP)...${NC}"
    
    if ping -c 2 -W 2 $IP > /dev/null 2>&1; then
        echo -e "${GREEN}  ✓ Ping successful${NC}"
    else
        echo -e "${RED}  ✗ Cannot ping${NC}"
    fi
    
    if timeout 3 bash -c "cat < /dev/null > /dev/tcp/$IP/22" 2>/dev/null; then
        echo -e "${GREEN}  ✓ SSH port open${NC}"
    else
        echo -e "${YELLOW}  ⚠ SSH not accessible (may need to wait for cloud-init)${NC}"
    fi
    echo ""
}

#######################
# Main Verification
#######################
main() {
    # Check if running on Proxmox
    if ! command -v qm &> /dev/null; then
        echo -e "${YELLOW}Not running on Proxmox host, skipping VM status checks${NC}"
    else
        echo "VM Status:"
        echo "----------"
        check_vm_status $DB_VMID "Database"
        check_vm_status $APP1_VMID "App Server 1"
        check_vm_status $APP2_VMID "App Server 2"
        check_vm_status $LB_VMID "Load Balancer"
    fi
    
    echo ""
    echo "Network Connectivity:"
    echo "---------------------"
    check_network $DB_VM_IP "Database"
    check_network $APP1_VM_IP "App Server 1"
    check_network $APP2_VM_IP "App Server 2"
    check_network $LB_VM_IP "Load Balancer"
    
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}Verification Complete${NC}"
    echo -e "${GREEN}========================================${NC}"
}

main "$@"
