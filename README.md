# Marians Werkstatt

Eine TYPO3-Seite, die vier Dinge gleichzeitig kann, ohne dass eines davon im Weg steht:

| Bereich | Was er leistet |
|---------|----------------|
| **Abi-Orga** | Kanban-Board, Komitees mit Fortschritt, Budget, Countdown und Terminleiste |
| **Wiki & Journalismus** | Artikel mit Quellenapparat, Wiki-Verlinkung, Register, Volltextsuche, Redaktionsstatus |
| **Sensorik** | HTTP-API für Arduino/ESP, Live-Dashboard, Diagramme, Warnschwellen, Aufbewahrungsfristen |
| **Projekte** | Projektübersicht nach Status, Logbuch, Stückliste, verknüpfte Sensoren |

Die Bereiche greifen ineinander: Ein Artikel kann auf ein Projekt zeigen, ein Projekt
auf seine Sensoren, ein Sensor zurück aufs Projekt.

## Was hier drin liegt

```
composer.json                    TYPO3 v13.4 als Composer-Projekt
config/sites/marian/             Site-Konfiguration inkl. sprechender URLs
packages/marian_hub/             Die Funktionen: Modelle, Controller, API, Kommandos
packages/marian_sitepackage/     Das Drumherum: Seitenlayouts, Navigation, Design
```

## Einrichten

### Mit DDEV (empfohlen)

```bash
ddev start                                   # installiert per Hook auch die Abhängigkeiten
ddev exec vendor/bin/typo3 setup             # Datenbank und Backend-Zugang anlegen
ddev launch /typo3
```

### Ohne DDEV

```bash
composer install
vendor/bin/typo3 setup
```

Beim `setup` nach der Datenbank fragen lassen und einen Backend-Benutzer anlegen.
Anschließend `vendor/bin/typo3 extension:setup` ausführen, damit die Tabellen entstehen.

### Danach im Backend

1. **Seitenbaum anlegen** (Vorschlag):

   ```
   Startseite                     Layout „Startseite“, Site-Root
   ├── Abi                        Plugin „Abi-Board & Countdown“ (Board)
   │   └── Termine                Plugin „Abi-Board & Countdown“ (Countdown)
   ├── Wiki                       Plugin „Wiki & Artikel“ (Liste)
   │   ├── Register               Plugin „Wiki & Artikel“ (Register)
   │   ├── Suche                  Plugin „Wiki & Artikel“ (Suche)
   │   └── Artikel                Plugin „Wiki & Artikel“ (Detail) – im Menü verstecken
   ├── Projekte                   Plugin „Projekte“ (Liste)
   │   └── Projekt                Plugin „Projekte“ (Detail) – im Menü verstecken
   ├── Sensoren                   Plugin „Sensor-Dashboard“, Layout „Volle Breite“
   │   └── Sensor                 Plugin „Sensor-Dashboard“ (Detail) – im Menü verstecken
   └── Daten                      Ordner (Seitentyp „Ordner“) für alle Datensätze
   ```

2. **Datensatz-Ordner eintragen**: In den Site-Einstellungen (Site-Management →
   Einstellungen) `marianhub.storagePid` auf die Seiten-ID des Ordners „Daten“ setzen.
   Ohne diesen Schritt finden die Plugins keine Datensätze.

3. **Detailseiten verknüpfen**: In jedem Listen-Plugin unter „Einstellungen“ die
   passende Detailseite auswählen. Die Auswahl steuert auch die `[[Wiki-Links]]`.

4. **Basis-URL anpassen**: `config/sites/marian/config.yaml`, Feld `base`.

Welche Ansicht ein Plugin zeigt, hängt an der Seite: Das Plugin „Wiki & Artikel“ zeigt
auf einer Seite die Liste und auf der Detailseite den Artikel – die Route entscheidet.
Für „Register“ und „Suche“ eigene Seiten anlegen und im Plugin die jeweilige Aktion
über die URL ansteuern (`/wiki/index`, `/wiki/suche`).

## Sensoren anschließen

Kurzfassung – ausführlich in
[`packages/marian_hub/Documentation/Arduino/README.md`](packages/marian_hub/Documentation/Arduino/README.md),
fertiger Sketch daneben in `esp32_sensor.ino`.

```bash
vendor/bin/typo3 marian:sensor:token balkon-temp   # Token erzeugen (erscheint einmalig)
```

```
POST /api/sensor/ingest
X-Sensor-Token: <token>

{"sensor": "balkon-temp", "value": 21.4}
```

Gespeichert wird nur der SHA-256-Hash des Tokens. Unbekannte Kennung und falsches
Token liefern dieselbe Antwort, damit sich über die API keine Sensoren durchprobieren
lassen.

## Kommandozeile

| Kommando | Zweck |
|----------|-------|
| `marian:sensor:token <kennung>` | Neues Gerätetoken erzeugen, altes wird ungültig |
| `marian:sensor:purge [--dry-run]` | Messwerte jenseits der Aufbewahrungsfrist löschen |

`marian:sensor:purge` lässt sich im Scheduler als tägliche Aufgabe eintragen – ein
Sensor im Minutentakt sammelt sonst eine halbe Million Zeilen im Jahr an.

## Wiki-Syntax

Im Artikeltext verweisen doppelte eckige Klammern auf andere Artikel:

```
[[mottowoche]]                → Link mit dem Titel des Zielartikels
[[mottowoche|unsere Woche]]   → Link mit eigenem Text
```

Existiert der Zielartikel nicht (oder ist er noch nicht veröffentlicht), erscheint
ein roter Hinweis statt eines toten Links. Der Zielartikel zeigt unter „Verweist
hierher“ automatisch alle Artikel, die auf ihn verlinken.

## Entwicklung

```bash
composer ci:php:lint     # Syntaxprüfung aller PHP-Dateien
composer ci:php:stan     # statische Analyse
composer ci:php:cs       # Codestil prüfen
```

Die Seite ist einsprachig (Deutsch) aufgesetzt. Für weitere Sprachen brauchen die
Tabellen in `packages/marian_hub/ext_tables.sql` die üblichen Übersetzungsfelder und
die TCA-Dateien die passenden `ctrl`-Einträge.
