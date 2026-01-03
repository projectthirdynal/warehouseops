# Waybill Scanning System

A modern PHP-based waybill scanning and dispatch management system with PostgreSQL database.

## Features

- 📤 **Bulk Upload**: Import waybill data from XLSX files (5000+ rows supported)
- 📱 **Barcode Scanner**: Scan waybills for dispatch with barcode scanner support
- 📊 **Dashboard**: Real-time statistics and tracking
- 🔍 **Search & Filter**: Advanced search capabilities
- 🎨 **Modern UI**: Beautiful dark-themed interface with smooth animations
- 🔔 **Audio Feedback**: Beep sounds for scan confirmation

## Requirements

- PHP 7.4 or higher
- PostgreSQL 12 or higher
- Composer
- Web server (Apache/Nginx)
- PHP extensions: pdo_pgsql, mbstring, zip, xml

## Installation

### 1. Clone or Download

```bash
cd /home/it-admin/Documents/v3
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Setup PostgreSQL Database

```bash
# Create database
sudo -u postgres createdb waybill_system

# Import schema
sudo -u postgres psql waybill_system < database/schema.sql
```

### 4. Configure Database Connection

Edit `config/database.php` and update your credentials:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'waybill_system');
define('DB_USER', 'postgres');
define('DB_PASS', 'your_password');
```

### 5. Start PHP Development Server

```bash
php -S localhost:8000
```

### 6. Access the Application

Open your browser and navigate to:
```
http://localhost:8000
```

## Excel File Format

The XLSX file should have the following columns (in order):

1. Waybill Number
2. Sender Name
3. Sender Address
4. Sender Phone
5. Receiver Name
6. Receiver Address
7. Receiver Phone
8. Destination
9. Weight (kg)
10. Quantity
11. Service Type
12. COD Amount
13. Remarks

## Usage

### Upload Waybills

1. Go to **Upload** page
2. Select or drag-and-drop your XLSX file
3. Click **Upload File**
4. Wait for processing to complete

### Scan Waybills

1. Go to **Scanner** page
2. Use a barcode scanner or manually enter waybill number
3. Click **Scan Waybill** or press Enter
4. System will validate and mark as dispatched

### View Waybills

1. Go to **Waybills** page
2. Search by waybill number, sender, receiver, or destination
3. Filter by status (Pending/Dispatched)

## Database Schema

- **uploads**: Track batch uploads
- **waybills**: Store individual waybill records
- **scanned_waybills**: Track dispatch scans

## File Structure

```
v3/
├── api/                    # API endpoints
├── assets/
│   ├── css/               # Stylesheets
│   └── js/                # JavaScript files
├── config/                # Configuration files
├── database/              # Database schema
├── includes/              # PHP includes and functions
├── index.php              # Dashboard
├── scanner.php            # Scanner interface
├── upload-page.php        # Upload interface
├── waybills.php           # Waybills list
├── upload.php             # Upload handler
├── scan.php               # Scan handler
└── composer.json          # PHP dependencies
```

## Security Notes

- Update database credentials in `config/database.php`
- Set proper file permissions
- Use HTTPS in production
- Implement user authentication for production use
- Validate and sanitize all inputs

## Troubleshooting

### PostgreSQL Connection Error

```bash
# Check if PostgreSQL is running
sudo systemctl status postgresql

# Start PostgreSQL
sudo systemctl start postgresql
```

### Permission Issues

```bash
# Set proper permissions
chmod -R 755 /home/it-admin/Documents/v3
```

### PHP Extensions Missing

```bash
# Install required extensions (Ubuntu/Debian)
sudo apt-get install php-pgsql php-mbstring php-zip php-xml
```

## License

This project is open source and available under the MIT License.

## Support

For issues and feature requests, please contact your system administrator.
