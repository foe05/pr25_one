# App Assets - Hegegemeinschaftsmanagement

## Logos und Icons

Dieses Verzeichnis enthält SVG-Vorlagen für das App-Logo und Icons.

### Dateien

1. **logo.svg** - Vollständiges Logo mit Hirsch und Notizblock (512x512)
   - Verwendung: Splash Screen, Marketing, Website

2. **logo-simple.svg** - Vereinfachtes Logo mit "HG" Monogram (512x512)
   - Verwendung: App-Icon, wenn mehr Klarheit gewünscht ist

3. **icon-round.svg** - Rundes Icon (512x512)
   - Verwendung: Android Adaptive Icon

### Farben

- **Primär (Dunkelgrün):** `#2d5016`
- **Sekundär (Beige/Creme):** `#e8d4a0`
- **Akzent (Braun):** `#8b6f47`
- **Dunkel:** `#1a3009`

### Nächste Schritte

#### Für React Native (Android):

1. **Konvertierung zu PNG:**
   ```bash
   # Mit Inkscape oder ImageMagick
   inkscape logo.svg --export-png=icon-xxxhdpi.png --export-width=192
   inkscape logo.svg --export-png=icon-xxhdpi.png --export-width=144
   inkscape logo.svg --export-png=icon-xhdpi.png --export-width=96
   inkscape logo.svg --export-png=icon-hdpi.png --export-width=72
   inkscape logo.svg --export-png=icon-mdpi.png --export-width=48
   ```

2. **Online-Tool (einfacher):**
   - Gehe zu: https://icon.kitchen
   - Lade `logo.svg` oder `icon-round.svg` hoch
   - Wähle "Adaptive Icons" für Android
   - Generiere alle Größen automatisch
   - Download ZIP-Datei

3. **Icon-Größen für Android:**
   - `mipmap-mdpi` (48x48)
   - `mipmap-hdpi` (72x72)
   - `mipmap-xhdpi` (96x96)
   - `mipmap-xxhdpi` (144x144)
   - `mipmap-xxxhdpi` (192x192)

4. **Ablage in React Native:**
   ```
   mobile-app/android/app/src/main/res/
   ├── mipmap-mdpi/ic_launcher.png
   ├── mipmap-hdpi/ic_launcher.png
   ├── mipmap-xhdpi/ic_launcher.png
   ├── mipmap-xxhdpi/ic_launcher.png
   └── mipmap-xxxhdpi/ic_launcher.png
   ```

### Anpassungen

Falls du die Icons anpassen möchtest:
- SVG-Dateien können in Inkscape, Figma oder jedem SVG-Editor geöffnet werden
- Farben können einfach ersetzt werden
- Alle Dateien sind vektorbasiert und skalierbar

### Design-Konzept

Das Logo kombiniert:
- 🦌 **Hirsch mit Geweih** - Repräsentiert die Jagd und Wildtierverwaltung
- 📋 **Notizblock mit Stift** - Symbolisiert das Management und die Dokumentation
- 🌲 **Naturfarben (Grün/Braun)** - Verbindung zur Natur und Jagd

### Lizenz

Diese Assets wurden für das Projekt "Hegegemeinschaftsmanagement" erstellt und können frei verwendet werden.
