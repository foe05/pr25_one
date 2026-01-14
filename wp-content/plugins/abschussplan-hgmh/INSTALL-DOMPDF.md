# DOMPDF Installation Instructions

## Required Manual Step

After deploying this code, you need to install the DOMPDF library via Composer:

```bash
cd wp-content/plugins/abschussplan-hgmh
composer install --no-dev --optimize-autoloader
```

## What This Does

This command will:
- Download DOMPDF library (version 2.0+) and its dependencies
- Create a `vendor/` directory with all required files
- Generate the Composer autoloader

## Verification

After running composer install, verify that the following file exists:
```
wp-content/plugins/abschussplan-hgmh/vendor/autoload.php
```

The PDF service class will automatically detect and load DOMPDF when available.

## Requirements

- PHP 7.4 or higher
- Composer installed on the server
- Write permissions to the plugin directory

## Troubleshooting

If PDF generation fails, check the WordPress error log for messages like:
- "DOMPDF library not found. Please run: composer install"
- "DOMPDF not initialized"

These indicate that composer install needs to be run.
