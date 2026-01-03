#!/bin/bash
# Troubleshoot Upload Errors
# Run this on the app server

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}Starting Upload Troubleshooting...${NC}"

# 1. Check PHP Version and Extensions
PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
echo -e "PHP Version: ${GREEN}$PHP_VER${NC}"

REQUIRED_EXT=("zip" "xml" "gd" "mbstring" "simplexml")
MISSING_EXT=0

echo "Checking extensions..."
for ext in "${REQUIRED_EXT[@]}"; do
    if php -m | grep -i -q "^$ext$"; then
        echo -e "  - $ext: ${GREEN}Installed${NC}"
    else
        echo -e "  - $ext: ${RED}Missing${NC}"
        MISSING_EXT=1
    fi
done

if [ $MISSING_EXT -eq 1 ]; then
    echo -e "${RED}CRITICAL: Missing required PHP extensions for Excel processing!${NC}"
fi

# 2. Check Permissions
echo ""
echo "Checking permissions..."
APP_DIR="/var/www/waybill"

# Check storage/framework/cache
if [ -w "$APP_DIR/storage/framework/cache" ]; then
    echo -e "  - storage/framework/cache: ${GREEN}Writable${NC}"
else
    echo -e "  - storage/framework/cache: ${RED}Not Writable${NC}"
fi

# Check /tmp (often used for uploads)
if [ -w "/tmp" ]; then
    echo -e "  - /tmp: ${GREEN}Writable${NC}"
else
    echo -e "  - /tmp: ${RED}Not Writable${NC}"
fi

# 3. Check Logs
echo ""
echo "Checking Laravel Logs (Last 50 lines)..."
LOG_FILE="$APP_DIR/storage/logs/laravel.log"

if [ -f "$LOG_FILE" ]; then
    echo -e "${YELLOW}--- Start of Log ---${NC}"
    tail -n 50 "$LOG_FILE"
    echo -e "${YELLOW}--- End of Log ---${NC}"
else
    echo -e "${RED}Log file not found at $LOG_FILE${NC}"
fi

# 4. Check Nginx Error Log
echo ""
echo "Checking Nginx Error Log (Last 20 lines)..."
if [ -f "/var/log/nginx/waybill-error.log" ]; then
    tail -n 20 "/var/log/nginx/waybill-error.log"
else
    echo "Nginx error log not found."
fi
