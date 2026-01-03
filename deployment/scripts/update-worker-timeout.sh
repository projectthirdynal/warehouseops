#!/bin/bash
# Load configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="$(dirname "$SCRIPT_DIR")/config.env"
source "$CONFIG_FILE"

update_worker() {
    local SERVER_IP=$1
    local SERVER_NAME=$2
    echo "Updating worker timeout on $SERVER_NAME ($SERVER_IP)..."

    local SSH_PREFIX=""
    if [ -n "$SSH_PASSWORD" ]; then
        SSH_PREFIX="sshpass -p $SSH_PASSWORD"
    fi
    local SSH_CMD="$SSH_PREFIX ssh $SSH_OPTIONS"
    local SUDO_PASS_CMD="echo '$SSH_PASSWORD' | sudo -S"

    # Update the ExecStart line in the systemd service file
    $SSH_CMD $SSH_USER@$SERVER_IP "$SUDO_PASS_CMD sed -i 's/artisan queue:work .*/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=1800/' /etc/systemd/system/waybill-worker.service"
    
    # Reload systemd and restart the service
    $SSH_CMD $SSH_USER@$SERVER_IP "$SUDO_PASS_CMD systemctl daemon-reload"
    $SSH_CMD $SSH_USER@$SERVER_IP "$SUDO_PASS_CMD systemctl restart waybill-worker"
    
    echo "✓ Done for $SERVER_NAME"
}

update_worker "$APP1_VM_IP" "App Server 1"
update_worker "$APP2_VM_IP" "App Server 2"
