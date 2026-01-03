#!/bin/bash
#######################################################################################
# Application Server Setup Script
# Run this on each application server VM to install Laravel application
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
if [ -z "${DB_HOST:-}" ] || [ -z "${DB_NAME:-}" ] || [ -z "${DB_USER:-}" ] || [ -z "${DB_PASSWORD:-}" ]; then
    log_error "Essential database configuration variables are missing"
    exit 1
fi

#######################
# Install PHP and Dependencies
#######################
install_php() {
    echo -e "${YELLOW}Installing PHP 8.4 and extensions...${NC}"
    
    apt-get update
    apt-get install -y software-properties-common
    add-apt-repository -y ppa:ondrej/php
    apt-get update
    
    apt-get install -y \
        php8.4-fpm \
        php8.4-cli \
        php8.4-common \
        php8.4-pgsql \
        php8.4-mbstring \
        php8.4-xml \
        php8.4-zip \
        php8.4-curl \
        php8.4-gd \
        php8.4-bcmath \
        php8.4-intl \
        php8.4-opcache \
        php8.4-dom \
        php8.4-simplexml \
        php8.4-fileinfo \
        php8.4-pdo \
        php8.4-calendar \
        php8.4-ctype \
        php8.4-exif \
        php8.4-ffi \
        php8.4-ftp \
        php8.4-gettext \
        php8.4-iconv \
        php8.4-phar \
        php8.4-posix \
        php8.4-readline \
        php8.4-shmop \
        php8.4-sockets \
        php8.4-sysvmsg \
        php8.4-sysvsem \
        php8.4-sysvshm \
        unzip \
        git \
        curl
    
    echo -e "${GREEN}✓ PHP installed${NC}"
}

#######################
# Install Composer
#######################
install_composer() {
    echo -e "${YELLOW}Installing Composer...${NC}"
    
    if command -v composer &> /dev/null; then
        echo -e "${GREEN}✓ Composer already installed${NC}"
        return 0
    fi
    
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    php -r "unlink('composer-setup.php');"
    
    echo -e "${GREEN}✓ Composer installed${NC}"
}

#######################
# Install Nginx
#######################
install_nginx() {
    echo -e "${YELLOW}Installing Nginx...${NC}"
    
    apt-get install -y nginx
    systemctl enable nginx
    
    echo -e "${GREEN}✓ Nginx installed${NC}"
}

#######################
# Deploy Laravel Application
#######################
deploy_application() {
    echo -e "${YELLOW}Deploying Laravel application...${NC}"
    
    local APP_DIR="/var/www/waybill"
    
    # Create application directory
    mkdir -p $APP_DIR
    
    # Check if source path exists in /tmp
    if [ -d "/tmp/laravel" ]; then
        echo "Copying application from /tmp/laravel..."
        # Copy all files including hidden files
        cp -r /tmp/laravel/. $APP_DIR/
        
        # Verify important files were copied
        if [ ! -f "$APP_DIR/.env.example" ]; then
            echo -e "${RED}Error: .env.example not found after copy${NC}"
            echo "Checking source directory..."
            ls -la /tmp/laravel/.env* || echo ".env files not found in source"
            return 1
        fi
    else
        echo -e "${YELLOW}⚠ Application source not found in /tmp/laravel${NC}"
        echo "Please copy your Laravel application to /tmp/laravel first"
        echo "Or manually copy to $APP_DIR"
        return 1
    fi
    
    # Set ownership
    chown -R www-data:www-data $APP_DIR
    
    # Install dependencies
    cd $APP_DIR
    sudo -u www-data composer install --no-dev --optimize-autoloader
    
    echo -e "${GREEN}✓ Application deployed${NC}"
}

#######################
# Configure Environment
#######################
configure_environment() {
    echo -e "${YELLOW}Configuring environment...${NC}"
    
    local APP_DIR="/var/www/waybill"
    local ENV_FILE="$APP_DIR/.env"
    
    # Copy .env.example if .env doesn't exist
    if [ ! -f "$ENV_FILE" ]; then
        cp $APP_DIR/.env.example $ENV_FILE
    fi
    
    # Generate application key
    cd $APP_DIR
    sudo -u www-data php artisan key:generate --force
    
    # Update database configuration
    sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=pgsql/" $ENV_FILE
    sed -i "s/DB_HOST=.*/DB_HOST=${DB_VM_IP}/" $ENV_FILE
    sed -i "s/DB_PORT=.*/DB_PORT=5432/" $ENV_FILE
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" $ENV_FILE
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" $ENV_FILE
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" $ENV_FILE
    
    # Configure for production
    sed -i "s/APP_ENV=.*/APP_ENV=production/" $ENV_FILE
    sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" $ENV_FILE
    
    # Configure session for load balancing
    sed -i "s/SESSION_DRIVER=.*/SESSION_DRIVER=database/" $ENV_FILE
    sed -i "s/CACHE_STORE=.*/CACHE_STORE=database/" $ENV_FILE
    sed -i "s/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=database/" $ENV_FILE
    
    # Set proper permissions
    chmod 644 $ENV_FILE
    chown www-data:www-data $ENV_FILE
    
    echo -e "${GREEN}✓ Environment configured${NC}"
}

#######################
# Run Laravel Setup
#######################
setup_laravel() {
    echo -e "${YELLOW}Running Laravel setup...${NC}"
    
    local APP_DIR="/var/www/waybill"
    cd $APP_DIR
    
    # Run migrations (continue on error if tables already exist from another app server)
    echo "Running database migrations..."
    sudo -u www-data php artisan migrate --force 2>&1 || echo -e "${YELLOW}Note: Some migrations may have already been run by another app server${NC}"
    
    # Create session table (for load balancing) - ignore if already exists
    sudo -u www-data php artisan session:table 2>/dev/null || true
    sudo -u www-data php artisan migrate --force 2>&1 || true
    
    # Clear and optimize
    sudo -u www-data php artisan config:cache
    sudo -u www-data php artisan route:cache
    sudo -u www-data php artisan view:cache
    
    # Set storage permissions
    chmod -R 775 storage bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache
    
    echo -e "${GREEN}✓ Laravel setup complete${NC}"
}

#######################
# Configure Nginx
#######################
configure_nginx() {
    echo -e "${YELLOW}Configuring Nginx...${NC}"
    
    local APP_DIR="/var/www/waybill"
    local NGINX_CONF="/etc/nginx/sites-available/waybill"
    
    # Remove default site
    rm -f /etc/nginx/sites-enabled/default
    
    # Create Nginx configuration
    cat > $NGINX_CONF <<EOF
server {
    listen 80;
    listen [::]:80;
    
    server_name _;
    root ${APP_DIR}/public;
    index index.php index.html;
    
    # Increase max upload size for Excel files
    client_max_body_size 100M;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    # Logging
    access_log /var/log/nginx/waybill-access.log;
    error_log /var/log/nginx/waybill-error.log;
    
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        
        # Increase timeouts for large uploads
        fastcgi_read_timeout 300;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    # Health check endpoint
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }
}
EOF
    
    # Enable site
    ln -sf $NGINX_CONF /etc/nginx/sites-enabled/
    
    # Test configuration
    nginx -t
    
    # Restart Nginx
    systemctl restart nginx
    
    echo -e "${GREEN}✓ Nginx configured${NC}"
}

#######################
# Configure PHP-FPM
#######################
configure_php_fpm() {
    echo -e "${YELLOW}Optimizing PHP-FPM...${NC}"
    
    local PHP_FPM_CONF="/etc/php/8.4/fpm/pool.d/www.conf"
    
    # Backup original config
    cp $PHP_FPM_CONF ${PHP_FPM_CONF}.backup
    
    # Optimize for production
    sed -i 's/pm = dynamic/pm = static/' $PHP_FPM_CONF
    sed -i 's/pm.max_children = .*/pm.max_children = 20/' $PHP_FPM_CONF
    sed -i 's/;pm.max_requests = .*/pm.max_requests = 500/' $PHP_FPM_CONF
    
    # Increase upload limits
    sed -i 's/upload_max_filesize = .*/upload_max_filesize = 100M/' /etc/php/8.4/fpm/php.ini
    sed -i 's/post_max_size = .*/post_max_size = 100M/' /etc/php/8.4/fpm/php.ini
    sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.4/fpm/php.ini
    sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/8.4/fpm/php.ini
    
    # Restart PHP-FPM
    systemctl restart php8.4-fpm
    
    echo -e "${GREEN}✓ PHP-FPM optimized${NC}"
}

#######################
# Setup Queue Workers
#######################
setup_queue_workers() {
    echo -e "${YELLOW}Setting up queue workers...${NC}"
    
    local APP_DIR="/var/www/waybill"
    
    # Create systemd service for queue worker
    cat > /etc/systemd/system/waybill-worker.service <<EOF
[Unit]
Description=Waybill Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=${APP_DIR}
ExecStart=/usr/bin/php ${APP_DIR}/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=1800
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF
    
    # Enable and start worker
    # Enable and start worker
    systemctl daemon-reload
    systemctl enable waybill-worker
    systemctl restart waybill-worker
    
    echo -e "${GREEN}✓ Queue workers configured${NC}"
}

#######################
# Install Monitoring Tools
#######################
install_monitoring() {
    echo -e "${YELLOW}Installing monitoring tools...${NC}"
    
    apt-get install -y htop iotop net-tools
    
    echo -e "${GREEN}✓ Monitoring tools installed${NC}"
}

#######################
# Display Summary
#######################
display_summary() {
    local SERVER_IP=$(hostname -I | awk '{print $1}')
    
    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}Application Server Setup Complete!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    echo "Server Details:"
    echo "  IP: ${SERVER_IP}"
    echo "  Application: /var/www/waybill"
    echo "  Web Server: Nginx (Port 80)"
    echo ""
    echo "Testing:"
    echo "  Health Check: curl http://${SERVER_IP}/health"
    echo "  Dashboard: http://${SERVER_IP}/"
    echo ""
    echo "Services:"
    echo "  Nginx: systemctl status nginx"
    echo "  PHP-FPM: systemctl status php8.4-fpm"
    echo "  Queue Worker: systemctl status waybill-worker"
    echo ""
    echo "Logs:"
    echo "  Nginx Access: tail -f /var/log/nginx/waybill-access.log"
    echo "  Nginx Error: tail -f /var/log/nginx/waybill-error.log"
    echo "  Laravel: tail -f /var/www/waybill/storage/logs/laravel.log"
    echo ""
}

#######################
# Main Setup
#######################
main() {
    install_php
    install_composer
    install_nginx
    deploy_application
    configure_environment
    setup_laravel
    configure_nginx
    configure_php_fpm
    setup_queue_workers
    install_monitoring
    display_summary
}

main "$@"
