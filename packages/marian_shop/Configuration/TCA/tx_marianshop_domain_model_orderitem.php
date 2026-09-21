<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Bestellposition',
        'label' => 'title',
        'label_alt' => 'sku',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'hideTable' => true,
        'searchFields' => 'title,sku',
        'iconfile' => 'EXT:marian_shop/Resources/Public/Icons/orderitem.svg',
        'security' => ['ignorePageTypeRestriction' => true],
    ],
    'columns' => [
        'order' => ['label' => 'Bestellung', 'config' => ['type' => 'passthrough']],
        'product' => ['label' => 'Artikel-ID', 'config' => ['type' => 'number', 'readOnly' => true]],
        'variant' => ['label' => 'Ausführungs-ID', 'config' => ['type' => 'number', 'readOnly' => true]],
        'title' => ['label' => 'Artikel', 'config' => ['type' => 'input', 'size' => 40, 'readOnly' => true]],
        'variant_title' => ['label' => 'Ausführung', 'config' => ['type' => 'input', 'size' => 20, 'readOnly' => true]],
        'sku' => ['label' => 'Artikelnummer', 'config' => ['type' => 'input', 'size' => 20, 'readOnly' => true]],
        'quantity' => ['label' => 'Menge', 'config' => ['type' => 'number', 'size' => 6, 'readOnly' => true]],
        'unit_gross' => ['label' => 'Einzelpreis (Cent)', 'config' => ['type' => 'number', 'size' => 10, 'readOnly' => true]],
        'line_gross' => ['label' => 'Positionssumme (Cent)', 'config' => ['type' => 'number', 'size' => 10, 'readOnly' => true]],
        'line_tax' => ['label' => 'enthaltene Steuer (Cent)', 'config' => ['type' => 'number', 'size' => 10, 'readOnly' => true]],
        'tax_rate' => ['label' => 'Steuersatz', 'config' => ['type' => 'number', 'format' => 'decimal', 'size' => 8, 'readOnly' => true]],
    ],
    'types' => [
        '0' => [
            'showitem' => 'title, variant_title, sku, quantity, unit_gross, line_gross, line_tax, tax_rate, product, variant',
        ],
    ],
];
