# Hegegemeinschaftsmanagement - React Native App Setup

Diese Anleitung erklärt, wie du das neue Repository für die Mobile App erstellst und die Entwicklung startest.

---

## Schritt 1: Neues GitHub Repository erstellen

### Via GitHub Web UI

1. Gehe zu https://github.com/new
2. **Repository name:** `hegegemeinschaftsmanagement-app`
3. **Description:** `Mobile App für Hegegemeinschafts-Management (React Native)`
4. **Visibility:** Private oder Public (deine Wahl)
5. **Initialize:**
   - ✅ Add a README file
   - ✅ Add .gitignore → Node
   - ⬜ Choose a license (optional: MIT)
6. Klicke **Create repository**

### Via GitHub CLI

```bash
gh repo create hegegemeinschaftsmanagement-app \
  --public \
  --description "Mobile App für Hegegemeinschafts-Management (React Native)" \
  --gitignore Node \
  --clone
```

---

## Schritt 2: Repository lokal klonen

```bash
cd ~/Projects  # Oder dein Projekt-Ordner
git clone https://github.com/DEIN-USERNAME/hegegemeinschaftsmanagement-app.git
cd hegegemeinschaftsmanagement-app
```

---

## Schritt 3: React Native App initialisieren

### Voraussetzungen prüfen

```bash
# Node.js (v18+)
node --version

# npm oder yarn
npm --version

# Watchman (Mac)
brew install watchman

# Java (für Android)
java --version  # Sollte Java 17 sein
```

### React Native CLI installieren

```bash
npm install -g react-native-cli
```

### Neue React Native App erstellen

```bash
npx react-native@latest init Hegegemeinschaftsmanagement --skip-install
cd Hegegemeinschaftsmanagement
```

**Hinweis:** Wir nutzen `--skip-install`, um später die Dependencies gemeinsam zu installieren.

---

## Schritt 4: Projekt-Struktur anpassen

### Erstelle die Ordnerstruktur

```bash
mkdir -p src/{api,screens,components,navigation,store,utils,constants}
mkdir -p src/screens/{OnboardingScreens,PublicScreens,AuthenticatedScreens,SettingsScreens}
mkdir -p assets/{images,icons,fonts}
```

### Dateien verschieben

```bash
# App.tsx nach src/ verschieben (falls gewünscht)
# oder direkt im Root lassen

# .gitignore erweitern
cat >> .gitignore << 'EOF'

# App-specific
.env
.env.local
*.jks
*.keystore
google-services.json
GoogleService-Info.plist

# iOS
ios/Pods/
ios/build/

# Android
android/app/build/
android/app/release/
android/.gradle/
android/app/src/main/assets/

# VS Code
.vscode/
EOF
```

---

## Schritt 5: Dependencies installieren

### Basis-Dependencies

```bash
npm install
```

### Navigation

```bash
npm install @react-navigation/native @react-navigation/stack @react-navigation/bottom-tabs
npm install react-native-screens react-native-safe-area-context
npm install react-native-gesture-handler react-native-reanimated
npm install @react-native-masked-view/masked-view
```

### UI & Styling

```bash
npm install react-native-paper
npm install react-native-vector-icons
npm install react-native-svg
```

### API & State

```bash
npm install axios
npm install @tanstack/react-query
npm install zustand  # oder redux-toolkit
```

### Storage

```bash
npm install @react-native-async-storage/async-storage
```

### Forms & Validation

```bash
npm install react-hook-form
npm install yup  # für Validierung
```

### Date & Time

```bash
npm install date-fns
npm install react-native-date-picker
```

### Dev Dependencies

```bash
npm install --save-dev @types/react-native-vector-icons
npm install --save-dev babel-plugin-module-resolver
```

---

## Schritt 6: Icons & Logo hinzufügen

### Icons aus WordPress-Plugin-Repo kopieren

```bash
# Von deinem WordPress-Plugin-Repo
cp -r /pfad/zu/pr25_one/app-assets/* ./assets/

# Oder manuell:
# Kopiere die SVG-Dateien aus app-assets/icons/ nach assets/icons/
```

### Icons zu PNG konvertieren

**Option A: Online-Tool (empfohlen für Anfänger)**

1. Gehe zu https://icon.kitchen
2. Lade `assets/icons/logo.svg` hoch
3. Konfiguriere:
   - Icon Type: Adaptive Icon
   - Background: Solid color (#2d5016)
   - Shape: Circle/Rounded Square
4. Download ZIP
5. Entpacke und kopiere nach `android/app/src/main/res/`

**Option B: Inkscape/ImageMagick**

```bash
# Inkscape installieren
brew install inkscape  # Mac
sudo apt install inkscape  # Linux

# Konvertiere zu verschiedenen Größen
cd assets/icons

for size in 48 72 96 144 192; do
  inkscape logo.svg --export-png=ic_launcher_${size}.png --export-width=$size
done

# Verschiebe zu Android-Ressourcen
cp ic_launcher_48.png ../../android/app/src/main/res/mipmap-mdpi/ic_launcher.png
cp ic_launcher_72.png ../../android/app/src/main/res/mipmap-hdpi/ic_launcher.png
cp ic_launcher_96.png ../../android/app/src/main/res/mipmap-xhdpi/ic_launcher.png
cp ic_launcher_144.png ../../android/app/src/main/res/mipmap-xxhdpi/ic_launcher.png
cp ic_launcher_192.png ../../android/app/src/main/res/mipmap-xxxhdpi/ic_launcher.png
```

---

## Schritt 7: Android-Konfiguration

### App-Name ändern

**Datei:** `android/app/src/main/res/values/strings.xml`

```xml
<resources>
    <string name="app_name">Hegegemeinschaftsmanagement</string>
</resources>
```

### Package-Name ändern

**Datei:** `android/app/build.gradle`

```gradle
android {
    ...
    defaultConfig {
        applicationId "com.hegegemeinschaft.abschussplan"
        ...
    }
}
```

**Hinweis:** Package-Namen vollständig ändern erfordert Umbenennen von Ordnern. Für MVP kannst du den Standard behalten.

### Permissions hinzufügen

**Datei:** `android/app/src/main/AndroidManifest.xml`

```xml
<manifest ...>
    <!-- Internet-Zugriff -->
    <uses-permission android:name="android.permission.INTERNET" />

    <!-- Optional: Camera für QR-Code-Scanner -->
    <!-- <uses-permission android:name="android.permission.CAMERA" /> -->

    <application ...>
        ...
    </application>
</manifest>
```

---

## Schritt 8: Erste API-Integration

### API-Client erstellen

**Datei:** `src/utils/api-client.js`

```javascript
import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';

class APIClient {
  constructor() {
    this.baseURL = null;
    this.token = null;
    this.client = null;
  }

  async initialize(baseURL) {
    this.baseURL = baseURL;
    this.client = axios.create({
      baseURL: `${baseURL}/wp-json`,
      timeout: 10000,
      headers: {
        'Content-Type': 'application/json',
      }
    });

    // Load saved token
    this.token = await AsyncStorage.getItem('jwt_token');
    if (this.token) {
      this.setAuthToken(this.token);
    }

    // Response interceptor for token expiration
    this.client.interceptors.response.use(
      response => response,
      error => {
        if (error.response?.status === 401) {
          // Token expired, clear and redirect to login
          this.clearAuth();
        }
        return Promise.reject(error);
      }
    );
  }

  setAuthToken(token) {
    this.token = token;
    this.client.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    AsyncStorage.setItem('jwt_token', token);
  }

  clearAuth() {
    this.token = null;
    delete this.client.defaults.headers.common['Authorization'];
    AsyncStorage.removeItem('jwt_token');
  }

  // Public endpoints
  async getPluginInfo() {
    const { data } = await this.client.get('/ahgmh/v1/info');
    return data;
  }

  async getSpecies() {
    const { data } = await this.client.get('/ahgmh/v1/species');
    return data;
  }

  async getSummary(species, meldegruppe) {
    const { data } = await this.client.get('/ahgmh/v1/summary', {
      params: { species, meldegruppe }
    });
    return data;
  }

  async getSubmissions(filters = {}) {
    const { data } = await this.client.get('/ahgmh/v1/submissions', {
      params: filters
    });
    return data;
  }

  // Authentication
  async login(username, password) {
    const { data } = await this.client.post('/jwt-auth/v1/token', {
      username,
      password
    });

    if (data.token) {
      this.setAuthToken(data.token);
    }

    return data;
  }

  // Authenticated endpoints
  async createSubmission(submissionData) {
    const { data } = await this.client.post('/ahgmh/v1/submissions', submissionData);
    return data;
  }

  async getMySubmissions(page = 1) {
    const { data } = await this.client.get('/ahgmh/v1/submissions/my', {
      params: { page }
    });
    return data;
  }

  async getUserProfile() {
    const { data } = await this.client.get('/ahgmh/v1/user/profile');
    return data;
  }
}

export default new APIClient();
```

---

## Schritt 9: Testen der App

### Android Emulator starten

```bash
# Emulator-Liste anzeigen
emulator -list-avds

# Emulator starten
emulator -avd Pixel_5_API_33

# Oder via Android Studio:
# Tools → Device Manager → Create/Start Emulator
```

### App starten

```bash
# Terminal 1: Metro Bundler
npx react-native start

# Terminal 2: Android Build & Install
npx react-native run-android
```

### Bei Fehlern

```bash
# Clean build
cd android && ./gradlew clean && cd ..

# Cache löschen
npm start -- --reset-cache

# Node modules neu installieren
rm -rf node_modules
npm install
```

---

## Schritt 10: Release-Keystore erstellen

### Keystore generieren

```bash
cd android/app

keytool -genkeypair -v \
  -storetype PKCS12 \
  -keystore release.keystore \
  -alias hegegemeinschaft \
  -keyalg RSA \
  -keysize 2048 \
  -validity 10000 \
  -storepass DEIN-PASSWORT \
  -keypass DEIN-PASSWORT \
  -dname "CN=Hegegemeinschaftsmanagement, OU=Mobile, O=HegeGemeinschaft, L=Berlin, ST=Berlin, C=DE"
```

**Wichtig:** Notiere Passwort und Alias sicher! Diese brauchst du für GitHub Secrets.

### Keystore zu Base64 konvertieren

```bash
base64 -i release.keystore -o release.keystore.base64

# Ausgabe anzeigen
cat release.keystore.base64
```

**Hinweis:** Kopiere den Base64-String für GitHub Secrets.

### .gitignore aktualisieren

```bash
echo "android/app/release.keystore" >> .gitignore
echo "android/app/release.keystore.base64" >> .gitignore
```

---

## Schritt 11: GitHub Actions einrichten

### Workflow-Datei erstellen

```bash
mkdir -p .github/workflows
```

**Datei:** `.github/workflows/android-release.yml`

*[Hier den Workflow-Code von vorher einfügen]*

### GitHub Secrets hinzufügen

1. Gehe zu GitHub → Repository → Settings → Secrets and variables → Actions
2. Klicke "New repository secret"
3. Füge hinzu:
   - `KEYSTORE_BASE64`: [Base64-String von release.keystore.base64]
   - `KEYSTORE_PASSWORD`: [Dein Keystore-Passwort]
   - `KEY_ALIAS`: `hegegemeinschaft`
   - `KEY_PASSWORD`: [Dein Key-Passwort]

---

## Schritt 12: Erstes Release erstellen

### Code committen und pushen

```bash
git add .
git commit -m "Initial React Native app setup"
git push origin main
```

### Release-Tag erstellen

```bash
# Version bump
npm version 1.0.0

# Tag pushen
git push --tags
```

**GitHub Actions startet automatisch und baut die APK!**

---

## Projekt-Struktur (Final)

```
hegegemeinschaftsmanagement-app/
├── .github/
│   └── workflows/
│       └── android-release.yml
├── android/
├── ios/
├── src/
│   ├── api/
│   │   ├── auth.js
│   │   ├── submissions.js
│   │   └── summary.js
│   ├── screens/
│   │   ├── OnboardingScreens/
│   │   │   ├── WelcomeScreen.js
│   │   │   ├── UrlInputScreen.js
│   │   │   ├── RoleSelectionScreen.js
│   │   │   └── LoginScreen.js
│   │   ├── PublicScreens/
│   │   │   ├── SummaryScreen.js
│   │   │   └── SummaryTableScreen.js
│   │   ├── AuthenticatedScreens/
│   │   │   ├── FormScreen.js
│   │   │   ├── MySubmissionsScreen.js
│   │   │   └── ExportScreen.js
│   │   └── SettingsScreens/
│   │       ├── InstanceManagerScreen.js
│   │       └── AccountScreen.js
│   ├── components/
│   │   ├── SubmissionForm.js
│   │   ├── SummaryCard.js
│   │   └── SubmissionListItem.js
│   ├── navigation/
│   │   ├── OnboardingNavigator.js
│   │   ├── MainNavigator.js
│   │   └── RootNavigator.js
│   ├── store/
│   │   ├── authStore.js
│   │   └── instanceStore.js
│   ├── utils/
│   │   ├── api-client.js
│   │   └── storage.js
│   └── constants/
│       ├── colors.js
│       └── config.js
├── assets/
│   ├── icons/
│   │   ├── logo.svg
│   │   └── icon-round.svg
│   └── images/
├── App.tsx
├── package.json
├── tsconfig.json
└── README.md
```

---

## Nächste Schritte

1. ✅ Repository erstellt
2. ✅ React Native app initialisiert
3. ✅ Dependencies installiert
4. ✅ Icons hinzugefügt
5. ✅ API-Client erstellt
6. ⏳ Screens implementieren (kommt als nächstes)
7. ⏳ Navigation einrichten
8. ⏳ Release testen

---

## Hilfreiche Kommandos

```bash
# App starten
npm start
npx react-native run-android

# Clean build
cd android && ./gradlew clean && cd .. && npx react-native run-android

# Logs anzeigen
npx react-native log-android

# Bundle-Size analysieren
npx react-native-bundle-visualizer

# TypeScript-Check
npx tsc --noEmit

# Lint
npm run lint
```

---

## Support

- React Native Docs: https://reactnative.dev/docs/getting-started
- React Navigation: https://reactnavigation.org/docs/getting-started
- React Native Paper: https://callstack.github.io/react-native-paper/

---

**Fertig!** Du kannst jetzt mit der Entwicklung der Screens beginnen. 🚀

Nächster Schritt: Implementierung der Onboarding-Screens (kommt im nächsten Commit)
