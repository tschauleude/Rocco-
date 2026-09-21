# Marians Werkstatt

Eine TYPO3-Seite, die vier Dinge gleichzeitig kann, ohne dass eines davon im Weg steht:

| Bereich | Was er leistet |
|---------|----------------|
| **Abi-Orga** | Kanban-Board, Komitees mit Fortschritt, Budget, Countdown und Terminleiste |
| **Wiki & Journalismus** | Artikel mit Quellenapparat, Wiki-Verlinkung, Register, Volltextsuche, Redaktionsstatus |
| **Sensorik** | HTTP-API für Arduino/ESP, Live-Dashboard, Diagramme, Warnschwellen, Aufbewahrungsfristen |
| **Projekte** | Projektübersicht nach Status, Logbuch, Stückliste, verknüpfte Sensoren |
| **Shop** | Katalog mit Größen, Warenkorb, Kasse, Kundenkonto, Bestellungen, Zahlung per Stripe/Rechnung/Vorkasse |

Die Bereiche greifen ineinander: Ein Artikel kann auf ein Projekt zeigen, ein Projekt
auf seine Sensoren, ein Sensor zurück aufs Projekt.

## Was hier drin liegt

```
composer.json                    TYPO3 v13.4 als Composer-Projekt
config/sites/marian/             Site-Konfiguration inkl. sprechender URLs
packages/marian_hub/             Die Funktionen: Modelle, Controller, API, Kommandos
packages/marian_sitepackage/     Das Drumherum: Seitenlayouts, Navigation, Design
packages/marian_shop/            Der Shop: Katalog, Warenkorb, Kasse, Konto, Zahlung
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

## Der Shop

Ausführlich in
[`packages/marian_shop/Documentation/README.md`](packages/marian_shop/Documentation/README.md).
Das Wichtigste:

### Vor dem ersten echten Verkauf

Ein Shop, der an Verbraucher verkauft, hat Pflichten. Die **Mechanik** dafür ist
eingebaut, die **Texte** nicht – die kann dir niemand abnehmen:

- **Impressum, AGB, Widerrufsbelehrung, Datenschutzerklärung** schreiben und auf
  die dafür angelegten Seiten setzen. Die mitgelieferten Texte sind ausdrücklich
  als Platzhalter markiert.
- In der Kasse sind die Kästchen für AGB und Widerrufsbelehrung Pflicht, der
  Bestellknopf heißt „Zahlungspflichtig bestellen", und die wesentlichen Angaben
  stehen unmittelbar darüber. **Diese Beschriftung bitte nicht ändern** – sie ist
  in § 312j Abs. 3 BGB so vorgeschrieben.
- Preise sind Bruttopreise, die Umsatzsteuer wird je Satz ausgewiesen,
  Versandkosten stehen dabei.
- Wer regelmäßig verkauft, betreibt ein Gewerbe. Das ist keine Frage der
  Software, sondern eine ans Finanzamt.

### Zahlungsarten

| Kennung | Ablauf |
|---------|--------|
| `invoice` | Rechnung, liegt der Lieferung bei |
| `prepayment` | Vorkasse, Bankverbindung steht am Datensatz |
| `stripe` | Stripe Checkout: Karte, Apple Pay, Google Pay |

Stripe braucht zwei Schlüssel. Sie gehören **nicht** ins Repository:

```bash
export STRIPE_SECRET_KEY=sk_live_...
export STRIPE_WEBHOOK_SECRET=whsec_...
```

Ersatzweise gehen sie in die Extension-Konfiguration (Admin Tools → Settings →
Extension Configuration → marian_shop); die Umgebungsvariable gewinnt.

Der Webhook-Endpunkt ist `/api/shop/stripe-webhook`. Diese Adresse im
Stripe-Dashboard eintragen und die Ereignisse `checkout.session.completed`,
`checkout.session.expired` und `checkout.session.async_payment_*` abonnieren.
**Ob eine Bestellung bezahlt ist, erfährt der Shop nur von dort** – die Rückkehr
des Kunden im Browser kann jeder aufrufen, den signierten Webhook nur Stripe.

Eine weitere Zahlungsart (PayPal, Mollie, …) ist eine Klasse, die
`PaymentProviderInterface` umsetzt, plus ein Datensatz im Backend. Am
Bestellablauf ändert sich nichts.

### Wichtige Details

- **Beträge** werden als ganzzahlige Cent geführt. Fließkomma und Geld vertragen
  sich nicht.
- **Versandkosten** werden bei gemischten Steuersätzen anteilig aufgeteilt, wie
  es das Umsatzsteuerrecht verlangt – nicht pauschal mit 19 % belegt.
- **Preise** stehen nie in der Sitzung. Der Warenkorb merkt sich nur Artikel-ID
  und Menge; gerechnet wird bei jedem Aufruf frisch aus dem Katalog.
- **Bestellungen** frieren Titel, Artikelnummer, Preis und Anschrift ein. Eine
  spätere Preisänderung verfälscht keine alte Bestellung.
- **Bestand** wird beim Bestellen in einem bedingten UPDATE abgebucht, damit
  zwei gleichzeitige Bestellungen nicht dasselbe letzte Stück bekommen.
- **Kundenkonten** sind TYPO3-Frontend-Benutzer. Anmeldung, Passwort-Hashing und
  Sitzung macht der Kern; die Extension baut daran nichts Eigenes.
- Neue Konten sind bis zur Bestätigung per E-Mail gesperrt (Double Opt-in).

### Einstellungen

In den Site-Einstellungen unter `marianshop.*`: Ordner für Artikel und
Bestellungen, Benutzergruppe neuer Konten und die Seiten-IDs für Shop,
Warenkorb, Kasse, Konto und die Rechtstexte.

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

Die Modelle der Extension liegen im Namespace `Marian\Hub`, die Tabellen heißen nach
dem Extension-Key `tx_marianhub_*`. Weil Extbase den Tabellennamen sonst aus dem
Namespace ableiten würde, steht die Zuordnung ausdrücklich in
`packages/marian_hub/Configuration/Extbase/Persistence/Classes.php`. Wer ein Modell
hinzufügt, trägt es dort mit ein.

Die Seite ist einsprachig (Deutsch) aufgesetzt. Für weitere Sprachen brauchen die
Tabellen in `packages/marian_hub/ext_tables.sql` die üblichen Übersetzungsfelder und
die TCA-Dateien die passenden `ctrl`-Einträge.
