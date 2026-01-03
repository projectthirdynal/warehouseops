# Deployment Checklist - Current Status

## ✅ Completed
- [x] Database VM created (192.168.120.36)
- [x] App Server 1 VM created (192.168.120.33)
- [x] App Server 2 VM created (192.168.120.37)
- [x] Load Balancer VM created (192.168.120.38)
- [x] Load balancer configured and running
- [x] All files copied to Proxmox (/tmp/deployment/ and /tmp/laravel/)
- [x] App server setup script updated with PHP extensions

## 🔄 In Progress
- [x] Database server setup
- [x] App Server 1 setup
- [x] App Server 2 setup

## 📋 Next Steps

### Step 1: Setup Database Server (192.168.120.36)

**From Proxmox:**
```bash
ssh root@192.168.120.155

# Copy files to database VM
scp /tmp/deployment/config.env it-admin@192.168.120.36:/tmp/
scp /tmp/deployment/scripts/database-setup.sh it-admin@192.168.120.36:/tmp/

# Run database setup
ssh it-admin@192.168.120.36 "sudo bash /tmp/database-setup.sh"
```

**Expected time:** 3-5 minutes

**Verification:**
```bash
ssh it-admin@192.168.120.36 "sudo systemctl status postgresql"
ssh it-admin@192.168.120.36 "sudo -u postgres psql -l | grep warehousedb"
```

---

### Step 2: Setup App Server 1 (192.168.120.33)

**From Proxmox:**
```bash
# Copy Laravel application
scp -r /tmp/laravel/* it-admin@192.168.120.33:/tmp/laravel/

# Copy config and setup script
scp /tmp/deployment/config.env it-admin@192.168.120.33:/tmp/
scp /tmp/deployment/scripts/app-server-setup.sh it-admin@192.168.120.33:/tmp/

# Run app server setup
ssh it-admin@192.168.120.33 "sudo bash /tmp/app-server-setup.sh"
```

**Expected time:** 5-7 minutes

**Verification:**
```bash
ssh it-admin@192.168.120.33 "curl -s http://localhost/health"
ssh it-admin@192.168.120.33 "sudo systemctl status nginx php8.2-fpm"
```

---

### Step 3: Setup App Server 2 (192.168.120.37)

**From Proxmox:**
```bash
# Copy Laravel application
scp -r /tmp/laravel/* it-admin@192.168.120.37:/tmp/laravel/

# Copy config and setup script
scp /tmp/deployment/config.env it-admin@192.168.120.37:/tmp/
scp /tmp/deployment/scripts/app-server-setup.sh it-admin@192.168.120.37:/tmp/

# Run app server setup
ssh it-admin@192.168.120.37 "sudo bash /tmp/app-server-setup.sh"
```

**Expected time:** 5-7 minutes

**Verification:**
```bash
ssh it-admin@192.168.120.37 "curl -s http://localhost/health"
```

---

### Step 4: Final Verification

**Test Load Balancer:**
```bash
# From Proxmox or any machine on the network
curl http://192.168.120.38/health
curl http://192.168.120.38

# Test distribution (should alternate between app servers)
for i in {1..10}; do
  curl -s http://192.168.120.38 | grep -o "title.*title" || echo "Request $i"
done
```

**Access Application:**
- Application: http://192.168.120.38
- HAProxy Stats: http://192.168.120.38:8404/stats
  - Username: admin
  - Password: admin

---

## Troubleshooting Quick Reference

### Database Connection Issues
```bash
# On database VM
ssh it-admin@192.168.120.36
sudo ss -tlnp | grep 5432
sudo cat /etc/postgresql/*/main/pg_hba.conf | grep "host all"

# Test from app server
ssh it-admin@192.168.120.33
psql -h 192.168.120.36 -U devuser -d warehousedb
# Password: Devpassword00
```

### Composer/PHP Issues
```bash
# On app server
ssh it-admin@192.168.120.33
php -v  # Should show PHP 8.2.x
php -m | grep -E 'dom|xml|pdo|mbstring'  # Check extensions
cd /var/www/waybill
sudo -u www-data composer install --no-dev
```

### Nginx/Laravel Issues
```bash
# Check logs
sudo tail -f /var/log/nginx/waybill-error.log
sudo tail -f /var/www/waybill/storage/logs/laravel.log

# Fix permissions
sudo chown -R www-data:www-data /var/www/waybill/storage
sudo chmod -R 775 /var/www/waybill/storage
```

### Load Balancer Shows 503
```bash
# Check backend health
ssh it-admin@192.168.120.38
sudo journalctl -u haproxy -n 20
echo "show stat" | sudo socat stdio /var/run/haproxy/admin.sock | grep app_servers
```

---

## Estimated Total Time
- Database setup: 3-5 minutes
- App Server 1: 5-7 minutes  
- App Server 2: 5-7 minutes
- **Total: 13-19 minutes**

---

## Post-Deployment

### Daily Operations

**View Logs:**
```bash
# Application logs
ssh it-admin@192.168.120.33
sudo tail -f /var/www/waybill/storage/logs/laravel.log

# HAProxy stats
open http://192.168.120.38:8404/stats
```

**Backup Database:**
```bash
ssh it-admin@192.168.120.36
sudo /usr/local/bin/backup-waybill-db.sh
# Backups stored in: /var/backups/waybill/
```

**Deploy Updates:**
```bash
# From Proxmox
bash /tmp/deployment/scripts/deploy-app.sh
```

---

## Success Criteria

✅ Database server running and accessible from app servers  
✅ Both app servers serving the Laravel application  
✅ Load balancer distributing traffic evenly  
✅ No 503 errors when accessing http://192.168.120.38  
✅ Can log in and scan waybills  

---

**Ready to deploy? Start with Step 1!** 🚀
