# nexTab CPT Media Importer

Automatischer Import von Medien-Dateien als Posts in Custom Post Types.

## Features

- 🎯 **Admin-Interface** - Benutzerfreundliche Upload-Oberfläche im Backend
- 🚀 **WP-CLI Support** - Schneller Bulk-Import via Kommandozeile
- 🔄 **Automatische Alt-Tags** - Generierung aus Dateinamen (z.B. "Audi-Logo.svg" → "Audi Logo")
- 📦 **Batch-Processing** - Import mehrerer Dateien auf einmal
- ✅ **Duplikat-Prüfung** - Bereits existierende Posts werden übersprungen
- 🎨 **Featured Images** - Automatisches Setzen des Logos als Beitragsbild

## Installation

Das Plugin ist als **mu-plugin** (Must-Use Plugin) installiert und wird automatisch von WordPress geladen. Keine Aktivierung notwendig!

## Verwendung

### Option 1: Admin-Interface

1. Im WordPress-Backend zu **Werkzeuge → CPT Media Importer** navigieren
2. Custom Post Type auswählen (z.B. "kundenlogo")
3. Dateien hochladen (Drag & Drop oder Klick)
4. "Start Import" klicken

### Option 2: WP-CLI

Für Bulk-Imports direkt von einem lokalen Ordner:

```bash
wp nxt import-media --directory=/pfad/zum/ordner --post-type=kundenlogo
```

#### Beispiel:

```bash
wp nxt import-media --directory=/Users/max/Desktop/kundenlogos --post-type=kundenlogo
```

#### Parameter:

- `--directory` (erforderlich) - Pfad zum Ordner mit den Logos
- `--post-type` (erforderlich) - Ziel Custom Post Type

### Option 3: REST API

Für externe Tools oder automatisierte Workflows:

**Endpoint:** `POST /wp-json/nxt/v1/import-media`

**Parameter:**
- `post_type` - Ziel CPT
- `files[]` - Array von Dateien (multipart/form-data)

**Authentifizierung:** WordPress-Session oder Application Password erforderlich

## Dateinamen-Konvention

Das Plugin konvertiert Dateinamen automatisch in lesbare Titel und Alt-Tags:

| Dateiname | Post-Titel | Alt-Tag |
|-----------|------------|---------|
| `Audi-Logo.svg` | Audi | Audi |
| `BMW_corporate-logo.png` | Bmw Corporate | Bmw Corporate |
| `microsoft-logo-2024.jpg` | Microsoft 2024 | Microsoft 2024 |

**Regeln:**
- Bindestriche (`-`) und Unterstriche (`_`) werden zu Leerzeichen
- Das Wort "Logo" wird entfernt (case-insensitive)
- Mehrfache Leerzeichen werden zu einem einzelnen
- Erster Buchstabe jedes Wortes wird großgeschrieben
- Dateiendung wird entfernt

## Unterstützte Dateiformate

- JPG / JPEG
- PNG
- GIF
- SVG
- WebP

## Duplikat-Erkennung

Das Plugin prüft automatisch, ob ein Post mit dem gleichen Titel bereits existiert. Duplikate werden übersprungen und im Report angezeigt.

## Workflow-Beispiel

### Szenario: 50 Kundenlogos importieren

1. **Vorbereitung:**
   - Alle Logos in einem Ordner sammeln (z.B. `/Users/max/Desktop/kundenlogos/`)
   - Dateinamen sinnvoll benennen (z.B. `Firmenname-Logo.svg`)

2. **Import via WP-CLI:**
   ```bash
   cd /pfad/zum/wordpress
   wp nxt import-media --directory=/Users/max/Desktop/kundenlogos --post-type=kundenlogo
   ```

3. **Ergebnis:**
   ```
   Starting import from: /Users/max/Desktop/kundenlogos
   Target post type: kundenlogo

   ✓ Audi-Logo.svg → Post ID: 123
   ✓ BMW-Logo.png → Post ID: 124
   ✓ Microsoft-Logo.svg → Post ID: 125
   ...

   Import complete!
   Successful: 48
   Failed: 2
   Total: 50
   ```
   
   **Achtung:** Die Dateinamen enthalten "Logo", aber die erstellten Posts heißen nur "Audi", "BMW", "Microsoft" (ohne "Logo").

## Wiederverwendbarkeit

Dieses Plugin kann für verschiedene Szenarien verwendet werden:

- **Kundenlogos** → CPT "kundenlogo"
- **Team-Mitglieder** → CPT "team" (Profilbilder)
- **Partner-Logos** → CPT "partner"
- **Projekt-Screenshots** → CPT "projekt"
- **Produktbilder** → CPT "produkt"

## Technische Details

### Struktur:
```
wp-content/mu-plugins/
├── nxt-cpt-media-importer.php    # Haupt-Plugin-Datei
├── templates/
│   └── admin-page.php             # Admin-Interface Template
├── assets/
│   ├── admin-script.js            # JavaScript für Admin-UI
│   └── admin-style.css            # Styles für Admin-UI
└── README.md                      # Diese Datei
```

### Funktionen:

**PHP-Klasse:** `NXT_CPT_Media_Importer`

- `import_single_file()` - Importiert eine einzelne Datei
- `import_from_directory()` - Importiert alle Dateien aus einem Ordner
- `generate_alt_text()` - Generiert Alt-Tag aus Dateinamen
- `generate_post_title()` - Generiert Post-Titel aus Dateinamen

### WordPress-Hooks:

- `admin_menu` - Registriert Admin-Seite
- `wp_ajax_nxt_import_media_batch` - AJAX-Handler für Batch-Import
- `rest_api_init` - Registriert REST-Endpoint

### WP-CLI:

- `wp nxt import-media` - CLI-Command für Bulk-Import

## Troubleshooting

### "Directory does not exist"
→ Stelle sicher, dass der Pfad korrekt ist und existiert

### "Insufficient permissions"
→ Du musst als Administrator eingeloggt sein

### "Post already exists"
→ Ein Post mit diesem Titel existiert bereits. Duplikat wird übersprungen.

### SVG-Uploads schlagen fehl
→ Prüfe ob SVG-Uploads in WordPress erlaubt sind (bereits im Theme aktiviert)

## Support

Bei Fragen oder Problemen:
- **E-Mail:** info@nextab.de
- **Web:** https://nextab.de

---

**Version:** 1.0.0  
**Autor:** nexTab / Cursor mit Claude Sonnet 4.5
**Lizenz:** Proprietär
