# Infrastructure Startup & Management Guide

This guide provides the necessary commands to start, restart, and monitor the various services across the entire infrastructure.

## 🔌 VM Management (From Proxmox Host)

If the virtual machines are powered off, start them in this order from the **Proxmox Host** (`192.168.120.155`):

| VM Role | ID | Command to Start |
| :--- | :--- | :--- |
| **Database** | `800` | `qm start 800` |
| **App Server 1** | `801` | `qm start 801` |
| **App Server 2** | `802` | `qm start 802` |
| **Load Balancer** | `803` | `qm start 803` |

> [!TIP]
> You can check the status of all VMs using:
> `bash /home/it-admin/Documents/v4/v4/deployment/scripts/verify-vms.sh`

---

## 🚀 Quick Service Restart (From Proxmox)

To restart the application services (Nginx, PHP, Workers) on both App Servers simultaneously, run this command from the **Proxmox Host** (`192.168.120.155`):

```bash
bash /home/it-admin/Documents/v4/v4/deployment/scripts/restart-services.sh
```

---

## 🛠️ Operational Maintenance Commands

The following commands are essential for the daily operation of the Agent System. They are scheduled to run automatically, but can be run manually if needed.

| Command | Schedule | Purpose |
| :--- | :--- | :--- |
| `php artisan leads:score` | 00:00 | **Archiving**: Decays lead scores and archives dead leads. |
| `php artisan leads:analyze-agents` | 01:00 | **Governance**: Flags Recycle Abuse and Low Contact rates. |
| `php artisan leads:guardian-audit` | 02:00 | **Audit**: Detects system drift stuck leads. |
| `php artisan leads:snapshot-active` | 03:00 | **Safety**: Backs up all active lead states. |
| `php artisan leads:restore {id}` | Manual | **Recovery**: Restores a lead to a previous snapshot state. |

---

## 🏗️ Manual Service Management

If you need to manage services on individual nodes, use the following commands.

### 1. Database Node (`192.168.120.36`)
The database must be running before the application servers can successfully connect.

| Action | Command |
| :--- | :--- |
| **Start/Restart** | `sudo systemctl restart postgresql` |
| **Check Status** | `sudo systemctl status postgresql` |
| **View Logs** | `sudo tail -f /var/log/postgresql/postgresql-16-main.log` |

### 2. Application Servers (`192.168.120.33` & `192.168.120.37`)
Manage the web server, PHP processor, and background queue workers.

| Service | Action | Command |
| :--- | :--- | :--- |
| **Nginx** (Web) | Restart | `sudo systemctl restart nginx` |
| **PHP-FPM** | Restart | `sudo systemctl restart php8.4-fpm` |
| **Worker** (Queue) | Restart | `sudo systemctl restart waybill-worker` |

### 3. Load Balancer (`192.168.120.38`)
The entrance to the application.

| Action | Command |
| :--- | :--- |
| **Start/Restart** | `sudo systemctl restart haproxy` |
| **Check Status** | `sudo systemctl status haproxy` |
| **Test Config** | `sudo haproxy -c -f /etc/haproxy/haproxy.cfg` |
| **Stats URL** | `http://192.168.120.38:8404/stats` (Admin/Admin123) |

---

## 🔍 Verification Checklist

After starting the services, verify the system health:

1. **Check Load Balancer Connectivity**:
   ```bash
   curl -I http://192.168.120.38
   ```
2. **Check Database Connectivity** (from App Server):
   ```bash
   nc -zv 192.168.120.36 5432
   ```
3. **Verify Queue Processing**:
   ```bash
   php artisan queue:active
   ```
