# Abschussplan HGMH - REST API Dokumentation

Version: 1.0.0
Base URL: `https://ihre-website.de/wp-json/ahgmh/v1`

## Inhaltsverzeichnis

- [Authentifizierung](#authentifizierung)
- [Public Endpoints](#public-endpoints)
- [Authenticated Endpoints](#authenticated-endpoints)
- [Fehlerbehandlung](#fehlerbehandlung)

---

## Authentifizierung

Die API nutzt **JWT (JSON Web Token)** für die Authentifizierung.

### JWT Plugin Installation

1. Installiere das Plugin "JWT Authentication for WP REST API"
   ```bash
   wp plugin install jwt-authentication-for-wp-rest-api --activate
   ```

2. Füge zu `wp-config.php` hinzu:
   ```php
   define('JWT_AUTH_SECRET_KEY', 'your-top-secret-key');
   define('JWT_AUTH_CORS_ENABLE', true);
   ```

3. Füge zur `.htaccess` hinzu:
   ```apache
   RewriteEngine on
   RewriteCond %{HTTP:Authorization} ^(.*)
   RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]
   ```

### Login-Flow

**1. Token anfordern:**

```http
POST /wp-json/jwt-auth/v1/token
Content-Type: application/json

{
  "username": "obmann.mueller",
  "password": "sicheres-passwort"
}
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user_email": "mueller@example.com",
  "user_nicename": "obmann.mueller",
  "user_display_name": "Max Mueller"
}
```

**2. Token verwenden:**

Füge den Token in den `Authorization` Header ein:

```http
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**3. Token validieren:**

```http
POST /wp-json/jwt-auth/v1/token/validate
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

---

## Public Endpoints

Diese Endpoints benötigen keine Authentifizierung.

### GET /info

Plugin-Informationen abrufen.

**Request:**
```http
GET /wp-json/ahgmh/v1/info
```

**Response:**
```json
{
  "success": true,
  "data": {
    "plugin_name": "Abschussplan HGMH",
    "plugin_version": "2.5.2",
    "api_version": "1.0.0",
    "app_compatible": true,
    "site_name": "Hegegemeinschaft Musterwald",
    "site_url": "https://hg-musterwald.de",
    "features": {
      "submissions": true,
      "export": true,
      "notifications": false
    }
  }
}
```

---

### GET /species

Liste aller verfügbaren Wildarten.

**Request:**
```http
GET /wp-json/ahgmh/v1/species
```

**Response:**
```json
{
  "success": true,
  "data": [
    "Rotwild",
    "Damwild",
    "Rehwild"
  ]
}
```

---

### GET /summary

Zusammenfassung der Abschusszahlen.

**Request:**
```http
GET /wp-json/ahgmh/v1/summary?species=Rotwild&meldegruppe=GruppeA
```

**Parameter:**
- `species` (optional): Filter nach Wildart
- `meldegruppe` (optional): Filter nach Meldegruppe

**Response:**
```json
{
  "success": true,
  "data": {
    "species": "Rotwild",
    "meldegruppe": "GruppeA",
    "categories": [
      "Wildkalb (AK0)",
      "Schmaltier (AK 1)",
      "Alttier (AK 2)"
    ],
    "limits": {
      "Wildkalb (AK0)": 10,
      "Schmaltier (AK 1)": 5,
      "Alttier (AK 2)": 3
    },
    "counts": {
      "Wildkalb (AK0)": 7,
      "Schmaltier (AK 1)": 4,
      "Alttier (AK 2)": 3
    },
    "allow_exceeding": {
      "Wildkalb (AK0)": false,
      "Schmaltier (AK 1)": false,
      "Alttier (AK 2)": true
    }
  }
}
```

---

### GET /submissions

Liste der Abschussmeldungen.

**Request:**
```http
GET /wp-json/ahgmh/v1/submissions?species=Rotwild&page=1&per_page=20
```

**Parameter:**
- `species` (optional): Filter nach Wildart
- `meldegruppe` (optional): Filter nach Meldegruppe
- `page` (optional, default: 1): Seitennummer
- `per_page` (optional, default: 20, max: 100): Einträge pro Seite

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "species": "Rotwild",
      "date": "2024-10-26",
      "category": "Wildkalb (AK0)",
      "wus": "1234567",
      "jagdbezirk": "Jagdbezirk 1",
      "bemerkung": "Im Nordrevier",
      "created_at": "2024-10-27 10:30:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total_items": 45,
    "total_pages": 3
  }
}
```

**Hinweis:** Das Feld `interne_notiz` wird NICHT in der öffentlichen API zurückgegeben.

---

### GET /categories

Kategorien und Limits für eine Wildart.

**Request:**
```http
GET /wp-json/ahgmh/v1/categories?species=Rotwild
```

**Parameter:**
- `species` (erforderlich): Wildart

**Response:**
```json
{
  "success": true,
  "data": {
    "species": "Rotwild",
    "categories": [
      {
        "name": "Wildkalb (AK0)",
        "limit": 10,
        "current_count": 7,
        "allow_exceeding": false,
        "is_at_limit": false
      },
      {
        "name": "Schmaltier (AK 1)",
        "limit": 5,
        "current_count": 5,
        "allow_exceeding": false,
        "is_at_limit": true
      }
    ]
  }
}
```

---

### GET /jagdbezirke

Liste aller Jagdbezirke gruppiert nach Meldegruppe.

**Request:**
```http
GET /wp-json/ahgmh/v1/jagdbezirke
```

**Response:**
```json
{
  "success": true,
  "data": {
    "Gruppe A": [
      {
        "jagdbezirk": "Jagdbezirk 1",
        "wildart": null,
        "bemerkung": "Nordrevier"
      },
      {
        "jagdbezirk": "Jagdbezirk 2",
        "wildart": null,
        "bemerkung": ""
      }
    ],
    "Gruppe B": [
      {
        "jagdbezirk": "Jagdbezirk 3",
        "wildart": "Rotwild",
        "bemerkung": "Südrevier"
      }
    ]
  }
}
```

---

## Authenticated Endpoints

Diese Endpoints benötigen einen gültigen JWT Token im `Authorization` Header.

### POST /submissions

Neue Abschussmeldung erstellen.

**Request:**
```http
POST /wp-json/ahgmh/v1/submissions
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: application/json

{
  "species": "Rotwild",
  "date": "2024-10-26",
  "category": "Wildkalb (AK0)",
  "wus": "1234567",
  "jagdbezirk": "Jagdbezirk 1",
  "bemerkung": "Im Nordrevier",
  "interne_notiz": "Nur für Vorstände sichtbar"
}
```

**Pflichtfelder:**
- `species`: Wildart
- `date`: Abschussdatum (Format: YYYY-MM-DD)
- `category`: Kategorie
- `jagdbezirk`: Jagdbezirk

**Optionale Felder:**
- `wus`: Wildursprungsschein-Nummer (7 Ziffern)
- `bemerkung`: Öffentliche Bemerkung
- `interne_notiz`: Interne Notiz (nur für Ersteller sichtbar)

**Response (Erfolg):**
```json
{
  "success": true,
  "message": "Abschussmeldung erfolgreich gespeichert",
  "data": {
    "submission_id": 456
  }
}
```

**Response (Fehler):**
```json
{
  "code": "invalid_date",
  "message": "Das Datum darf nicht in der Zukunft liegen",
  "data": {
    "status": 400
  }
}
```

---

### GET /submissions/my

Eigene Abschussmeldungen abrufen.

**Request:**
```http
GET /wp-json/ahgmh/v1/submissions/my?page=1&per_page=20
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 456,
      "species": "Rotwild",
      "date": "2024-10-26",
      "category": "Wildkalb (AK0)",
      "wus": "1234567",
      "jagdbezirk": "Jagdbezirk 1",
      "bemerkung": "Im Nordrevier",
      "interne_notiz": "Nur für mich sichtbar",
      "created_at": "2024-10-27 10:30:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total_items": 12,
    "total_pages": 1
  }
}
```

---

### GET /user/profile

Benutzerprofil abrufen.

**Request:**
```http
GET /wp-json/ahgmh/v1/user/profile
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 42,
    "username": "obmann.mueller",
    "display_name": "Max Mueller",
    "email": "mueller@example.com",
    "role": "obmann",
    "wildarten": [
      "Rotwild",
      "Damwild"
    ],
    "capabilities": {
      "can_create_submissions": true,
      "can_export": true,
      "can_manage_settings": false
    }
  }
}
```

---

### GET /export

Abschussmeldungen exportieren.

**Request:**
```http
GET /wp-json/ahgmh/v1/export?species=Rotwild&from_date=2024-01-01&to_date=2024-12-31
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Parameter:**
- `species` (optional): Filter nach Wildart
- `from_date` (optional): Von-Datum (Format: YYYY-MM-DD)
- `to_date` (optional): Bis-Datum (Format: YYYY-MM-DD)
- `format` (optional, default: json): Export-Format (json)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "species": "Rotwild",
      "date": "2024-10-26",
      "category": "Wildkalb (AK0)",
      "wus": "1234567",
      "jagdbezirk": "Jagdbezirk 1",
      "meldegruppe": "Gruppe A",
      "bemerkung": "Im Nordrevier",
      "created_by": "Max Mueller",
      "created_at": "2024-10-27 10:30:00"
    }
  ],
  "meta": {
    "total_records": 45,
    "filters": {
      "species": "Rotwild",
      "from_date": "2024-01-01",
      "to_date": "2024-12-31"
    },
    "exported_at": "2024-10-27 15:45:00"
  }
}
```

**Hinweis:** Die CSV-Konvertierung kann Client-seitig durchgeführt werden.

---

## Fehlerbehandlung

### HTTP-Statuscodes

- `200 OK`: Erfolgreiche Anfrage
- `400 Bad Request`: Ungültige Parameter
- `401 Unauthorized`: Nicht authentifiziert
- `403 Forbidden`: Keine Berechtigung
- `404 Not Found`: Ressource nicht gefunden
- `500 Internal Server Error`: Serverfehler

### Fehler-Response-Format

```json
{
  "code": "error_code",
  "message": "Beschreibende Fehlermeldung",
  "data": {
    "status": 400
  }
}
```

### Häufige Fehler

**Nicht authentifiziert:**
```json
{
  "code": "not_authenticated",
  "message": "Sie müssen angemeldet sein",
  "data": {
    "status": 401
  }
}
```

**Ungültiges Datum:**
```json
{
  "code": "invalid_date",
  "message": "Das Datum darf nicht in der Zukunft liegen",
  "data": {
    "status": 400
  }
}
```

**Duplizierte WUS:**
```json
{
  "code": "duplicate_wus",
  "message": "Diese WUS-Nummer ist bereits vergeben",
  "data": {
    "status": 400
  }
}
```

---

## Beispiel-Integration (JavaScript)

```javascript
// Base API Client
class AbschussplanAPI {
  constructor(baseURL) {
    this.baseURL = baseURL;
    this.token = null;
  }

  async login(username, password) {
    const response = await fetch(`${this.baseURL}/jwt-auth/v1/token`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password })
    });
    const data = await response.json();
    this.token = data.token;
    return data;
  }

  async request(endpoint, options = {}) {
    const headers = {
      'Content-Type': 'application/json',
      ...options.headers
    };

    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
    }

    const response = await fetch(`${this.baseURL}/ahgmh/v1${endpoint}`, {
      ...options,
      headers
    });

    return response.json();
  }

  // Public methods
  async getInfo() {
    return this.request('/info');
  }

  async getSpecies() {
    return this.request('/species');
  }

  async getSummary(species, meldegruppe) {
    const params = new URLSearchParams({ species, meldegruppe });
    return this.request(`/summary?${params}`);
  }

  async getSubmissions(filters = {}) {
    const params = new URLSearchParams(filters);
    return this.request(`/submissions?${params}`);
  }

  // Authenticated methods
  async createSubmission(data) {
    return this.request('/submissions', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }

  async getMySubmissions(page = 1) {
    return this.request(`/submissions/my?page=${page}`);
  }

  async getUserProfile() {
    return this.request('/user/profile');
  }

  async exportSubmissions(filters = {}) {
    const params = new URLSearchParams(filters);
    return this.request(`/export?${params}`);
  }
}

// Verwendung
const api = new AbschussplanAPI('https://ihre-website.de/wp-json');

// Login
await api.login('obmann.mueller', 'passwort');

// Meldung erstellen
const result = await api.createSubmission({
  species: 'Rotwild',
  date: '2024-10-26',
  category: 'Wildkalb (AK0)',
  wus: '1234567',
  jagdbezirk: 'Jagdbezirk 1',
  bemerkung: 'Im Nordrevier'
});

console.log(result);
```

---

## Rate Limiting

Aktuell gibt es kein Rate Limiting. Falls nötig, kann dies über ein WordPress-Plugin wie "WP REST API Controller" implementiert werden.

---

## Support

Bei Fragen oder Problemen:
- GitHub Issues: https://github.com/foe05/pr25_one/issues
- E-Mail: [DEINE-EMAIL]

---

**Version:** 1.0.0
**Letzte Aktualisierung:** 27. Oktober 2024
