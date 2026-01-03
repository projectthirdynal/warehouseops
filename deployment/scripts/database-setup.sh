#!/bin/bash
#######################################################################################
# Database Server Setup Script
# Run this on the database VM to install and configure PostgreSQL
#######################################################################################

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Log functions
log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Database Server Setup${NC}"
echo -e "${GREEN}========================================${NC}"

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
    log_error "config.env not found in any standard location."
    log_info "Searched: ${CONFIG_PATHS[*]}"
    log_info "Please create a config.env file with DB_NAME, DB_USER, DB_PASSWORD, POSTGRES_VERSION, and BACKUP_DIR."
    exit 1
fi

# Validate essential configuration variables
if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASSWORD" ] || [ -z "$POSTGRES_VERSION" ] || [ -z "$BACKUP_DIR" ]; then
    log_error "One or more essential configuration variables (DB_NAME, DB_USER, DB_PASSWORD, POSTGRES_VERSION, BACKUP_DIR) are not set in config.env."
    exit 1
fi

#######################
# Install PostgreSQL
#######################
install_postgresql() {
    echo -e "${YELLOW}Installing PostgreSQL ${POSTGRES_VERSION}...${NC}"
    
    # Add PostgreSQL repository
    apt-get update
    apt-get install -y wget gnupg2
    
    echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list
    wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add -
    
    apt-get update
    apt-get install -y postgresql-${POSTGRES_VERSION} postgresql-contrib-${POSTGRES_VERSION}
    
    echo -e "${GREEN}✓ PostgreSQL installed${NC}"
}

#######################
# Configure PostgreSQL
#######################
configure_postgresql() {
    echo -e "${YELLOW}Configuring PostgreSQL...${NC}"
    
    local PG_VERSION=$(ls /etc/postgresql/ | head -n1)
    local PG_CONF="/etc/postgresql/${PG_VERSION}/main/postgresql.conf"
    local PG_HBA="/etc/postgresql/${PG_VERSION}/main/pg_hba.conf"
    
    # Backup original configs
    cp $PG_CONF ${PG_CONF}.backup
    cp $PG_HBA ${PG_HBA}.backup
    
    # Configure PostgreSQL for remote connections
    sed -i "s/#listen_addresses = 'localhost'/listen_addresses = '*'/" $PG_CONF
    
    # Performance tuning
    cat >> $PG_CONF <<EOF

# Performance Tuning for Waybill System
shared_buffers = 2GB
effective_cache_size = 6GB
maintenance_work_mem = 512MB
checkpoint_completion_target = 0.9
wal_buffers = 16MB
default_statistics_target = 100
random_page_cost = 1.1
effective_io_concurrency = 200
work_mem = 10485kB
min_wal_size = 1GB
max_wal_size = 4GB
max_connections = 100
EOF
    
    # Allow remote connections from app servers
    echo "# Allow app servers" >> $PG_HBA
    echo "host    all             all             192.168.0.0/16            md5" >> $PG_HBA
    echo "host    all             all             10.0.0.0/8                md5" >> $PG_HBA
    
    # Restart PostgreSQL
    systemctl restart postgresql
    systemctl enable postgresql
    
    echo -e "${GREEN}✓ PostgreSQL configured${NC}"
}

#######################
# Create Database and User
#######################
setup_database() {
    echo -e "${YELLOW}Setting up database and user...${NC}"
    
    sudo -u postgres psql -c "CREATE DATABASE ${DB_NAME};" 2>/dev/null || echo "Database already exists"
    sudo -u postgres psql -c "CREATE USER ${DB_USER} WITH PASSWORD '${DB_PASSWORD}';" 2>/dev/null || echo "User already exists"
    sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};"
    sudo -u postgres psql -c "ALTER DATABASE ${DB_NAME} OWNER TO ${DB_USER};"
    
    echo -e "${GREEN}✓ Database and user created${NC}"
}

#######################
# Import Schema
#######################
import_schema() {
    echo -e "${YELLOW}Importing database schema...${NC}"
    
    if [ -f "/tmp/schema.sql" ]; then
        sudo -u postgres psql -d ${DB_NAME} -f /tmp/schema.sql
        echo -e "${GREEN}✓ Schema imported${NC}"
    else
        echo -e "${YELLOW}⚠ Schema file not found at /tmp/schema.sql${NC}"
        echo "You can import manually later with:"
        echo "  psql -U ${DB_USER} -d ${DB_NAME} -f schema.sql"
    fi
}

#######################
# Migrate from Existing Database (Optional)
#######################
migrate_existing_db() {
    if [ "${MIGRATE_EXISTING_DB}" != "true" ]; then
        return 0
    fi
    
    echo -e "${YELLOW}Migrating from existing database...${NC}"
    
    if [ -z "$EXISTING_DB_HOST" ]; then
        echo -e "${YELLOW}⚠ EXISTING_DB_HOST not set, skipping migration${NC}"
        return 0
    fi
    
    echo "Dumping from ${EXISTING_DB_HOST}..."
    PGPASSWORD="${DB_PASSWORD}" pg_dump -h ${EXISTING_DB_HOST} -U ${DB_USER} ${DB_NAME} > /tmp/db_dump.sql
    
    echo "Importing to local database..."
    sudo -u postgres psql -d ${DB_NAME} -f /tmp/db_dump.sql
    
    rm /tmp/db_dump.sql
    
    echo -e "${GREEN}✓ Database migrated${NC}"
}

#######################
# Setup Backups
#######################
setup_backups() {
    echo -e "${YELLOW}Setting up automated backups...${NC}"
    
    mkdir -p ${BACKUP_DIR}
    chown postgres:postgres ${BACKUP_DIR}
    
    # Create backup script
    # Create backup script
    cat > /usr/local/bin/backup-waybill-db.sh <<EOF
#!/bin/bash
BACKUP_DIR="${BACKUP_DIR}"
DB_NAME="${DB_NAME}"
DB_USER="${DB_USER}"
TIMESTAMP=\$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="\${BACKUP_DIR}/waybill_\${TIMESTAMP}.sql.gz"

# Create backup
sudo -u postgres pg_dump \${DB_NAME} | gzip > \${BACKUP_FILE}

# Remove backups older than 7 days
find \${BACKUP_DIR} -name "waybill_*.sql.gz" -mtime +7 -delete

echo "Backup completed: \${BACKUP_FILE}"
EOF
    
    chmod +x /usr/local/bin/backup-waybill-db.sh
    
    # Add cron job for daily backups at 2 AM
    echo "0 2 * * * root /usr/local/bin/backup-waybill-db.sh >> /var/log/waybill-backup.log 2>&1" > /etc/cron.d/waybill-backup
    
    echo -e "${GREEN}✓ Backup system configured${NC}"
}

#######################
# Install Monitoring Tools
#######################
install_monitoring() {
    echo -e "${YELLOW}Installing monitoring tools...${NC}"
    
    apt-get install -y postgresql-contrib htop iotop
    
    echo -e "${GREEN}✓ Monitoring tools installed${NC}"
}

#######################
# Display Summary
#######################
display_summary() {
    local DB_IP=$(hostname -I | awk '{print $1}')
    
    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}Database Setup Complete!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    echo "Database Details:"
    echo "  Host: ${DB_IP}"
    echo "  Port: 5432"
    echo "  Database: ${DB_NAME}"
    echo "  User: ${DB_USER}"
    echo "  Password: ${DB_PASSWORD}"
    echo ""
    echo "Connection String:"
    echo "  postgresql://${DB_USER}:${DB_PASSWORD}@${DB_IP}:5432/${DB_NAME}"
    echo ""
    echo "Testing:"
    echo "  psql -U ${DB_USER} -d ${DB_NAME} -h localhost"
    echo ""
    echo "Backups:"
    echo "  Location: ${BACKUP_DIR}"
    echo "  Schedule: Daily at 2:00 AM"
    echo "  Manual: /usr/local/bin/backup-waybill-db.sh"
    echo ""
    echo "Monitoring:"
    echo "  Check status: systemctl status postgresql"
    echo "  View logs: tail -f /var/log/postgresql/postgresql-*-main.log"
    echo ""
}

#######################
# Main Setup
#######################
main() {
    install_postgresql
    configure_postgresql
    setup_database
    import_schema
    migrate_existing_db
    setup_backups
    install_monitoring
    display_summary
}

main "$@"
