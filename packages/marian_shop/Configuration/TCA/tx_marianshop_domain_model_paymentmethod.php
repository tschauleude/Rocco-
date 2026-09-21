<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Zahlungsart',
        'label' => 'title',
        'label_alt' => 'provider',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'enablecolumns' => ['disabled' => 'hidden'],
        'searchFields' => 'title,description',
        'iconfile' => 'EXT:marian_shop/Resources/Public/Icons/payment.svg',
        'security' => ['ignorePageTypeRestriction' => true],
    ],
    'columns' => [
        'title' => [
            'label' => 'Bezeichnung',
            'config' => ['type' => 'input', 'size' => 40, 'eval' => 'trim', 'required' => true],
        ],
        'description' => [
            'label' => 'Hinweis für Kunden',
            'config' => ['type' => 'text', 'rows' => 3, 'cols' => 40],
        ],
        'provider' => [
            'label' => 'Abwicklung',
            'description' => 'Bestimmt, welche Implementierung die Zahlung übernimmt.',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Rechnung (keine Online-Zahlung)', 'value' => 'invoice'],
                    ['label' => 'Vorkasse / Überweisung', 'value' => 'prepayment'],
                    ['label' => 'Stripe Checkout (Karte, Apple Pay, Google Pay)', 'value' => 'stripe'],
                ],
                'default' => 'invoice',
            ],
        ],
        'surcharge' => [
            'label' => 'Aufschlag (brutto, €)',
            'config' => ['type' => 'number', 'format' => 'decimal', 'size' => 10, 'range' => ['lower' => 0], 'default' => 0],
        ],
        'instructions' => [
            'label' => 'Text nach der Bestellung',
            'description' => 'Erscheint auf der Bestätigungsseite und in der Bestellmail – bei Vorkasse die Bankverbindung.',
            'config' => ['type' => 'text', 'rows' => 5, 'cols' => 40],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'title, provider, description, surcharge, instructions, hidden'],
    ],
];
