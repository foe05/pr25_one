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
- **Routes**: Form submission, admin panel, database config, submissions viewing with pagination
- **Categories**: 8 hunting categories (Wildkalb, Schmaltier, etc.) with configurable limits

## Database Schema
- `custom_form_submissions`: id, field1 (date), field2 (category), field3 (WUS int), field4 (remarks), created_at
- `settings`: key-value pairs for DB config
- `category_limits`: category-specific max_count limits

## Code Style & Conventions
- **Language**: Python 3.11+, German text/comments for domain-specific terms
- **Framework**: Flask with SQLAlchemy, jQuery frontend, Bootstrap dark theme
- **Error Handling**: JSON responses for AJAX, exception catching with user-friendly messages
- **Validation**: Server-side form validation, date validation (no future dates), limit checking
- **Security**: Environment-based secret keys, prepared SQL statements
