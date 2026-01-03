#!/bin/bash
#######################################################################################
# Proxmox VM Setup Script
# This script creates VMs on Proxmox for the Waybill Scanning System
#######################################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Load configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="$(dirname "$SCRIPT_DIR")/config.env"

if [ ! -f "$CONFIG_FILE" ]; then
    echo -e "${RED}Error: Configuration file not found: $CONFIG_FILE${NC}"
    echo "Please create config.env with your environment settings"
    exit 1
fi

source "$CONFIG_FILE"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Proxmox VM Setup for Waybill System${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

#######################
# Validate Prerequisites
#######################
check_prerequisites() {
    echo -e "${YELLOW}Checking prerequisites...${NC}"
    
    # Check if running on Proxmox host
    if ! command -v qm &> /dev/null; then
        echo -e "${RED}Error: This script must be run on a Proxmox host${NC}"
        exit 1
    fi
    
    # Check if SSH key exists
    if [ ! -f "$SSH_KEY_PATH" ]; then
        echo -e "${RED}Error: SSH key not found: $SSH_KEY_PATH${NC}"
        echo "Generate one with: ssh-keygen -t rsa -b 4096"
        exit 1
    fi
    
    # Check if storage pool exists
    if ! pvesm status | grep -q "$STORAGE_POOL"; then
        echo -e "${RED}Error: Storage pool not found: $STORAGE_POOL${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✓ Prerequisites check passed${NC}"
}

#######################
# Download Ubuntu Cloud Image
#######################
download_cloud_image() {
    echo -e "${YELLOW}Downloading Ubuntu 24.04 cloud image...${NC}"
    
    local IMAGE_URL="https://cloud-images.ubuntu.com/noble/current/noble-server-cloudimg-amd64.img"
    local IMAGE_FILE="/var/lib/vz/template/iso/ubuntu-24.04-cloudimg-amd64.img"
    
    if [ -f "$IMAGE_FILE" ]; then
        echo -e "${GREEN}✓ Cloud image already exists${NC}"
        return 0
    fi
    
    wget -O "$IMAGE_FILE" "$IMAGE_URL"
    echo -e "${GREEN}✓ Cloud image downloaded${NC}"
}

#######################
# Create VM Function
#######################
create_vm() {
    local VMID=$1
    local VM_NAME=$2
    local VM_IP=$3
    local CORES=$4
    local MEMORY=$5
    local DISK=$6
    
    echo -e "${YELLOW}Creating VM $VMID: $VM_NAME...${NC}"
    
    # Check if VM already exists
    if qm status $VMID &> /dev/null; then
        echo -e "${YELLOW}⚠ VM $VMID already exists, skipping...${NC}"
        return 0
    fi
    
    # Create VM
    qm create $VMID \
        --name "$VM_NAME" \
        --cores $CORES \
        --memory $MEMORY \
        --net0 virtio,bridge=$NETWORK_BRIDGE \
        --ostype l26
    
    # Import cloud image as disk
    qm importdisk $VMID /var/lib/vz/template/iso/ubuntu-24.04-cloudimg-amd64.img $STORAGE_POOL
    
    # Attach disk
    qm set $VMID --scsihw virtio-scsi-pci --scsi0 ${STORAGE_POOL}:vm-${VMID}-disk-0
    
    # Resize disk
    qm resize $VMID scsi0 $DISK
    
    # Add cloud-init drive
    qm set $VMID --ide2 ${STORAGE_POOL}:cloudinit
    
    # Configure cloud-init
    qm set $VMID --boot order=scsi0
    qm set $VMID --serial0 socket --vga serial0
    
    # Set cloud-init network
    qm set $VMID --ipconfig0 ip=${VM_IP}/${NETMASK},gw=${GATEWAY}
    
    # Set cloud-init user
    qm set $VMID --ciuser $SSH_USER
    qm set $VMID --sshkeys $SSH_KEY_PATH
    
    # Set DNS
    qm set $VMID --nameserver "$DNS_SERVERS"
    
    echo -e "${GREEN}✓ VM $VMID created successfully${NC}"
}

#######################
# Main Setup
#######################
main() {
    echo "This script will create the following VMs:"
    echo "  - Database Server (VMID: $DB_VMID, IP: $DB_VM_IP)"
    echo "  - App Server 1 (VMID: $APP1_VMID, IP: $APP1_VM_IP)"
    echo "  - App Server 2 (VMID: $APP2_VMID, IP: $APP2_VM_IP)"
    echo "  - Load Balancer (VMID: $LB_VMID, IP: $LB_VM_IP)"
    echo ""
    echo "Total Resources:"
    echo "  - vCPUs: $(($DB_CORES + $APP_CORES + $APP_CORES + $LB_CORES))"
    echo "  - Memory: $(($DB_MEMORY + $APP_MEMORY + $APP_MEMORY + $LB_MEMORY)) MB"
    echo "  - Storage: Database=$DB_DISK, App=$APP_DISK each, LB=$LB_DISK"
    echo ""
    read -p "Continue? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 0
    fi
    
    check_prerequisites
    download_cloud_image
    
    # Create VMs
    create_vm $DB_VMID "waybill-db" $DB_VM_IP $DB_CORES $DB_MEMORY $DB_DISK
    create_vm $APP1_VMID "waybill-app1" $APP1_VM_IP $APP_CORES $APP_MEMORY $APP_DISK
    create_vm $APP2_VMID "waybill-app2" $APP2_VM_IP $APP_CORES $APP_MEMORY $APP_DISK
    create_vm $LB_VMID "waybill-lb" $LB_VM_IP $LB_CORES $LB_MEMORY $LB_DISK
    
    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}VM Creation Complete!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Start the VMs:"
    echo "   qm start $DB_VMID && qm start $APP1_VMID && qm start $APP2_VMID && qm start $LB_VMID"
    echo ""
    echo "2. Wait for cloud-init to complete (~2 minutes)"
    echo ""
    echo "3. Test SSH access:"
    echo "   ssh $SSH_USER@$DB_VM_IP"
    echo ""
    echo "4. Run setup scripts for each VM:"
    echo "   ./scripts/database-setup.sh"
    echo "   ./scripts/app-server-setup.sh"
    echo "   ./scripts/loadbalancer-setup.sh"
}

main "$@"
