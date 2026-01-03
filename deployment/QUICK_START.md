# Quick Start Deployment Guide

This guide walks you through the end-to-end deployment process, starting from your local development machine.

## 📋 System Overview

| Role | IP Address | Details |
|------|------------|---------|
| **Proxmox Host** | `192.168.120.155` | The gateway and VM manager |
| **Database** | `192.168.120.36` | PostgreSQL 16 |
| **App Server 1** | `192.168.120.33` | Laravel (Worker 1) |
| **App Server 2** | `192.168.120.37` | Laravel (Worker 2) |
| **Load Balancer** | `192.168.120.38` | HAProxy |

---

## 🚀 Phase 1: Upload Files (From Local Machine)

Run these commands on your **local computer** to transfer the project files to the Proxmox host.

### 1. Clean Staging Area
Clear any old files on the Proxmox server to ensure a clean transfer.
```bash
ssh root@192.168.120.155 "rm -rf /tmp/laravel /tmp/deployment"
```

### 2. Copy Project Files
Upload the application code and deployment scripts.
```bash
# Copy Laravel application
scp -r /home/it-admin/Documents/v4/v4/laravel root@192.168.120.155:/tmp/laravel

# Copy deployment tools
scp -r /home/it-admin/Documents/v4/v4/deployment root@192.168.120.155:/tmp/
```

---

## 🛠️ Phase 2: Deployment (From Proxmox)

Login to the Proxmox host to execute the deployment scripts.

```bash
ssh root@192.168.120.155
```

*All subsequent commands are run inside this SSH session.*

### Option A: Standard Update (Rolling Deploy)
Use this if the infrastructure is already set up and you just want to update the code.
```bash
bash /tmp/deployment/scripts/deploy-all-app-servers.sh
```

### Option B: Fresh Installation (Reset App Servers)
Use this to **wipe** the app servers and reinstall from scratch. (Keeps database intact).
```bash
bash /tmp/deployment/scripts/fresh-install.sh
```

### Option C: Complete First Time Setup
If this is the very first time building the infrastructure, run these in order:

**1. Setup Database**
```bash
# Copy setup scripts to DB VM
scp /tmp/deployment/config.env it-admin@192.168.120.36:/tmp/
scp /tmp/deployment/scripts/database-setup.sh it-admin@192.168.120.36:/tmp/

# Run setup
ssh it-admin@192.168.120.36 "sudo bash /tmp/database-setup.sh"
```

**2. Setup Load Balancer**
```bash
# Copy setup scripts to LB VM
scp /tmp/deployment/config.env ubuntu@192.168.120.38:/tmp/
scp /tmp/deployment/scripts/loadbalancer-setup.sh ubuntu@192.168.120.38:/tmp/

# Run setup
ssh ubuntu@192.168.120.38 "sudo bash /tmp/loadbalancer-setup.sh"
```

**3. Deploy App Servers**
```bash
# This sets up both App 1 and App 2
bash /tmp/deployment/scripts/fresh-install.sh
```

### Option D: Manual Deployment (If Scripts Fail)
If you encounter `sudo: a terminal is required` errors, run these commands manually from Proxmox. The `-t` flag is key.

**App Server 1 (192.168.120.33)**
```bash
# 1. Clean & Copy
ssh it-admin@192.168.120.33 "rm -rf /tmp/laravel"
scp -r /tmp/laravel it-admin@192.168.120.33:/tmp/
scp /tmp/deployment/config.env /tmp/deployment/scripts/app-server-setup.sh it-admin@192.168.120.33:/tmp/

# 2. Run Setup (Force TTY with -t)
ssh -t it-admin@192.168.120.33 "sudo bash /tmp/app-server-setup.sh"
```

**App Server 2 (192.168.120.37)**
```bash
# 1. Clean & Copy
ssh it-admin@192.168.120.37 "rm -rf /tmp/laravel"
scp -r /tmp/laravel it-admin@192.168.120.37:/tmp/
scp /tmp/deployment/config.env /tmp/deployment/scripts/app-server-setup.sh it-admin@192.168.120.37:/tmp/

# 2. Run Setup (Force TTY with -t)
ssh -t it-admin@192.168.120.37 "sudo bash /tmp/app-server-setup.sh"
```

---

## ✅ Phase 3: Verification

Once deployment is finished, the application is live.

**Access URL:**
```
http://192.168.120.38
```

**Check Health:**
```bash
# Test Load Balancer
curl http://192.168.120.38/health

# Test Individual Nodes (from Proxmox)
curl http://192.168.120.33/health
curl http://192.168.120.37/health
```

**View Statistics:**
*   **HAProxy Stats:** `http://192.168.120.38:8404/stats` (User: `admin`, Pass: `admin`)

---

## 🔧 Troubleshooting

**Database Connectivity**
```bash
# Check from App Server
ssh it-admin@192.168.120.33 "nc -zv 192.168.120.36 5432"
```

**View Logs**
```bash
# App Server Logs
ssh it-admin@192.168.120.33 "tail -f /var/www/waybill/storage/logs/laravel.log"

# Nginx Logs
ssh it-admin@192.168.120.33 "sudo tail -f /var/log/nginx/error.log"
```
