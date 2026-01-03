#!/bin/bash
# Verify permissions script
# Run this on the app server to check if permissions are correct

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

APP_DIR="/var/www/waybill"

echo -e "${YELLOW}Verifying permissions for $APP_DIR...${NC}"

# Check ownership of the main directory
OWNER=$(stat -c '%U:%G' $APP_DIR)
if [ "$OWNER" == "www-data:www-data" ]; then
    echo -e "${GREEN}✓ Main directory owned by www-data:www-data${NC}"
else
    echo -e "${RED}✗ Main directory owned by $OWNER (expected www-data:www-data)${NC}"
fi

# Check storage permissions
STORAGE_PERM=$(stat -c '%a' $APP_DIR/storage)
if [[ "$STORAGE_PERM" == "775" || "$STORAGE_PERM" == "777" ]]; then
    echo -e "${GREEN}✓ Storage directory permissions are $STORAGE_PERM${NC}"
else
    echo -e "${RED}✗ Storage directory permissions are $STORAGE_PERM (expected 775)${NC}"
fi

# Check if critical files are owned by www-data
FAILED=0
for file in "$APP_DIR/.env" "$APP_DIR/storage/logs/laravel.log"; do
    if [ -f "$file" ]; then
        FILE_OWNER=$(stat -c '%U' "$file")
        if [ "$FILE_OWNER" == "www-data" ]; then
            echo -e "${GREEN}✓ $file owned by www-data${NC}"
        else
            echo -e "${RED}✗ $file owned by $FILE_OWNER (expected www-data)${NC}"
            FAILED=1
        fi
    fi
done

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}All checks passed!${NC}"
else
    echo -e "${RED}Some checks failed. Run 'chown -R www-data:www-data /var/www/waybill' to fix.${NC}"
    exit 1
fi
