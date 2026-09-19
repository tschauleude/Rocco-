# Sensoren anschließen

## Der Ablauf in vier Schritten

1. **Sensor im Backend anlegen** – Liste „Sensor“ im Datensatz-Ordner. Wichtig sind
   *Kennung* (z. B. `balkon-temp`), *Einheit* (`°C`) und *Nachkommastellen*.
2. **Token erzeugen** – auf dem Server:
   ```bash
   vendor/bin/typo3 marian:sensor:token balkon-temp
   ```
   Das Token erscheint genau einmal. In der Datenbank liegt nur der SHA-256-Hash.
3. **Sketch anpassen** – WLAN-Daten, URL, Kennung und Token in `esp32_sensor.ino` eintragen.
4. **Dashboard einbauen** – Inhaltselement „Sensor-Dashboard“ auf einer Seite platzieren.

## Die API

### Messwert abliefern

```
POST /api/sensor/ingest
Content-Type: application/json
X-Sensor-Token: <token>

{"sensor": "balkon-temp", "value": 21.4}
```

Antwort bei Erfolg (`201`):

```json
{"status": "ok", "sensor": "balkon-temp", "stored": 1, "skipped": 0}
```

### Mehrere Werte auf einmal

Geräte, die zwischendurch offline waren, können bis zu 200 Messungen nachreichen:

```json
{
  "sensor": "balkon-temp",
  "readings": [
    {"value": 21.4, "measured_at": 1726742400},
    {"value": 21.6, "measured_at": 1726742460, "rssi": -67}
  ]
}
```

Alles außer `value` und `measured_at` wird als Zusatzdaten am Messwert gespeichert
und in der Detailansicht angezeigt.

### Erreichbarkeit prüfen

```
GET /api/sensor/ping
→ {"status": "ok", "time": 1726742400}
```

Praktisch zum Debuggen: Antwortet der Ping, liegt es nicht am Netz, sondern am Token.

## Statuscodes

| Code | Bedeutung |
|------|-----------|
| 201  | Messwerte gespeichert |
| 400  | JSON kaputt oder kein gültiger Messwert dabei |
| 401  | Kennung oder Token stimmt nicht |
| 403  | Sensor ist im Backend deaktiviert |
| 405  | Falsche Methode – die Ingest-API nimmt nur POST |
| 413  | Zu viele Messwerte oder zu großer Body |

## Gut zu wissen

- **Zeitstempel**: Ohne `measured_at` nimmt der Server seine eigene Uhrzeit. Geräte ohne
  RTC schicken gern Sekunden seit dem Einschalten – solche Werte werden erkannt und
  durch die Serverzeit ersetzt.
- **Token austauschen**: `marian:sensor:token` einfach erneut aufrufen. Das alte Token
  gilt ab dem Moment nicht mehr.
- **Aufräumen**: `marian:sensor:purge` löscht Messwerte jenseits der eingestellten
  Aufbewahrungsfrist. Am besten als Scheduler-Aufgabe einmal täglich.
- **HTTPS**: Das Token steht im Header, aber nur TLS schützt es unterwegs.

## Andere Sensoren

Nur `readValue()` austauschen:

| Sensor | Bibliothek | Aufruf |
|--------|-----------|--------|
| DHT22 (Temperatur) | DHT sensor library | `dht.readTemperature()` |
| DHT22 (Luftfeuchte) | DHT sensor library | `dht.readHumidity()` |
| BMP280 (Luftdruck) | Adafruit BMP280 | `bmp.readPressure() / 100.0` |
| HC-SR04 (Abstand) | – | Laufzeit messen, `/ 58.0` |
| Bodenfeuchte (analog) | – | `analogRead(A0)` |
| MH-Z19 (CO₂) | MHZ19 | `mhz19.getCO2()` |

Für mehrere Messgrößen an einem Board: pro Messgröße einen eigenen Sensor-Datensatz
anlegen und pro Messgröße einen POST schicken.
