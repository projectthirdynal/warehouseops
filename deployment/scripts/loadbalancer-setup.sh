#!/bin/bash
#######################################################################################
# Load Balancer Setup Script
# Run this on the load balancer VM to install and configure HAProxy
#######################################################################################

set -euo pipefail

# Color output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

log_info "========================================"
log_info "Load Balancer Setup"
log_info "========================================"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    log_error "Please run as root or with sudo"
    exit 1
fi

# Load configuration
CONFIG_PATHS=("/tmp/config.env" "./config.env" "../config.env" "../../config.env")
CONFIG_LOADED=false

for config_path in "${CONFIG_PATHS[@]}"; do
    if [ -f "$config_path" ]; then
        log_info "Loading configuration from $config_path"
        source "$config_path"
        CONFIG_LOADED=true
        break
    fi
done

if [ "$CONFIG_LOADED" = false ]; then
    log_error "config.env not found in any standard location"
    log_info "Searched: ${CONFIG_PATHS[*]}"
    exit 1
fi

# Validate essential configuration
if [ -z "${APP1_VM_IP:-}" ] || [ -z "${APP2_VM_IP:-}" ]; then
    log_error "APP1_VM_IP and APP2_VM_IP must be set in config.env"
    exit 1
fi

#######################
# Install HAProxy
#######################
install_haproxy() {
    echo -e "${YELLOW}Installing HAProxy...${NC}"
    
    apt-get update
    apt-get install -y haproxy
    
    systemctl enable haproxy
    
    echo -e "${GREEN}✓ HAProxy installed${NC}"
}

#######################
# Configure HAProxy
#######################
configure_haproxy() {
    echo -e "${YELLOW}Configuring HAProxy...${NC}"
    
    local HAPROXY_CONF="/etc/haproxy/haproxy.cfg"
    
    # Backup original config
    cp $HAPROXY_CONF ${HAPROXY_CONF}.backup
    
    # Check if custom config exists
    if [ -f "/tmp/haproxy.cfg" ]; then
        echo "Using custom HAProxy configuration..."
        cp /tmp/haproxy.cfg $HAPROXY_CONF
    else
        # Create new configuration
        cat > $HAPROXY_CONF <<EOF
global
    log /dev/log local0
    log /dev/log local1 notice
    chroot /var/lib/haproxy
    stats socket /run/haproxy/admin.sock mode 660 level admin expose-fd listeners
    stats timeout 30s
    user haproxy
    group haproxy
    daemon

    # Default SSL material locations
    ca-base /etc/ssl/certs
    crt-base /etc/ssl/private

    # Performance tuning
    maxconn 4096
    tune.ssl.default-dh-param 2048

defaults
    log     global
    mode    http
    option  httplog
    option  dontlognull
    option  http-server-close
    option  forwardfor except 127.0.0.0/8
    option  redispatch
    retries 3
    timeout connect 5000
    timeout client  50000
    timeout server  50000
    errorfile 400 /etc/haproxy/errors/400.http
    errorfile 403 /etc/haproxy/errors/403.http
    errorfile 408 /etc/haproxy/errors/408.http
    errorfile 500 /etc/haproxy/errors/500.http
    errorfile 502 /etc/haproxy/errors/502.http
    errorfile 503 /etc/haproxy/errors/503.http
    errorfile 504 /etc/haproxy/errors/504.http

# Statistics Dashboard
listen stats
    bind *:8404
    stats enable
    stats uri /stats
    stats refresh 10s
    stats admin if TRUE
    stats auth admin:admin123
    stats show-legends
    stats show-node

# Frontend - Accepts incoming traffic
frontend waybill_frontend
    bind *:80
    
    # Set X-Forwarded headers
    http-request set-header X-Forwarded-Port %[dst_port]
    http-request add-header X-Forwarded-Proto https if { ssl_fc }
    
    # Use backend
    default_backend waybill_backend

# Backend - Application servers
backend waybill_backend
    balance roundrobin
    
    # Sticky session using cookie
    cookie SERVERID insert indirect nocache
    
    # Health checks
    option httpchk GET /health
    http-check expect status 200
    
    # Application servers
    server app1 ${APP1_VM_IP}:80 check cookie app1 inter 3s fall 3 rise 2
    server app2 ${APP2_VM_IP}:80 check cookie app2 inter 3s fall 3 rise 2
EOF
    fi
    
    # Test configuration
    haproxy -c -f $HAPROXY_CONF
    
    # Restart HAProxy
    systemctl restart haproxy
    
    echo -e "${GREEN}✓ HAProxy configured${NC}"
}

#######################
# Configure Firewall (Optional)
#######################
configure_firewall() {
    echo -e "${YELLOW}Configuring firewall...${NC}"
    
    if command -v ufw &> /dev/null; then
        ufw allow 80/tcp  # HTTP
        ufw allow 8404/tcp  # Statistics
        ufw allow 22/tcp  # SSH
        
        echo -e "${GREEN}✓ Firewall configured${NC}"
    else
        echo -e "${YELLOW}⚠ UFW not installed, skipping firewall configuration${NC}"
    fi
}

#######################
# Install Monitoring Tools
#######################
install_monitoring() {
    echo -e "${YELLOW}Installing monitoring tools...${NC}"
    
    apt-get install -y htop net-tools curl
    
    echo -e "${GREEN}✓ Monitoring tools installed${NC}"
}

#######################
# Test Load Balancer
#######################
test_load_balancer() {
    echo -e "${YELLOW}Testing load balancer...${NC}"
    
    sleep 3  # Wait for HAProxy to fully start
    
    local LB_IP=$(hostname -I | awk '{print $1}')
    
    echo "Testing health checks..."
    if curl -s http://localhost:80/ > /dev/null; then
        echo -e "${GREEN}✓ Load balancer is responding${NC}"
    else
        echo -e "${YELLOW}⚠ Load balancer test inconclusive (app servers may not be ready)${NC}"
    fi
}

#######################
# Display Summary
#######################
display_summary() {
    local LB_IP=$(hostname -I | awk '{print $1}')
    
    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}Load Balancer Setup Complete!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    echo "Load Balancer Details:"
    echo "  IP: ${LB_IP}"
    echo "  HTTP Port: 80"
    echo "  Stats Dashboard: http://${LB_IP}:8404/stats"
    echo "    Username: admin"
    echo "    Password: admin123"
    echo ""
    echo "Backend Servers:"
    echo "  App Server 1: ${APP1_VM_IP}:80"
    echo "  App Server 2: ${APP2_VM_IP}:80"
    echo ""
    echo "Configuration:"
    echo "  Algorithm: Round Robin"
    echo "  Sticky Sessions: Enabled (cookie-based)"
    echo "  Health Checks: Every 3 seconds on /health"
    echo ""
    echo "Testing:"
    echo "  Access application: http://${LB_IP}/"
    echo "  Check stats: http://${LB_IP}:8404/stats"
    echo "  Test distribution:"
    echo "    for i in {1..10}; do curl -s http://${LB_IP}/ | grep -o 'app[12]' || echo 'request'; done"
    echo ""
    echo "Services:"
    echo "  HAProxy: systemctl status haproxy"
    echo "  Config test: haproxy -c -f /etc/haproxy/haproxy.cfg"
    echo ""
    echo "Logs:"
    echo "  tail -f /var/log/haproxy.log"
    echo ""
}

#######################
# Main Setup
#######################
main() {
    install_haproxy
    configure_haproxy
    configure_firewall
    install_monitoring
    test_load_balancer
    display_summary
}

main "$@"
