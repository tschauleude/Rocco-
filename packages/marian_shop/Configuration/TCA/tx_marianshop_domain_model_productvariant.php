<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Ausführung',
        'label' => 'title',
        'label_alt' => 'sku',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'hideTable' => true,
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'title,sku',
        'iconfile' => 'EXT:marian_shop/Resources/Public/Icons/variant.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'product' => [
            'label' => 'Artikel',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'title' => [
            'label' => 'Bezeichnung',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'required' => true,
                'placeholder' => 'M',
            ],
        ],
        'sku' => [
            'label' => 'Artikelnummer',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim,upper',
            ],
        ],
        'price_delta' => [
            'label' => 'Preisunterschied (€)',
            'description' => 'Aufschlag oder Abschlag gegenüber dem Grundpreis. Darf negativ sein.',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'size' => 10,
                'default' => 0,
            ],
        ],
        'stock' => [
            'label' => 'Bestand',
            'config' => [
                'type' => 'number',
                'size' => 8,
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'title, sku, price_delta, stock, hidden',
        ],
    ],
];
