# Proxmox Deployment - Quick Start

This is a quick reference guide. For detailed instructions, see [DEPLOYMENT.md](file:///home/it-admin/Documents/v4/v4/deployment/docs/DEPLOYMENT.md).

## Prerequisites

- [ ] Proxmox VE server with sufficient resources (10 vCPU, 18GB RAM, 130GB storage)
- [ ] 4 available IP addresses on your network
- [ ] SSH key generated (`~/.ssh/id_rsa.pub`)
- [ ] Laravel application ready at `/home/it-admin/Documents/v4/v4/laravel`

## Quick Deployment

### 1. Configure Settings

```bash
cd /home/it-admin/Documents/v4/v4/deployment
nano config.env
```

Update these variables:
- IP addresses for all 4 VMs
- Proxmox node name and storage pool
- Database password

### 2. Create VMs (on Proxmox host)

```bash
# Copy files to Proxmox
scp -r /home/it-admin/Documents/v4/v4/deployment root@<proxmox-host>:/tmp/

# On Proxmox host
cd /tmp/deployment
./scripts/vm-setup.sh

# Start VMs
qm start 200 && qm start 201 && qm start 202 && qm start 203
```

Wait 2-3 minutes for cloud-init.

### 3. Setup Database

```bash
scp config.env ubuntu@<DB_IP>:/tmp/
ssh ubuntu@<DB_IP>
sudo bash /tmp/database-setup.sh
```

### 4. Setup App Servers (both)cd 

```bash
# For each app server
scp -r /home/it-admin/Documents/v4/v4/laravel/* ubuntu@<APP_IP>:/tmp/laravel/
scp config.env ubuntu@<APP_IP>:/tmp/
ssh ubuntu@<APP_IP>
sudo bash /tmp/app-server-setup.sh
```

### 5. Setup Load Balancer

```bash
scp deployment/config/haproxy.cfg ubuntu@<LB_IP>:/tmp/
scp config.env ubuntu@<LB_IP>:/tmp/
ssh ubuntu@<LB_IP>
sudo bash /tmp/loadbalancer-setup.sh
```

### 6. Test

```bash
cd /home/it-admin/Documents/v4/v4/deployment
./scripts/test-load-balancer.sh <LB_IP>
```

Open browser: `http://<LB_IP>/`

## Common Commands

**Check VM Status:**
```bash
./scripts/verify-vms.sh
```

**Deploy Updates:**
```bash
./scripts/deploy-app.sh
```

**View HAProxy Stats:**
```
http://<LB_IP>:8404/stats
(admin / admin123)
```

**Check Logs:**
```bash
# App server
ssh ubuntu@<APP_IP>
tail -f /var/www/waybill/storage/logs/laravel.log

# Load balancer
ssh ubuntu@<LB_IP>
tail -f /var/log/haproxy.log
```

## Troubleshooting

| Issue | Check | Fix |
|-------|-------|-----|
| App not loading | HAProxy stats | Restart services |
| 502 Bad Gateway | App server health | `systemctl restart nginx php8.2-fpm` |
| DB connection error | PostgreSQL running | `systemctl restart postgresql` |
| Load not balanced | HAProxy config | Check backend IPs |

## File Structure

```
deployment/
├── config.env              # Main configuration
├── scripts/
│   ├── vm-setup.sh         # Create VMs
│   ├── database-setup.sh   # Setup PostgreSQL
│   ├── app-server-setup.sh # Setup Laravel app
│   ├── loadbalancer-setup.sh # Setup HAProxy
│   ├── deploy-app.sh       # Deploy updates
│   ├── test-load-balancer.sh # Test load balancing
│   └── verify-vms.sh       # Verify VM status
├── config/
│   ├── haproxy.cfg         # HAProxy template
│   └── nginx-site.conf     # Nginx template
└── docs/
    ├── DEPLOYMENT.md       # Full guide
    ├── ARCHITECTURE.md     # System architecture
    └── README.md           # This file
```

## Support

For detailed documentation, see:
- **Deployment Guide:** [DEPLOYMENT.md](file:///home/it-admin/Documents/v4/v4/deployment/docs/DEPLOYMENT.md)
- **Architecture:** [ARCHITECTURE.md](file:///home/it-admin/Documents/v4/v4/deployment/docs/ARCHITECTURE.md)
- **Implementation Plan:** [implementation_plan.md](file:///home/it-admin/.gemini/antigravity/brain/5523c9e1-6206-46a5-b46b-df8b21f63fe0/implementation_plan.md)
