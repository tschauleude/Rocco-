<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Versandart',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'enablecolumns' => ['disabled' => 'hidden'],
        'searchFields' => 'title,description',
        'iconfile' => 'EXT:marian_shop/Resources/Public/Icons/shipping.svg',
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
        'price' => [
            'label' => 'Versandkosten (brutto, €)',
            'config' => ['type' => 'number', 'format' => 'decimal', 'size' => 10, 'range' => ['lower' => 0], 'default' => 0],
        ],
        'free_from' => [
            'label' => 'Versandfrei ab Warenwert (€)',
            'description' => '0 = nie versandkostenfrei.',
            'config' => ['type' => 'number', 'format' => 'decimal', 'size' => 10, 'range' => ['lower' => 0], 'default' => 0],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'title, description, price, free_from, hidden'],
    ],
];
