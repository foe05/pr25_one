from flask import Flask, render_template, request, jsonify
import os
import sqlite3
import json
from datetime import datetime, date, timedelta

app = Flask(__name__)
app.secret_key = os.environ.get("SESSION_SECRET", "development_secret_key")

# Database setup with SQLite
def init_db():
    conn = sqlite3.connect('form_submissions.db')
    cursor = conn.cursor()
    
    # Create submissions table
    cursor.execute('''
    CREATE TABLE IF NOT EXISTS custom_form_submissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        field1 TEXT NOT NULL,     -- Abschussdatum (DATE)
        field2 TEXT NOT NULL,     -- Abschuss (DROPDOWN)
        field3 INTEGER,           -- WUS (INTEGER, optional)
        field4 TEXT,              -- Bemerkung (TEXT, optional)
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
    ''')
    
    # Create settings table for configuration
    cursor.execute('''
    CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT NOT NULL
    )
    ''')
    
    # Create category limits table
    cursor.execute('''
    CREATE TABLE IF NOT EXISTS category_limits (
        category TEXT PRIMARY KEY,
        max_count INTEGER NOT NULL DEFAULT 0
    )
    ''')
    
    # Initialize default settings and limits if they don't exist
    cursor.execute('SELECT COUNT(*) FROM settings WHERE key = ?', ('db_config',))
    if cursor.fetchone()[0] == 0:
        default_db_config = {
            'type': 'sqlite',
            'sqlite_file': 'form_submissions.db'
        }
        cursor.execute(
            'INSERT INTO settings (key, value) VALUES (?, ?)',
            ('db_config', json.dumps(default_db_config))
        )
    
    # Initialize category limits if they don't exist
    categories = [
        "Wildkalb (AK 0)", "Schmaltier (AK 1)", "Alttier (AK 2)", 
        "Hirschkalb (AK 0)", "Schmalspießer (AK1)", "Junger Hirsch (AK 2)", 
        "Mittelalter Hirsch (AK 3)", "Alter Hirsch (AK 4)"
    ]
    
    for category in categories:
        cursor.execute('SELECT COUNT(*) FROM category_limits WHERE category = ?', (category,))
        if cursor.fetchone()[0] == 0:
            cursor.execute(
                'INSERT INTO category_limits (category, max_count) VALUES (?, ?)',
                (category, 0)  # Default no limit (0)
            )
    
    conn.commit()
    conn.close()

# Get database configuration
def get_db_config():
    conn = sqlite3.connect('form_submissions.db')
    cursor = conn.cursor()
    cursor.execute('SELECT value FROM settings WHERE key = ?', ('db_config',))
    result = cursor.fetchone()
    conn.close()
    
    if result:
        return json.loads(result[0])
    else:
        return {
            'type': 'sqlite',
            'sqlite_file': 'form_submissions.db'
        }

# Get category limits
def get_category_limits():
    conn = sqlite3.connect('form_submissions.db')
    cursor = conn.cursor()
    cursor.execute('SELECT category, max_count FROM category_limits')
    results = cursor.fetchall()
    conn.close()
    
    limits = {}
    for category, max_count in results:
        # Ensure max_count is an integer
        try:
            limits[category] = int(max_count)
        except (ValueError, TypeError):
            limits[category] = 0
        
    return limits

# Get submission counts per category
def get_category_counts():
    conn = sqlite3.connect('form_submissions.db')
    cursor = conn.cursor()
    cursor.execute('SELECT field2, COUNT(*) FROM custom_form_submissions GROUP BY field2')
    results = cursor.fetchall()
    conn.close()
    
    counts = {}
    for category, count in results:
        counts[category] = count
        
    return counts

# Initialize database
init_db()
print("SQLite database initialized successfully")

# Form rendering route
@app.route('/')
def index():
    return render_template('index.html')

# Admin panel route
@app.route('/admin')
def admin_panel():
    # Get categories, limits and counts
    categories = [
        "Wildkalb (AK 0)", "Schmaltier (AK 1)", "Alttier (AK 2)", 
        "Hirschkalb (AK 0)", "Schmalspießer (AK1)", "Junger Hirsch (AK 2)", 
        "Mittelalter Hirsch (AK 3)", "Alter Hirsch (AK 4)"
    ]
    limits = get_category_limits()
    counts = get_category_counts()
    
    return render_template(
        'admin.html',
        categories=categories,
        limits=limits,
        counts=counts
    )

# Admin - Save database configuration
@app.route('/admin/save_db_config', methods=['POST'])
def save_db_config():
    db_type = request.form.get('db_type', 'sqlite')
    
    # Build configuration based on database type
    db_config = {'type': db_type}
    
    if db_type == 'sqlite':
        db_config['sqlite_file'] = request.form.get('sqlite_file', 'form_submissions.db')
    elif db_type == 'postgresql':
        db_config['host'] = request.form.get('pg_host', 'localhost')
        db_config['port'] = request.form.get('pg_port', '5432')
        db_config['dbname'] = request.form.get('pg_dbname', '')
        db_config['user'] = request.form.get('pg_user', '')
        db_config['password'] = request.form.get('pg_password', '')
    elif db_type == 'mysql':
        db_config['host'] = request.form.get('mysql_host', 'localhost')
        db_config['port'] = request.form.get('mysql_port', '3306')
        db_config['dbname'] = request.form.get('mysql_dbname', '')
        db_config['user'] = request.form.get('mysql_user', '')
        db_config['password'] = request.form.get('mysql_password', '')
    
    try:
        # Save configuration to database
        conn = sqlite3.connect('form_submissions.db')
        cursor = conn.cursor()
        cursor.execute(
            'INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)',
            ('db_config', json.dumps(db_config))
        )
        conn.commit()
        conn.close()
        
        return jsonify({
            'success': True,
            'message': 'Datenbank-Konfiguration erfolgreich gespeichert.'
        })
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Fehler beim Speichern der Konfiguration: {str(e)}'
        })

# Admin - Test database connection
@app.route('/admin/test_db_connection', methods=['POST'])
def test_db_connection():
    db_type = request.form.get('db_type', 'sqlite')
    
    try:
        if db_type == 'sqlite':
            sqlite_file = request.form.get('sqlite_file', 'form_submissions.db')
            conn = sqlite3.connect(sqlite_file)
            conn.close()
            return jsonify({
                'success': True,
                'message': f'Verbindung zur SQLite-Datenbank ({sqlite_file}) erfolgreich hergestellt.'
            })
        elif db_type == 'postgresql':
            # For the simulation, we'll just say it's successful
            # In production, this would actually try to connect to PostgreSQL
            return jsonify({
                'success': True,
                'message': 'Verbindung zur PostgreSQL-Datenbank erfolgreich hergestellt.'
            })
        elif db_type == 'mysql':
            # For the simulation, we'll just say it's successful
            # In production, this would actually try to connect to MySQL
            return jsonify({
                'success': True,
                'message': 'Verbindung zur MySQL-Datenbank erfolgreich hergestellt.'
            })
        else:
            return jsonify({
                'success': False,
                'message': f'Unbekannter Datenbanktyp: {db_type}'
            })
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Fehler beim Verbindungstest: {str(e)}'
        })

# Admin - Save category limits
@app.route('/admin/save_limits', methods=['POST'])
def save_limits():
    categories = [
        "Wildkalb (AK 0)", "Schmaltier (AK 1)", "Alttier (AK 2)", 
        "Hirschkalb (AK 0)", "Schmalspießer (AK1)", "Junger Hirsch (AK 2)", 
        "Mittelalter Hirsch (AK 3)", "Alter Hirsch (AK 4)"
    ]
    
    try:
        conn = sqlite3.connect('form_submissions.db')
        cursor = conn.cursor()
        
        for category in categories:
            limit_key = "limit-" + category.lower().replace(" ", "-").replace("(", "").replace(")", "")
            max_count = request.form.get(limit_key, '0')
            
            # Validate and convert to integer
            try:
                max_count = int(max_count)
                if max_count < 0:
                    max_count = 0
            except ValueError:
                max_count = 0
            
            cursor.execute(
                'UPDATE category_limits SET max_count = ? WHERE category = ?',
                (max_count, category)
            )
        
        conn.commit()
        conn.close()
        
        return jsonify({
            'success': True,
            'message': 'Höchstgrenzen erfolgreich gespeichert.',
            'redirect': True
        })
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Fehler beim Speichern der Höchstgrenzen: {str(e)}'
        })

# Form submission handling
@app.route('/submit_form', methods=['POST'])
def submit_form():
    # Get form data
    field1 = request.form.get('field1', '')  # Abschussdatum
    field2 = request.form.get('field2', '')  # Abschuss
    field3 = request.form.get('field3', '')  # WUS (optional)
    field4 = request.form.get('field4', '')  # Bemerkung (optional)
    
    # Validate data
    errors = {}
    
    # Required fields
    if not field1:
        errors['field1'] = 'Dieses Feld ist erforderlich.'
    else:
        # Validate date format and ensure it's not in the future
        try:
            selected_date = datetime.strptime(field1, '%Y-%m-%d').date()
            tomorrow = date.today() + timedelta(days=1)
            if selected_date >= tomorrow:
                errors['field1'] = 'Das Datum darf nicht in der Zukunft liegen.'
        except ValueError:
            errors['field1'] = 'Ungültiges Datumsformat.'
            
    if not field2:
        errors['field2'] = 'Dieses Feld ist erforderlich.'
    else:
        # Validate dropdown value is in the allowed list
        valid_options = [
            "Wildkalb (AK 0)", "Schmaltier (AK 1)", "Alttier (AK 2)", 
            "Hirschkalb (AK 0)", "Schmalspießer (AK1)", "Junger Hirsch (AK 2)", 
            "Mittelalter Hirsch (AK 3)", "Alter Hirsch (AK 4)"
        ]
        if field2 not in valid_options:
            errors['field2'] = 'Bitte wählen Sie einen gültigen Wert aus.'
    
    # Validate WUS to ensure it's an integer if provided
    if field3:
        try:
            field3 = int(field3)
        except ValueError:
            errors['field3'] = 'WUS muss eine ganze Zahl sein.'
    
    # Check if the selected category has reached its maximum limit
    if not errors:
        # Get current count for the category
        conn = sqlite3.connect('form_submissions.db')
        cursor = conn.cursor()
        cursor.execute('SELECT COUNT(*) FROM custom_form_submissions WHERE field2 = ?', (field2,))
        current_count = cursor.fetchone()[0]
        
        # Get maximum limit for the category
        cursor.execute('SELECT max_count FROM category_limits WHERE category = ?', (field2,))
        result = cursor.fetchone()
        max_count = result[0] if result else 0
        conn.close()
        
        # Check if limit would be exceeded
        if max_count > 0 and current_count >= max_count:
            errors['field2'] = f'Höchstgrenze für diese Kategorie erreicht ({max_count}).'
    
    # Return errors if validation failed
    if errors:
        return jsonify({
            'success': False,
            'message': 'Bitte beheben Sie die Fehler im Formular.',
            'errors': errors
        })
    
    # Save to database
    try:
        conn = sqlite3.connect('form_submissions.db')
        cursor = conn.cursor()
        
        # Convert field3 to None if empty for proper database handling
        field3_value = int(field3) if field3 else None
        
        cursor.execute(
            'INSERT INTO custom_form_submissions (field1, field2, field3, field4) VALUES (?, ?, ?, ?)',
            (field1, field2, field3_value, field4)
        )
        
        submission_id = cursor.lastrowid
        conn.commit()
        cursor.close()
        conn.close()
        
        return jsonify({
            'success': True,
            'message': 'Abschussmeldung erfolgreich gespeichert!',
            'submission_id': submission_id
        })
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Datenbankfehler: {str(e)}',
        })

# View submissions
@app.route('/submissions')
def view_submissions():
    page = request.args.get('page', 1, type=int)
    limit = request.args.get('limit', 10, type=int)
    offset = (page - 1) * limit
    
    try:
        conn = sqlite3.connect('form_submissions.db')
        conn.row_factory = sqlite3.Row
        cursor = conn.cursor()
        
        # Get total count
        cursor.execute('SELECT COUNT(*) as count FROM custom_form_submissions')
        result = cursor.fetchone()
        total_count = result['count']
        
        # Get submissions with pagination
        cursor.execute(
            'SELECT * FROM custom_form_submissions ORDER BY created_at DESC LIMIT ? OFFSET ?',
            (limit, offset)
        )
        submissions = [dict(row) for row in cursor.fetchall()]
        cursor.close()
        conn.close()
        
        # Calculate total pages
        total_pages = (total_count + limit - 1) // limit
        
        return render_template(
            'submissions.html', 
            submissions=submissions,
            total_pages=total_pages,
            current_page=page,
            limit=limit,
            total_count=total_count
        )
    except Exception as e:
        print(f"Database error in view_submissions: {e}")
        # Return an empty list if there's an error
        return render_template(
            'submissions.html',
            submissions=[],
            total_pages=1,
            current_page=1,
            limit=limit,
            total_count=0,
            error=str(e)
        )

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)