# Waybill Scanning System

A comprehensive Laravel-based warehouse operations management system for tracking and managing waybills throughout the logistics lifecycle.

![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15+-336791?style=flat-square&logo=postgresql)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

## 📋 Overview

The Waybill Scanning System streamlines warehouse operations by providing real-time tracking, batch scanning, and comprehensive reporting for waybill management. Built with Laravel 11 and deployed on a high-availability cluster.

### Key Features

- **📊 Real-Time Dashboard** - Monitor waybill statuses with live statistics
- **📦 Batch Scanning** - Process multiple waybills efficiently with barcode scanning
- **📁 Excel Import** - Bulk upload waybills via Excel files
- **📈 Status Monitoring** - Track IN TRANSIT, DELIVERED, DELIVERING, RETURNED, and HQ Scheduling statuses
- **📝 Manifest Generation** - Automatically generate dispatch manifests
- **📜 History Tracking** - Access past batch sessions and manifests
- **🔍 Advanced Search** - Filter and search waybills by multiple criteria
- **👥 User Management** - Role-based access control for staff

## 🎯 System Architecture

### Deployment

- **Load Balancer**: HAProxy (192.168.120.38)
- **App Server 1**: 192.168.120.33
- **App Server 2**: 192.168.120.37
- **Database**: PostgreSQL 15+ (centralized)
- **Web Server**: Nginx + PHP-FPM 8.2

### Technologies

- **Backend**: Laravel 11.x
- **Database**: PostgreSQL with Eloquent ORM
- **Frontend**: Blade Templates, Vanilla JavaScript
- **Styling**: Custom CSS with modern dark theme
- **File Processing**: PhpSpreadsheet for Excel imports
- **Session Storage**: LocalStorage for manifest persistence

## 🚀 Installation

### Prerequisites

- PHP >= 8.2
- PostgreSQL >= 15
- Composer
- Node.js & NPM (for asset compilation)
- Nginx or Apache

### Local Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/projectthirdynal/warehouseops.git
   cd warehouseops
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Update database credentials in `.env`**
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=your-database-host
   DB_PORT=5432
   DB_DATABASE=waybill_system
   DB_USERNAME=your-username
   DB_PASSWORD=your-password
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Start development server**
   ```bash
   php artisan serve
   ```

## 📐 Database Schema

### Key Tables

- **waybills** - Core waybill data with status tracking
- **uploads** - Excel file upload records
- **scanned_waybills** - Individual scan records
- **batch_scan_sessions** - Batch scanning sessions
- **batch_scan_items** - Items within batch sessions

### Status Workflow

```
PENDING → DISPATCHED → IN TRANSIT → DELIVERING → DELIVERED
                                             ↓
                                         RETURNED
```

## 🎨 Features Detail

### Dashboard

- Real-time status cards for all waybill states
- Period-based delivery and return rate analytics
- Recent dispatch scans with product information
- Date range filtering

### Scanner Section

- **Upload Tab**: Excel file upload for batch processing
- **Scan Tab**: Real-time barcode scanning
- **Ready Tab**: View waybills ready for dispatch
- **History Tab**: Access past batch sessions and manifests

### Accounts Section

- Complete waybill listing with pagination
- Advanced filtering by status, date, and search terms
- Product information display (formerly sender)
- Date fallback to created_at when signing_time is unavailable

## 🔧 Configuration

### Excel Import Format

The system accepts Excel files with the following columns:

- Waybill Number
- Sender Name/Product
- Sender Address
- Sender Phone
- Receiver Name
- Receiver Address
- Receiver Phone
- Destination
- Number of Items
- Weight
- Express Type/Service Type
- COD Amount
- Remarks
- Order Status
- Signing Time

## 🚢 Deployment

### Production Deployment

Use the automated deployment script:

```bash
cd deployment
bash scripts/deploy-app.sh
```

This will:
- Sync code to both app servers
- Install dependencies (production mode)
- Run migrations
- Clear and cache configurations
- Restart PHP-FPM services

### Manual Deployment Steps

```bash
# On each app server
cd /var/www/waybill
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl restart php8.2-fpm nginx
```

## 📊 Status Color Coding

- **Pending** - Gray/Default
- **Dispatched** - Blue
- **In Transit** - Info Blue
- **Delivering** - Warning Yellow
- **Delivered** - Success Green
- **Returned** - Red
- **HQ Scheduling** - Info Blue

## 🔐 Security

- Environment variables for sensitive configuration
- CSRF protection on all forms
- SQL injection prevention via Eloquent ORM
- Input validation and sanitization
- Secure file upload handling

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License.

## 👥 Team

**Project Thirdynal**
- Warehouse Operations System
- Version 4.0

## 📞 Support

For issues and questions, please open an issue on GitHub.

---

**Built with ❤️ using Laravel**
