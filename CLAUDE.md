# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Language Policy

**All responses must be in Japanese.** Code, identifiers, and official terms may remain in English.

## Project Overview

This is a PHP 7.4-based Next Engine integration tool for managing e-commerce operations. The system handles order processing, goods management, CSV exports for accounting (Clear), and automated routine tasks.

**Production Environment:**
- FreeBSD 13.0-RELEASE-p14 amd64
- Apache 2.4.62
- PHP 7.4.33 (module mode)
- Perl 5.14.4

## Development Methodology

This project follows **Contract Driven Development**:
- **RFCs** (`rfcs/`): Development proposals tracking requirements → implementation → completion
  - `planned/`: Planning stage
  - `in_progress/`: Currently implementing
  - `completed/`: Finished work
- **Contracts** (`specs/`): System specifications for classes, databases, and APIs

Always review existing RFCs and contracts before making changes. See `rfcs/README.md` and `specs/README.md` for detailed guidelines.

## Architecture

### Directory Structure

```
├── tool/                   # Production environment
│   ├── include/           # Core PHP classes and utilities
│   │   ├── Tonary*.php   # Base utilities (session, input, logging, date)
│   │   ├── NE/           # Next Engine API models (orders, goods, backups)
│   │   ├── NE*.php       # Next Engine integration logic
│   │   ├── Customers.php # Customer master data
│   │   └── Shops.php     # Shop master data
│   ├── download/         # CSV generation tools
│   │   ├── csv_for_clear.php      # Main: Sales data CSV for Clear accounting
│   │   ├── customers_edit.php     # Customer master editor
│   │   └── tmp_download.php       # Secure file download handler
│   ├── ne_routine_exec.php        # Automated order processing
│   ├── ne_goods_list.php          # Goods management UI
│   ├── ne_receive_order_list.php  # Order list UI
│   └── env/env.php               # Config loader (loads from outside web root)
│
├── tool_test/             # Test environment (mirrors tool/ structure)
├── datas/                 # Data files
│   ├── ozie_customers.tsv       # Production customer master
│   └── ozie_customers_test.tsv  # Test customer master
├── config.example/        # Configuration templates
│   ├── Config.php        # Production config sample
│   └── Config_test.php   # Test/sandbox config sample
├── rfcs/                  # Development proposals
└── specs/                 # System contracts
```

### Core Classes

**Base Utilities** (`Tonary.php`):
- `Tonary::session_start()` - Session initialization
- `Tonary::get_post()`, `Tonary::get_get()` - Sanitized input retrieval
- `Tonary::write_accesslog()` - Access logging
- `Tonary::str_to_ymd()` - Date normalization

**Next Engine Integration** (`Tonary_NE.php`):
- `Tonary_NE::login()` - API authentication, returns user info and sets `$this->token`
- `Tonary_NE::api_search()` - Generic API search wrapper
- Token management via session

**File Operations**:
- `Tonary_FileMaker` - CSV generation with SJIS encoding
- `Tonary_FileReader` - CSV/TSV reading utilities

**Database** (`Tonary_MySQL.php`):
- Basic MySQL wrapper for storing/retrieving Next Engine types

**Data Models** (in `tool/include/NE/`):
- `NEReceiveOrder` - Order header fields
- `NEReceiveOrderRow` - Order line item fields
- `NEBackupGoods` - Goods master fields
- `NEBackupPage` - Page/category fields

### Configuration

**Location**: Production config must be stored **outside the web root** (e.g., `/home/tonary/include/ozie/Config.php`). Never commit actual config files.

**Key Settings**:
```php
Config::$global_include_path   // Global includes path
Config::$local_include_path    // Local includes path
Config::$tmpdir_path          // Temp files (must be writable by web server)
Config::$session_path         // Session storage path
Config::$customer_path        // TSV customer master file path
Config::$ne_server_url        // Next Engine API base URL
Config::$ne_client_id         // API credentials
Config::$ne_client_secret     // API credentials
Config::$db_host, $db_user... // Database connection
Config::$keep_days            // Backup retention period
```

The config is loaded via `tool/env/env.php` which requires the actual config from outside the repository.

### Customer Master Data

Customer data can be managed in two ways:
1. **Static PHP array**: `tool/include/Customers.php` (legacy)
2. **TSV file**: Location specified in `Config::$customer_path` (preferred)

TSV format (tab-separated, 8 columns):
```
{id}	{shop_id}	{name}	{shop_name}	{tax_type}	{tax_method}	{jan}	{goods_name}
```

When both exist, TSV file takes precedence. Used by `csv_for_clear.php` for customer-specific processing.

## Common Development Commands

### Environment Setup

```bash
# Copy and configure settings (do this outside the repo)
cp config.example/Config.php /path/outside/webroot/Config.php
# Edit /path/outside/webroot/Config.php with actual credentials

# Ensure directories exist and are writable
mkdir -p {tmpdir_path}/clear_csv
mkdir -p {tmpdir_path}/session
chmod 777 {tmpdir_path}/clear_csv {tmpdir_path}/session
```

### Testing

There is a parallel test environment:
- Production: `tool/`
- Test: `tool_test/` (uses `Config_test.php` pointing to sandbox API)

Test by accessing files in `tool_test/` instead of `tool/`.

### Running Key Tools

**CSV Export for Clear Accounting**:
- Access: `tool/download/csv_for_clear.php`
- Flow: Authenticate → Select date range & customers → Generate CSV → Download
- Output: `{tmpdir_path}/clear_csv/clear_{timestamp}.csv` (SJIS)
- See: `specs/clear_csv/v1/contract.md` for detailed spec

**Routine Order Processing**:
- Access: `tool/ne_routine_exec.php`
- Automatically processes orders based on custom logic
- Executes batch operations (bundles, gifts, light baggage handling)

**Customer Master Editor**:
- Access: `tool/download/customers_edit.php`
- Edit TSV customer master via web interface

## Development Patterns

### Adding a New CSV Export Feature

1. Create RFC in `rfcs/planned/{category}_{summary}.md`
2. Define contract in `specs/{feature}/v1/contract.md` with:
   - Input parameters and validation
   - Next Engine API calls required
   - Output CSV format specification
   - Processing logic and edge cases
3. Implement in `tool/download/{feature}.php`:
   ```php
   require_once('../env/env.php');
   require_once(Config::$global_include_path.'Tonary.php');
   require_once(Config::$global_include_path.'Tonary_NE.php');

   Tonary::session_start();
   Tonary::write_accesslog();
   $nextengine = new Tonary_NE();
   $login_user = $nextengine->login();
   $token = $nextengine->token;
   ```
4. Use `Tonary_FileMaker` for CSV generation (auto-handles SJIS)
5. Move RFC to `rfcs/in_progress/` and update with implementation details
6. Test in `tool_test/` environment first
7. Move RFC to `rfcs/completed/` when done

### Adding a New Next Engine Integration

1. Check if model exists in `tool/include/NE/`
2. If new entity needed, create class defining `$fields` array
3. Use `Tonary_NE::api_search()` for API calls:
   ```php
   $result = $nextengine->api_search(
       '/api_v1_master_goods/search',
       $params,
       NEBackupGoods::$query_fields
   );
   ```
4. Document in contract before implementing

### Working with Customer-Specific Logic

Many tools have customer-specific processing (tax handling, special product codes, shipping methods). This logic is:
- Based on matching `receive_order_shop_id` with `Customers::$datas`
- Documented in the relevant contract
- Often includes special cases (e.g., `nekutai` product code price override in `csv_for_clear.php:26`)

Always check existing contracts for customer-specific requirements before modifying.

## Key Implementation Details

### Next Engine API Authentication

All tools follow this pattern:
```php
$nextengine = new Tonary_NE();
$login_user = $nextengine->login();  // Sets token in session
$token = $nextengine->token;         // For CSRF protection
```

Session must be initialized with `Tonary::session_start()` first.

### CSRF Protection

Download handlers use session tokens:
```php
// In main script:
$token = $nextengine->token;
// Pass to download form

// In tmp_download.php:
if ($token !== Tonary::get_post('token')) {
    die('Invalid token');
}
```

### Date Handling

Use `Tonary::str_to_ymd()` to normalize date strings to `Y-m-d` format. Next Engine API accepts dates in this format.

### Error Handling

- Display errors to authenticated users (already logged in to Next Engine)
- Use try-catch blocks for API calls
- Log access with `Tonary::write_accesslog()`

## Important Notes

- **Never commit Config.php** - It contains API credentials and must live outside the repository
- **Character encoding**: All PHP files use UTF-8, CSV exports typically use SJIS for compatibility with Japanese accounting software
- **File permissions**: Ensure `{tmpdir_path}` subdirectories are writable by web server
- **API rate limits**: Next Engine API has rate limits; use appropriate delays for batch operations
- **Parallel environments**: Changes to `tool/` should be mirrored to `tool_test/` when applicable
