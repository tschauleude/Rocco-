<?php

declare(strict_types=1);

/**
 * Bestellungen sind ein Protokoll dessen, was passiert ist. Beträge und
 * Positionen stehen deshalb schreibgeschützt im Backend – nur Bearbeitungs-
 * und Zahlungsstand lassen sich pflegen.
 */
return [
    'ctrl' => [
        'title' => 'Bestellung',
        'label' => 'order_number',
        'label_alt' => 'email',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'ordered_at DESC',
        'searchFields' => 'order_number,email,phone',
        'iconfile' => 'EXT:marian_shop/Resources/Public/Icons/order.svg',
        'security' => ['ignorePageTypeRestriction' => true],
    ],
    'columns' => [
        'order_number' => [
            'label' => 'Bestellnummer',
            'config' => ['type' => 'input', 'size' => 20, 'readOnly' => true],
        ],
        'ordered_at' => [
            'label' => 'Bestellt am',
            'config' => ['type' => 'datetime', 'readOnly' => true, 'default' => 0],
        ],
        'fe_user' => [
            'label' => 'Kundenkonto',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'fe_users',
                'items' => [['label' => '– Bestellung ohne Konto –', 'value' => 0]],
                'readOnly' => true,
                'default' => 0,
            ],
        ],
        'email' => ['label' => 'E-Mail', 'config' => ['type' => 'email', 'size' => 40, 'readOnly' => true]],
        'phone' => ['label' => 'Telefon', 'config' => ['type' => 'input', 'size' => 25, 'readOnly' => true]],
        'billing_address' => [
            'label' => 'Rechnungsanschrift',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_marianshop_domain_model_address',
                'maxitems' => 1,
                'appearance' => ['enabledControls' => ['new' => false, 'delete' => false]],
            ],
        ],
        'shipping_address' => [
            'label' => 'Lieferanschrift',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_marianshop_domain_model_address',
                'maxitems' => 1,
                'appearance' => ['enabledControls' => ['new' => false, 'delete' => false]],
            ],
        ],
        'items' => [
            'label' => 'Positionen',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_marianshop_domain_model_orderitem',
                'foreign_field' => 'order',
                'foreign_sortby' => 'sorting',
                'appearance' => [
                    'collapseAll' => true,
                    'enabledControls' => ['new' => false],
                ],
            ],
        ],
        'shipping_method' => [
            'label' => 'Versandart (Datensatz)',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_marianshop_domain_model_shippingmethod',
                'items' => [['label' => '– gelöscht –', 'value' => 0]],
                'readOnly' => true,
                'default' => 0,
            ],
        ],
        'shipping_title' => ['label' => 'Versandart', 'config' => ['type' => 'input', 'size' => 30, 'readOnly' => true]],
        'shipping_gross' => ['label' => 'Versandkosten (Cent)', 'config' => ['type' => 'number', 'size' => 10, 'readOnly' => true]],
        'payment_method' => [
            'label' => 'Zahlungsart (Datensatz)',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_marianshop_domain_model_paymentmethod',
                'items' => [['label' => '– gelöscht –', 'value' => 0]],
                'readOnly' => true,
                'default' => 0,
            ],
        ],
        'payment_title' => ['label' => 'Zahlungsart', 'config' => ['type' => 'input', 'size' => 30, 'readOnly' => true]],
        'payment_provider' => ['label' => 'Abwicklung', 'config' => ['type' => 'input', 'size' => 20, 'readOnly' => true]],
        'payment_surcharge_gross' => ['label' => 'Zahlungsaufschlag (Cent)', 'config' => ['type' => 'number', 'size' => 10, 'readOnly' => true]],
        'subtotal_gross' => ['label' => 'Zwischensumme (Cent)', 'config' => ['type' => 'number', 'size' => 10, 'readOnly' => true]],
        'total_gross' => ['label' => 'Gesamtsumme (Cent)', 'config' => ['type' => 'number', 'size' => 10, 'readOnly' => true]],
        'total_net' => ['label' => 'Netto (Cent)', 'config' => ['type' => 'number', 'size' => 10, 'readOnly' => true]],
        'tax_total' => ['label' => 'Steuer (Cent)', 'config' => ['type' => 'number', 'size' => 10, 'readOnly' => true]],
        'tax_breakdown' => ['label' => 'Steueraufschlüsselung', 'config' => ['type' => 'text', 'rows' => 3, 'readOnly' => true]],
        'status' => [
            'label' => 'Bearbeitungsstand',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Eingegangen', 'value' => 'new'],
                    ['label' => 'Bestätigt', 'value' => 'confirmed'],
                    ['label' => 'In Bearbeitung', 'value' => 'processing'],
                    ['label' => 'Versendet', 'value' => 'shipped'],
                    ['label' => 'Abgeschlossen', 'value' => 'completed'],
                    ['label' => 'Storniert', 'value' => 'cancelled'],
                ],
                'default' => 'new',
            ],
        ],
        'payment_status' => [
            'label' => 'Zahlungsstand',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Offen', 'value' => 'pending'],
                    ['label' => 'Bezahlt', 'value' => 'paid'],
                    ['label' => 'Fehlgeschlagen', 'value' => 'failed'],
                    ['label' => 'Erstattet', 'value' => 'refunded'],
                ],
                'default' => 'pending',
            ],
        ],
        'payment_reference' => ['label' => 'Referenz des Zahlungsdienstes', 'config' => ['type' => 'input', 'size' => 40, 'readOnly' => true]],
        'paid_at' => ['label' => 'Bezahlt am', 'config' => ['type' => 'datetime', 'default' => 0]],
        'customer_note' => ['label' => 'Nachricht des Kunden', 'config' => ['type' => 'text', 'rows' => 4, 'readOnly' => true]],
        'accepted_terms' => ['label' => 'AGB bestätigt', 'config' => ['type' => 'check', 'readOnly' => true, 'default' => 0]],
        'accepted_withdrawal' => ['label' => 'Widerrufsbelehrung bestätigt', 'config' => ['type' => 'check', 'readOnly' => true, 'default' => 0]],
    ],
    'types' => [
        '0' => [
            'showitem' => 'order_number, ordered_at, status, payment_status, paid_at,
                --div--;Kunde, fe_user, email, phone, billing_address, shipping_address,
                --div--;Positionen, items, customer_note,
                --div--;Beträge, subtotal_gross, shipping_method, shipping_title, shipping_gross, payment_method, payment_title, payment_surcharge_gross, total_gross, total_net, tax_total, tax_breakdown,
                --div--;Zahlung, payment_provider, payment_reference, accepted_terms, accepted_withdrawal',
        ],
    ],
];
