# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **Laravel 12** warehouse operations system for managing waybills. The application handles waybill uploads (via Excel/CSV), batch scanning operations, and provides a dark-themed dashboard for tracking waybill statuses and performance metrics.

**Key Features:**
- Waybill import from Excel/CSV files using `maatwebsite/excel`
- Batch scanning workflow with session management
- Real-time dashboard with statistics
- Pending waybill management and dispatch
- Dark-themed UI following the design system in `thirdynal-ui-design-system.md`

## Development Commands

### Initial Setup
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Or use the composer setup script:
```bash
composer setup
```

### Development Server
```bash
# Run all services (server, queue, logs, vite) concurrently
composer dev
```

This starts:
- Laravel server (`php artisan serve`)
- Queue worker (`php artisan queue:listen`)
- Log viewer (`php artisan pail`)
- Vite dev server (`npm run dev`)

Alternatively, run services individually:
```bash
php artisan serve          # Dev server at http://localhost:8000
php artisan queue:listen   # Queue worker
npm run dev                # Vite dev server
```

### Testing
```bash
# Run all tests
composer test
# Or directly:
php artisan test

# Run specific test file
php artisan test tests/Feature/WaybillTest.php

# Run specific test method
php artisan test --filter test_method_name

# Run with coverage (requires Xdebug)
php artisan test --coverage
```

### Code Quality
```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Format specific files
./vendor/bin/pint app/Models
```

### Database
```bash
# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh database with seeding
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_table_name
```

### Build for Production
```bash
npm run build              # Build frontend assets
php artisan config:cache   # Cache config
php artisan route:cache    # Cache routes
php artisan view:cache     # Cache views
```

## Architecture

### Core Domain: Waybill Management

The application is built around a **waybill processing workflow**:

1. **Upload Phase** - Waybills are imported from Excel/CSV files
2. **Batch Ready State** - Waybills can be marked as ready for scanning
3. **Batch Scanning** - Operators scan waybills in sessions to confirm pickup/dispatch
4. **Pending Management** - Waybills can be marked as pending for various reasons
5. **Status Tracking** - Waybills have lifecycle states: pending → in_transit → delivered/returned

### Database Schema

**Main Tables:**
- `uploads` - Tracks file uploads with processing status
- `waybills` - Central entity; stores all waybill data
  - Links to `uploads` via `upload_id`
  - Has `status` (pending, in_transit, delivered, returned)
  - Has `batch_ready` flag for scanning workflow
  - Has `marked_pending_at` timestamp for pending waybills
  - Has `signing_time` for delivery timestamp
- `batch_sessions` - Scanning session records
- `batch_scan_items` - Individual scans within a session (valid/duplicate/error)
- `scanned_waybills` - Historical record of all scans
- `notifications` - User notifications

**Key Relationships:**
- Upload hasMany Waybills
- BatchSession hasMany BatchScanItems
- BatchSession hasMany ScannedWaybills

### Controllers

**Web Routes** (`routes/web.php`):
- `DashboardController` - Main dashboard with stats
- `ScannerController` - Scanner interface
- `UploadController` - Single and batch file uploads
- `WaybillController` - Waybill listing/management
- `PendingController` - Pending waybill management

**API Routes** (`routes/api.php`):
- `BatchScanController` - RESTful API for batch scanning operations
  - `POST /batch-scan/start` - Start new session
  - `POST /batch-scan/scan` - Scan a waybill
  - `GET /batch-scan/status` - Get session status
  - `POST /batch-scan/dispatch` - Complete session
  - `POST /batch-scan/mark-pending` - Mark waybill as pending
  - `GET /batch-scan/issues` - Get pending issues

### Import/Export System

**WaybillsImport** (`app/Imports/WaybillsImport.php`):
- Uses `maatwebsite/excel` package
- Implements chunked reading (1000 rows) for memory efficiency
- Implements batch inserts (1000 rows) for performance
- Uses upserts with `waybill_number` as unique key
- Maps Excel columns to database fields (handles variations like `signing_time`/`signingtime`)
- Parses Excel date formats and text dates using Carbon

**Expected Excel Columns:**
```
waybill_number, sender_name, sender_address, sender_cellphone,
receiver, receiver_cellphone, barangay, city, province,
item_weight, number_of_items, express_type, cod, remarks,
order_status, signing_time
```

### Frontend Architecture

**Technology Stack:**
- Laravel Blade templates for server-side rendering
- Vite for asset bundling
- Tailwind CSS 4.0 for styling
- Vanilla JavaScript (no framework)

**View Structure:**
- `resources/views/layouts/app.blade.php` - Main layout
- `resources/views/components/` - Reusable Blade components
- `resources/views/*.blade.php` - Page templates

**UI Design System:**
- Comprehensive design tokens in `thirdynal-ui-design-system.md`
- Dark theme with cyan/blue accents (#22d3ee, #3b82f6)
- 8px grid system
- Specific component styles for cards, tables, badges, buttons
- **IMPORTANT:** Follow the design system strictly for consistency

**Key UI Patterns:**
- Stat cards with large numbers and percentages
- Data tables with status badges
- Date range filters
- Batch scanning interface with real-time feedback
- Navigation tabs

## Important Conventions

### Waybill Status Values
Use lowercase for consistency:
- `pending` - Initial state
- `in_transit` - Being shipped
- `delivered` - Successfully delivered
- `returned` - Failed delivery

### Batch Scanning Workflow
1. Start a session: Creates `BatchSession` record
2. Scan waybills: Creates `BatchScanItem` with type (valid/duplicate/error)
3. Waybill validation:
   - Must exist in database
   - Must have `batch_ready = true`
   - Cannot be duplicate in current session
   - Cannot already be scanned (status check)
4. Dispatch: Updates waybill status to `in_transit`, creates `ScannedWaybill` records

### Date Handling
- Use Carbon for all date operations
- Excel dates are numeric serial values - use `PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject()`
- Database stores timestamps as `timestamp` type
- Frontend displays dates in human-readable format

### Model Conventions
- Use `$fillable` for mass assignment protection
- Define relationships explicitly
- Waybill number is unique across the system
- Upload ID is required foreign key for waybills

## Common Pitfalls

1. **Excel Import Memory**: Large files must use chunking and batch inserts (already configured at 1000 rows)
2. **Duplicate Scans**: Check both `BatchScanItem` for session duplicates AND waybill status for already-scanned items
3. **Date Parsing**: Excel dates can be numeric OR string format - handle both in `parseDate()` method
4. **Batch Ready Flag**: Waybills must be explicitly marked `batch_ready = true` before scanning
5. **Session Management**: Always close previous active sessions before starting new one for same user
6. **Queue Processing**: The `composer dev` command runs queue worker - ensure jobs are queued if used

## File Locations

- **Models**: `app/Models/`
- **Controllers**: `app/Http/Controllers/`
- **Routes**: `routes/web.php`, `routes/api.php`
- **Views**: `resources/views/`
- **Migrations**: `database/migrations/`
- **Imports**: `app/Imports/`
- **Frontend Assets**: `resources/css/`, `resources/js/`
- **Public Assets**: `public/` (compiled by Vite)

## Environment Configuration

The application uses SQLite by default for development (`database/database.sqlite`). Key `.env` settings:
- `DB_CONNECTION=sqlite`
- `DB_DATABASE=/absolute/path/to/database/database.sqlite`
- Queue, cache, and session drivers are configurable

## Testing Configuration

Tests use in-memory SQLite (`:memory:`) configured in `phpunit.xml`. This provides fast, isolated test runs without affecting the development database.
