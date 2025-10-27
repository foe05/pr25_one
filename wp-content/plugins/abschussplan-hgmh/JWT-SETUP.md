# JWT Authentication Setup für Mobile App

Diese Anleitung erklärt, wie du JWT (JSON Web Tokens) für die mobile App konfigurierst.

## Was ist JWT?

JWT ermöglicht es der Mobile App, sich bei WordPress anzumelden und einen Token zu erhalten, mit dem dann alle weiteren API-Anfragen authentifiziert werden.

---

## Schritt 1: JWT Plugin installieren

### Option A: Via WordPress Admin

1. Gehe zu **Plugins → Neu hinzufügen**
2. Suche nach "JWT Authentication for WP REST API"
3. Installiere und aktiviere das Plugin

### Option B: Via WP-CLI

```bash
wp plugin install jwt-authentication-for-wp-rest-api --activate
```

### Option C: Manueller Download

1. Download: https://wordpress.org/plugins/jwt-authentication-for-wp-rest-api/
2. Entpacke die ZIP-Datei
3. Lade den Ordner nach `wp-content/plugins/` hoch
4. Aktiviere das Plugin im WordPress Admin

---

## Schritt 2: Secret Key generieren

Generiere einen sicheren Secret Key:

```bash
# Linux/Mac
openssl rand -base64 64

# Oder online:
# https://api.wordpress.org/secret-key/1.1/salt/
```

Beispiel-Output:
```
YourSuperSecretKeyHere1234567890abcdefghijklmnopqrstuvwxyz
```

---

## Schritt 3: wp-config.php konfigurieren

Öffne deine `wp-config.php` Datei und füge **vor** der Zeile `/* That's all, stop editing! */` folgendes hinzu:

```php
/* JWT Authentication */
define('JWT_AUTH_SECRET_KEY', 'YourSuperSecretKeyHere1234567890abcdefghijklmnopqrstuvwxyz');
define('JWT_AUTH_CORS_ENABLE', true);
```

**Wichtig:** Ersetze `YourSuperSecretKeyHere...` mit deinem generierten Secret Key!

---

## Schritt 4: .htaccess konfigurieren (Apache)

Falls du **Apache** nutzt, füge folgendes zu deiner `.htaccess` Datei hinzu:

```apache
# BEGIN JWT Authentication
RewriteEngine on
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
# END JWT Authentication
```

**Position:** Füge dies direkt unter `RewriteEngine On` ein, falls schon vorhanden.

### Nginx-Konfiguration

Falls du **Nginx** nutzt, füge zu deiner Server-Konfiguration hinzu:

```nginx
location / {
    try_files $uri $uri/ /index.php?$args;

    # JWT Authentication
    if ($http_authorization) {
        set $http_authorization $http_authorization;
    }
}
```

---

## Schritt 5: Testen der JWT-Endpunkte

### Test 1: Plugin-Info abrufen

```bash
curl https://ihre-website.de/wp-json/jwt-auth/v1
```

**Erwartete Antwort:**
```json
{
  "namespace": "jwt-auth/v1",
  "routes": {
    "/jwt-auth/v1": {...},
    "/jwt-auth/v1/token": {...},
    "/jwt-auth/v1/token/validate": {...}
  }
}
```

### Test 2: Token anfordern

```bash
curl -X POST https://ihre-website.de/wp-json/jwt-auth/v1/token \
  -H "Content-Type: application/json" \
  -d '{
    "username": "DEIN-USERNAME",
    "password": "DEIN-PASSWORT"
  }'
```

**Erwartete Antwort (Erfolg):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user_email": "deine@email.de",
  "user_nicename": "dein-username",
  "user_display_name": "Dein Name"
}
```

**Fehler-Response:**
```json
{
  "code": "[jwt_auth] incorrect_password",
  "message": "Falsches Passwort",
  "data": {
    "status": 403
  }
}
```

### Test 3: Token validieren

Kopiere den Token aus Test 2 und teste:

```bash
curl -X POST https://ihre-website.de/wp-json/jwt-auth/v1/token/validate \
  -H "Authorization: Bearer DEIN-TOKEN-HIER"
```

**Erwartete Antwort:**
```json
{
  "code": "jwt_auth_valid_token",
  "data": {
    "status": 200
  }
}
```

### Test 4: Geschützten Endpoint testen

```bash
curl https://ihre-website.de/wp-json/ahgmh/v1/user/profile \
  -H "Authorization: Bearer DEIN-TOKEN-HIER"
```

**Erwartete Antwort:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "username": "dein-username",
    ...
  }
}
```

---

## Fehlerbehandlung

### Problem: "Authorization header not found"

**Lösung 1 (Apache):** Überprüfe, ob `.htaccess` korrekt konfiguriert ist.

**Lösung 2 (Shared Hosting):** Manche Hoster blockieren den Authorization-Header. Kontaktiere deinen Hoster.

**Lösung 3:** Füge zu `wp-config.php` hinzu:
```php
if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}
```

### Problem: "jwt_auth_bad_config"

**Ursache:** `JWT_AUTH_SECRET_KEY` nicht gesetzt.

**Lösung:** Überprüfe `wp-config.php`, ob der Secret Key definiert ist.

### Problem: "Signature verification failed"

**Ursache:** Token wurde mit anderem Secret Key erstellt.

**Lösung:** Lösche alle Tokens und generiere neue (einfach neu einloggen).

### Problem: CORS-Fehler in Browser

**Lösung:** Füge zu `wp-config.php` hinzu:
```php
define('JWT_AUTH_CORS_ENABLE', true);
```

Und füge zu `functions.php` deines Themes hinzu:
```php
add_action('rest_api_init', function() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
});
```

---

## Sicherheits-Best-Practices

1. **Secret Key geheim halten:** Committe `wp-config.php` NICHT ins Git-Repository!

2. **HTTPS verwenden:** JWT Tokens sollten NUR über HTTPS übertragen werden:
   ```php
   if (!is_ssl() && !is_admin()) {
       wp_die('HTTPS ist erforderlich für API-Zugriff');
   }
   ```

3. **Token-Lebensdauer:** Tokens sind standardmäßig 7 Tage gültig. Anpassen:
   ```php
   add_filter('jwt_auth_expire', function() {
       return time() + (DAY_IN_SECONDS * 7); // 7 Tage
   });
   ```

4. **Rate Limiting:** Installiere ein Plugin wie "WP Limit Login Attempts" um Brute-Force-Angriffe zu verhindern.

---

## Token-Refresh-Strategie

Die Mobile App sollte Tokens regelmäßig erneuern:

```javascript
class APIClient {
  async refreshTokenIfNeeded() {
    const tokenAge = Date.now() - this.tokenTimestamp;
    const fiveDays = 5 * 24 * 60 * 60 * 1000;

    if (tokenAge > fiveDays) {
      // Token erneuern durch neuen Login
      await this.login(this.savedUsername, this.savedPassword);
    }
  }
}
```

---

## Zusätzliche Endpoints (JWT Plugin)

### POST /jwt-auth/v1/token
Login und Token erhalten

### POST /jwt-auth/v1/token/validate
Token validieren

### POST /jwt-auth/v1/token/refresh
Token erneuern (falls implementiert)

---

## Vollständiges Beispiel (curl)

```bash
#!/bin/bash

# Variablen
SITE_URL="https://ihre-website.de"
USERNAME="obmann.mueller"
PASSWORD="sicheres-passwort"

# 1. Token anfordern
echo "1. Token anfordern..."
TOKEN_RESPONSE=$(curl -s -X POST "${SITE_URL}/wp-json/jwt-auth/v1/token" \
  -H "Content-Type: application/json" \
  -d "{\"username\":\"${USERNAME}\",\"password\":\"${PASSWORD}\"}")

echo "$TOKEN_RESPONSE"

# Token extrahieren
TOKEN=$(echo "$TOKEN_RESPONSE" | jq -r '.token')

if [ "$TOKEN" == "null" ]; then
    echo "Fehler: Kein Token erhalten"
    exit 1
fi

echo "Token erhalten: ${TOKEN:0:20}..."

# 2. User-Profil abrufen
echo -e "\n2. User-Profil abrufen..."
curl -s "${SITE_URL}/wp-json/ahgmh/v1/user/profile" \
  -H "Authorization: Bearer ${TOKEN}" | jq

# 3. Neue Meldung erstellen
echo -e "\n3. Neue Meldung erstellen..."
curl -s -X POST "${SITE_URL}/wp-json/ahgmh/v1/submissions" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "species": "Rotwild",
    "date": "2024-10-26",
    "category": "Wildkalb (AK0)",
    "wus": "1234567",
    "jagdbezirk": "Jagdbezirk 1",
    "bemerkung": "Test-Meldung via API"
  }' | jq

echo -e "\n✅ Alle Tests erfolgreich!"
```

Speichere dies als `test-jwt.sh`, mache es ausführbar (`chmod +x test-jwt.sh`) und führe es aus.

---

## Checklist

- [ ] JWT Plugin installiert und aktiviert
- [ ] `JWT_AUTH_SECRET_KEY` in `wp-config.php` gesetzt
- [ ] `JWT_AUTH_CORS_ENABLE` in `wp-config.php` gesetzt
- [ ] `.htaccess` (Apache) oder Nginx-Config angepasst
- [ ] Test 1: Token kann angefordert werden
- [ ] Test 2: Token kann validiert werden
- [ ] Test 3: Geschützte Endpoints funktionieren
- [ ] HTTPS ist aktiviert (Produktion)
- [ ] Rate Limiting ist aktiviert

---

**Bei Problemen:**
- Überprüfe WordPress Debug-Log: `wp-content/debug.log`
- Aktiviere WP Debug: `define('WP_DEBUG', true);` in `wp-config.php`
- Teste mit Postman oder Insomnia statt curl
- Überprüfe Server-Error-Logs

---

**Fertig!** Die API ist jetzt bereit für die Mobile App. 🎉

Nächster Schritt: [React Native App einrichten](../../../MOBILE-APP-SETUP.md)
