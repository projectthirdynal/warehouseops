# Manual VM Setup Guide

This guide is for manually setting up your Laravel Waybill Scanning System on existing VMs.

## Prerequisites

You have 4 VMs already created:
- **Database VM** - PostgreSQL server
- **App Server 1** - Laravel application
- **App Server 2** - Laravel application  
- **Load Balancer** - HAProxy

## Quick Setup Steps

### 1. Update config.env

Edit `/root/deployment/config.env` (or wherever you placed it) with your actual VM IPs and settings:

```bash
# VM IP Addresses
DB_VM_IP="192.168.120.28"
APP1_VM_IP="192.168.120.30"
APP2_VM_IP="192.168.120.31"
LB_VM_IP="192.168.120.32"

# Database Configuration
DB_HOST="192.168.120.28"
DB_NAME="warehousedb"
DB_USER="devuser"
DB_PASSWORD="Devpassword00"

# Application
APP_NAME="Waybill Scanning System"
APP_URL="http://192.168.120.32"
APP_KEY=""  # Will be generated
```

### 2. Database VM Setup (192.168.120.28)

```bash
# Copy files to database VM
scp /root/deployment/config.env ubuntu@192.168.120.28:/tmp/
scp /root/deployment/scripts/database-setup.sh ubuntu@192.168.120.28:/tmp/

# SSH to database VM
ssh ubuntu@192.168.120.28

# Run setup script
sudo bash /tmp/database-setup.sh

# Verify PostgreSQL is running
sudo systemctl status postgresql
sudo -u postgres psql -c "\l" | grep warehousedb
```

**What this does:**
- Installs PostgreSQL 16
- Creates `warehousedb` database
- Creates `devuser` with password
- Configures remote access
- Sets up automated backups

### 3. App Server 1 Setup (192.168.120.30)

```bash
# Copy Laravel application files
scp -r /tmp/laravel/* ubuntu@192.168.120.30:/tmp/laravel/

# Copy config and setup script
scp /root/deployment/config.env ubuntu@192.168.120.30:/tmp/
scp /root/deployment/scripts/app-server-setup.sh ubuntu@192.168.120.30:/tmp/
scp /root/deployment/config/nginx-site.conf ubuntu@192.168.120.30:/tmp/

# SSH to app server 1
ssh ubuntu@192.168.120.30

# Run setup script
sudo bash /tmp/app-server-setup.sh

# Verify services are running
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status waybill-worker
```

**What this does:**
- Installs PHP 8.2, Nginx, Composer
- Deploys Laravel application to `/var/www/waybill`
- Runs migrations
- Configures Nginx
- Sets up queue workers

### 4. App Server 2 Setup (192.168.120.31)

```bash
# Repeat same steps as App Server 1
scp -r /tmp/laravel/* ubuntu@192.168.120.31:/tmp/laravel/
scp /root/deployment/config.env ubuntu@192.168.120.31:/tmp/
scp /root/deployment/scripts/app-server-setup.sh ubuntu@192.168.120.31:/tmp/
scp /root/deployment/config/nginx-site.conf ubuntu@192.168.120.31:/tmp/

ssh ubuntu@192.168.120.31
sudo bash /tmp/app-server-setup.sh
```

### 5. Load Balancer Setup (192.168.120.32)

```bash
# Copy config and setup script
scp /root/deployment/config.env ubuntu@192.168.120.32:/tmp/
scp /root/deployment/scripts/loadbalancer-setup.sh ubuntu@192.168.120.32:/tmp/
scp /root/deployment/config/haproxy.cfg ubuntu@192.168.120.32:/tmp/

# SSH to load balancer
ssh ubuntu@192.168.120.32

# Run setup script
sudo bash /tmp/loadbalancer-setup.sh

# Verify HAProxy is running
sudo systemctl status haproxy

# Check HAProxy stats (optional)
curl http://localhost:8404/stats
```

**What this does:**
- Installs HAProxy
- Configures load balancing with sticky sessions
- Sets up health checks
- Enables statistics dashboard

### 6. Testing

From any machine on the network:

```bash
# Test load balancer
curl http://192.168.120.32

# Test direct access to app servers
curl http://192.168.120.30
curl http://192.168.120.31

# Test sticky sessions
bash /root/deployment/scripts/test-load-balancer.sh
```

Access the application in your browser:
```
http://192.168.120.32
```

HAProxy statistics dashboard:
```
http://192.168.120.32:8404/stats
Username: admin
Password: (from config.env)
```

## Simplified File Distribution

If you're working from Proxmox and already have files there:

```bash
# On Proxmox - distribute config to all VMs
for vm_ip in 192.168.120.28 192.168.120.30 192.168.120.31 192.168.120.32; do
  scp /root/deployment/config.env ubuntu@$vm_ip:/tmp/
done

# Copy Laravel to app servers only
for vm_ip in 192.168.120.30 192.168.120.31; do
  scp -r /tmp/laravel/* ubuntu@$vm_ip:/tmp/laravel/
done

# Copy setup scripts to respective VMs
scp /root/deployment/scripts/database-setup.sh ubuntu@192.168.120.28:/tmp/
scp /root/deployment/scripts/app-server-setup.sh ubuntu@192.168.120.30:/tmp/
scp /root/deployment/scripts/app-server-setup.sh ubuntu@192.168.120.31:/tmp/
scp /root/deployment/scripts/loadbalancer-setup.sh ubuntu@192.168.120.32:/tmp/
```

## Configuration Notes

The setup scripts now automatically search for `config.env` in multiple locations:
- `/tmp/config.env` (default)
- `./config.env` (current directory)
- `../config.env` (parent directory)
- `../../config.env` (two levels up)

So you can place `config.env` anywhere convenient and run the scripts from any location.

## Troubleshooting

### Script can't find config.env
```bash
# Verify file exists
ls -la /tmp/config.env

# Or place it in the same directory as the script
cp /tmp/config.env /tmp/database-setup.sh
```

### Database connection fails
```bash
# On database VM, check PostgreSQL is listening
sudo netstat -tlnp | grep 5432

# Check pg_hba.conf allows remote connections
sudo cat /etc/postgresql/16/main/pg_hba.conf | grep "host all"

# Test connection from app server
psql -h 192.168.120.28 -U devuser -d warehousedb
```

### HAProxy shows backend servers as DOWN
```bash
# Check app servers are responding on port 80
curl http://192.168.120.30/health
curl http://192.168.120.31/health

# Check HAProxy logs
sudo journalctl -u haproxy -f
```

### Nginx permission errors
```bash
# Fix Laravel storage permissions
sudo chown -R www-data:www-data /var/www/waybill/storage
sudo chmod -R 775 /var/www/waybill/storage
```

## Future Updates

To deploy application updates:

```bash
# From Proxmox or any management machine
bash /root/deployment/scripts/deploy-app.sh
```

This performs zero-downtime rolling deployment across both app servers.

## Network Diagram

```
[Users] 
   │
   ├─> [Load Balancer - 192.168.120.32]
         │
         ├─> [App Server 1 - 192.168.120.30] ──┐
         │                                      │
         └─> [App Server 2 - 192.168.120.31] ──┤
                                                │
                                                ├─> [Database - 192.168.120.28]
```

## Security Checklist

- [ ] Database only accessible from app servers
- [ ] Strong database password set
- [ ] Firewall configured (optional for internal use)
- [ ] Regular backups enabled (automated daily)
- [ ] SSH keys configured (instead of passwords)
- [ ] Application in production mode (`APP_ENV=production`)
