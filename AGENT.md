# AGENT.md - PR25 One Project

## Build/Test Commands
- **Start Development**: `python main.py` (runs Flask dev server on port 5000)
- **Production**: `gunicorn --bind 0.0.0.0:5000 main:app` 
- **Replit**: Uses `.replit` config with `gunicorn --bind 0.0.0.0:5000 --reuse-port --reload main:app`
- **Dependencies**: `uv` package manager (pyproject.toml)

## Architecture & Structure
- **Type**: Flask web application for hunting submission tracking (German: Abschussplan HGMH)
- **Database**: SQLite (`form_submissions.db`) with tables: `custom_form_submissions`, `settings`, `category_limits`
- **Templates**: Jinja2 templates in `/templates/` (index.html, admin.html, submissions.html)
- **Routes**: Form submission, admin panel, database config, submissions viewing with pagination, **CSV export**
- **Categories**: 8 hunting categories (Wildkalb, Schmaltier, etc.) with configurable limits

## Database Schema
- `custom_form_submissions`: id, field1 (date), field2 (category), field3 (WUS int 1000000-9999999), field4 (remarks), created_at
- `settings`: key-value pairs for DB config, export_filename
- `category_limits`: category-specific max_count limits

## CSV Export Features
- **Export Route**: `/export_csv` - Public access, no authentication required
- **Parameters**: 
  - `category` (optional): Filter by hunting category (e.g., "Rotwild")
  - `from` (optional): Start date filter (YYYY-MM-DD format)
  - `to` (optional): End date filter (YYYY-MM-DD format)
- **Output**: 9 columns - ID, Wildart, Abschussdatum, Abschuss, WUS, Bemerkung, Erstellt von, Erstellt am
- **Filename**: Configurable via admin settings (`export_filename` in settings table)
- **Format**: Standard CSV (comma-separated, UTF-8 encoded)
- **UI Integration**: Export button in submissions table, admin URL examples with copy-to-clipboard

## WUS Field Constraints
- **Range**: 1000000-9999999 (7-digit numbers starting with 1-9)
- **Validation**: HTML input min/max, JavaScript real-time validation, server-side validation
- **Error Messages**: German text for constraint violations

## Code Style & Conventions
- **Language**: Python 3.11+, German text/comments for domain-specific terms
- **Framework**: Flask with SQLAlchemy, jQuery frontend, Bootstrap dark theme + Bootstrap Icons
- **Error Handling**: JSON responses for AJAX, exception catching with user-friendly messages
- **Validation**: Server-side form validation, date validation (no future dates), WUS range checking, limit checking
- **Security**: Environment-based secret keys, prepared SQL statements, input sanitization
