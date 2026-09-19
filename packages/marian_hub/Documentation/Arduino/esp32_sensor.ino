/*
 * Marians Werkstatt – Beispiel-Sketch für ESP32 / ESP8266.
 *
 * Schickt alle SEND_INTERVAL Millisekunden einen Messwert an die TYPO3-Seite.
 * Getestet mit DHT22 an GPIO 4; für andere Sensoren nur readValue() anpassen.
 *
 * Benötigte Bibliotheken (Bibliotheksverwalter der Arduino IDE):
 *   - ArduinoJson    (Benoit Blanchon)
 *   - DHT sensor library + Adafruit Unified Sensor  (nur für dieses Beispiel)
 */

#include <WiFi.h>            // Für ESP8266: #include <ESP8266WiFi.h>
#include <HTTPClient.h>      // Für ESP8266: #include <ESP8266HTTPClient.h>
#include <ArduinoJson.h>
#include <DHT.h>

// ---------------------------------------------------------------- Konfiguration

const char* WIFI_SSID     = "DEIN_WLAN";
const char* WIFI_PASSWORD = "DEIN_WLAN_PASSWORT";

// Die Adresse der Ingest-API. HTTPS, damit das Token nicht im Klartext durchs Netz geht.
const char* INGEST_URL    = "https://marian.example.org/api/sensor/ingest";

// Kennung des Sensors aus dem TYPO3-Backend.
const char* SENSOR_ID     = "balkon-temp";

// Token aus: vendor/bin/typo3 marian:sensor:token balkon-temp
const char* SENSOR_TOKEN  = "HIER_DAS_TOKEN_EINSETZEN";

const unsigned long SEND_INTERVAL = 60UL * 1000UL;   // jede Minute
const uint8_t  DHT_PIN  = 4;
const uint8_t  DHT_TYPE = DHT22;

// ---------------------------------------------------------------------- Aufbau

DHT dht(DHT_PIN, DHT_TYPE);
unsigned long lastSend = 0;

void connectWifi() {
    if (WiFi.status() == WL_CONNECTED) {
        return;
    }

    Serial.print("Verbinde mit WLAN");
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

    unsigned long startedAt = millis();
    while (WiFi.status() != WL_CONNECTED && millis() - startedAt < 20000) {
        delay(500);
        Serial.print(".");
    }

    Serial.println();
    if (WiFi.status() == WL_CONNECTED) {
        Serial.print("IP: ");
        Serial.println(WiFi.localIP());
    } else {
        Serial.println("WLAN-Verbindung fehlgeschlagen, neuer Versuch beim nächsten Durchlauf.");
    }
}

/**
 * Liest den eigentlichen Messwert. Hier steckt der sensorspezifische Teil.
 */
float readValue() {
    return dht.readTemperature();
}

/**
 * Schickt einen Messwert an TYPO3.
 * Das Token wandert in den Header, nicht in den Body – so taucht es in keinem Log auf.
 */
bool sendReading(float value) {
    if (WiFi.status() != WL_CONNECTED) {
        return false;
    }

    JsonDocument doc;                 // ArduinoJson 7; bei Version 6: StaticJsonDocument<192>
    doc["sensor"] = SENSOR_ID;
    doc["value"]  = value;
    // Optional: alles Weitere landet als Zusatzdaten am Messwert.
    doc["rssi"]   = WiFi.RSSI();

    String body;
    serializeJson(doc, body);

    HTTPClient http;
    http.begin(INGEST_URL);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-Sensor-Token", SENSOR_TOKEN);
    http.setTimeout(8000);

    int status = http.POST(body);
    String response = http.getString();
    http.end();

    Serial.print("HTTP ");
    Serial.print(status);
    Serial.print(" – ");
    Serial.println(response);

    // 201 = angenommen. 401 = Kennung oder Token falsch. 403 = Sensor deaktiviert.
    return status == 201;
}

void setup() {
    Serial.begin(115200);
    delay(200);
    dht.begin();
    connectWifi();
}

void loop() {
    if (millis() - lastSend < SEND_INTERVAL && lastSend != 0) {
        delay(100);
        return;
    }

    connectWifi();

    float value = readValue();
    if (isnan(value)) {
        Serial.println("Sensor lieferte keinen Wert – überspringe diese Runde.");
    } else {
        Serial.print("Messwert: ");
        Serial.println(value);
        sendReading(value);
    }

    lastSend = millis();
}
