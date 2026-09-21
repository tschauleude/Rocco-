# Shop – Handbuch

## Einmal einrichten

1. **Ordner anlegen**: einen Seitentyp „Ordner" für Artikel, Bestellungen,
   Adressen und Kundenkonten. Seiten-ID in den Site-Einstellungen unter
   `marianshop.storagePid` und `marianshop.catalogPid` eintragen.
2. **Benutzergruppe** für Kunden anlegen und unter `marianshop.customerGroup`
   hinterlegen. Ohne Gruppe landen Registrierungen in keiner Gruppe und sehen
   gruppengeschützte Inhalte nicht.
3. **Seiten** für Shop, Artikeldetail, Warenkorb, Kasse, Konto sowie AGB,
   Widerruf und Datenschutz anlegen und in den Einstellungen verknüpfen.
4. **Versandarten** anlegen (mindestens eine).
5. **Zahlungsarten** anlegen. Bei Vorkasse gehört die Bankverbindung in das
   Feld „Text nach der Bestellung".
6. **Artikel** anlegen.

## Artikel

| Feld | Bedeutung |
|------|-----------|
| Preis | **Bruttopreis**, also das, was der Kunde zahlt |
| Umsatzsteuersatz | 19 %, 7 % oder 0 % |
| Bestand führen | Aus: immer lieferbar (Vorbestellung, Spende, Download) |
| Höchstmenge je Bestellung | Bremse gegen Vertipper und Hamsterkäufe |
| Ausführungen | Größen o. Ä. Gibt es welche, **muss** beim Bestellen eine gewählt werden |

Bei Artikeln mit Ausführungen liegt der Bestand an der Ausführung, nicht am
Artikel. Die Verfügbarkeit des Artikels ist die Summe über alle Ausführungen.

Der Preisunterschied einer Ausführung darf negativ sein – etwa für Kindergrößen.

## Bestellablauf

```
Katalog → Artikel → Warenkorb → Kasse → Zahlung → Bestätigung
                                  │
                                  ├── Rechnung/Vorkasse: sofort bestätigt, Zahlung offen
                                  └── Stripe: Weiterleitung, Bestätigung kommt per Webhook
```

Die Bestellnummer entsteht aus Jahr und Datensatz-ID (`2026-00042`) – eindeutig
ohne Zählertabelle und ohne dass zwei gleichzeitige Bestellungen kollidieren.

## Stripe einrichten

1. Im Stripe-Dashboard die API-Schlüssel holen.
2. Auf dem Server setzen:
   ```bash
   STRIPE_SECRET_KEY=sk_live_...
   STRIPE_WEBHOOK_SECRET=whsec_...
   ```
3. Webhook-Endpunkt `https://deine-domain/api/shop/stripe-webhook` eintragen und
   abonnieren: `checkout.session.completed`, `checkout.session.expired`,
   `checkout.session.async_payment_succeeded`, `checkout.session.async_payment_failed`.
4. Zahlungsart mit Abwicklung „Stripe Checkout" anlegen.

Solange kein Schlüssel gesetzt ist, blendet die Kasse die Stripe-Zahlungsart
automatisch aus – es soll niemand etwas wählen können, das nicht funktioniert.

### Warum der Webhook und nicht die Rückkehr des Kunden?

Die Erfolgsseite nach der Zahlung kann jeder aufrufen, der die Adresse errät.
Der Webhook ist signiert: Stripe hängt an jede Anfrage einen HMAC über
Zeitstempel und Rumpf. Der Shop prüft diese Signatur, lehnt Anfragen ab, die
älter als fünf Minuten sind, und verbucht jede Zahlung nur einmal – Stripe
stellt Ereignisse absichtlich mehrfach zu.

## Statuscodes des Webhooks

| Code | Bedeutung |
|------|-----------|
| 200 | Verarbeitet (auch wenn das Ereignis nicht ausgewertet wird) |
| 400 | Signatur ungültig oder JSON kaputt |
| 405 | Falsche Methode – der Endpunkt nimmt nur POST |
| 413 | Anfrage zu groß |
| 503 | Kein Webhook-Secret hinterlegt |

Nicht ausgewertete Ereignisse werden bewusst mit 200 quittiert, sonst stellt
Stripe sie tagelang erneut zu.

## Eine weitere Zahlungsart

```php
class PayPalProvider implements PaymentProviderInterface
{
    public function getIdentifier(): string { return 'paypal'; }
    public function getLabel(): string { return 'PayPal'; }
    public function isAvailable(): bool { return $this->settings->hasPayPalKeys(); }

    public function start(Order $order, PaymentContext $context): PaymentResult
    {
        // Zahlung anlegen und den Kunden weiterleiten
        return PaymentResult::redirect($url, $referenz);
    }
}
```

Die Klasse wird über `Configuration/Services.yaml` automatisch eingesammelt –
alles, was `PaymentProviderInterface` umsetzt, bekommt das Tag
`marianshop.payment_provider`. Danach im Backend eine Zahlungsart mit der
Kennung `paypal` anlegen, fertig.

## Was bewusst nicht drin ist

- **Rechnungs-PDF.** Die Bestellbestätigung ist eine E-Mail, kein Beleg nach
  § 14 UStG. Für echte Rechnungen braucht es fortlaufende Rechnungsnummern und
  eine revisionssichere Ablage.
- **Gutscheine und Rabatte.**
- **Teillieferungen und Retouren.** Ein Widerruf wird derzeit von Hand
  abgewickelt: Bestellung auf „Storniert" setzen, Geld zurückerstatten.
- **Mehrere Währungen und Länder.** Ausgelegt ist alles auf Euro und den
  deutschen Steuersatz; Österreich und Schweiz sind als Lieferländer wählbar,
  die Steuerbehandlung dafür ist aber nicht abgebildet.

## Beträge und Steuern

Alles rechnet in ganzzahligen Cent. Versandkosten und Zahlungsaufschläge werden
bei gemischten Steuersätzen anteilig auf die Sätze verteilt; der Rundungsrest
landet auf dem größten Anteil, damit die Summe der Steuerzeilen exakt der
Gesamtsumme entspricht. Die Aufschlüsselung wird bei der Bestellung als JSON
eingefroren.
