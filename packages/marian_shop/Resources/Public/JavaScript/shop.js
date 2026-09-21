/**
 * Das bisschen Interaktion, das die Kasse braucht.
 *
 * Wichtig: Alles hier ist Komfort. Die Summe, die zählt, rechnet der Server
 * beim Absenden neu aus – diese Vorschau darf falsch liegen, ohne Schaden
 * anzurichten.
 */
(function () {
    'use strict';

    function formatPrice(cents) {
        return (cents / 100).toLocaleString('de-DE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' €';
    }

    /**
     * Blendet Abschnitte ein und aus, die an einer Checkbox hängen
     * (abweichende Lieferadresse, Konto anlegen).
     */
    function setupToggles() {
        document.querySelectorAll('input[type="checkbox"][data-toggle-target]').forEach(function (box) {
            var target = document.querySelector(box.dataset.toggleTarget);
            if (!target) {
                return;
            }

            var apply = function () {
                target.hidden = !box.checked;
            };

            box.addEventListener('change', apply);
            apply();
        });
    }

    /**
     * Zeigt Versandkosten und Gesamtsumme sofort an, wenn eine andere
     * Versand- oder Zahlungsart gewählt wird.
     */
    function setupTotalsPreview() {
        var form = document.querySelector('.checkout');
        if (!form) {
            return;
        }

        var summary = form.querySelector('.summary');
        if (!summary) {
            return;
        }

        var rows = summary.querySelectorAll('.summary__row');
        if (rows.length < 3) {
            return;
        }

        var subtotalCell = rows[0].querySelector('dd');
        var shippingCell = rows[1].querySelector('dd');
        var totalCell = summary.querySelector('.summary__row--total dd');

        if (!subtotalCell || !shippingCell || !totalCell) {
            return;
        }

        // Die Zwischensumme steht fest; sie kommt aus dem gerenderten Text.
        var subtotal = Math.round(
            parseFloat(subtotalCell.textContent.replace(/[^\d,]/g, '').replace(',', '.')) * 100
        );

        if (isNaN(subtotal)) {
            return;
        }

        var update = function () {
            var shippingInput = form.querySelector('input[name="tx_marianshop_checkout[shippingMethod]"]:checked');
            var paymentInput = form.querySelector('input[name="tx_marianshop_checkout[paymentMethod]"]:checked');

            var shipping = 0;
            if (shippingInput) {
                var price = parseInt(shippingInput.dataset.price, 10) || 0;
                var freeFrom = parseInt(shippingInput.dataset.freeFrom, 10) || 0;
                shipping = (freeFrom > 0 && subtotal >= freeFrom) ? 0 : price;
            }

            var surcharge = paymentInput ? (parseInt(paymentInput.dataset.surcharge, 10) || 0) : 0;

            shippingCell.textContent = shipping === 0 ? 'kostenlos' : formatPrice(shipping);
            totalCell.textContent = formatPrice(subtotal + shipping + surcharge);

            // Die Steueraufschlüsselung hängt von der Verteilung der Nebenkosten
            // ab; die überlassen wir dem Server und markieren sie als Schätzung.
            summary.querySelectorAll('.summary__row--tax').forEach(function (row) {
                row.dataset.stale = 'true';
            });
        };

        form.querySelectorAll('input[name="tx_marianshop_checkout[shippingMethod]"], input[name="tx_marianshop_checkout[paymentMethod]"]')
            .forEach(function (input) {
                input.addEventListener('change', update);
            });

        update();
    }

    /**
     * Schützt vor Doppelbestellungen durch hektisches Klicken.
     */
    function setupSubmitGuard() {
        document.querySelectorAll('form.checkout').forEach(function (form) {
            form.addEventListener('submit', function () {
                var button = form.querySelector('button[type="submit"]');
                if (!button) {
                    return;
                }

                // Erst nach dem Absenden sperren, sonst wird der Wert nicht mitgeschickt.
                window.setTimeout(function () {
                    button.disabled = true;
                    button.textContent = 'Einen Moment …';
                }, 0);
            });
        });
    }

    function init() {
        setupToggles();
        setupTotalsPreview();
        setupSubmitGuard();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
