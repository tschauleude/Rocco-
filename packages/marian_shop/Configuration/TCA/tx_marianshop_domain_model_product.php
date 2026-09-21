<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Artikel',
        'label' => 'title',
        'label_alt' => 'sku',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'title ASC',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'title,sku,subtitle,teaser,description,slug',
        'iconfile' => 'EXT:marian_shop/Resources/Public/Icons/product.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'title' => [
            'label' => 'Artikelname',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'slug' => [
            'label' => 'URL-Segment',
            'config' => [
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => [
                    'fields' => ['title'],
                    'replacements' => ['/' => '-'],
                ],
                'fallbackCharacter' => '-',
                'eval' => 'unique',
                'default' => '',
            ],
        ],
        'sku' => [
            'label' => 'Artikelnummer',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim,upper',
                'max' => 60,
            ],
        ],
        'subtitle' => [
            'label' => 'Untertitel',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ],
        ],
        'teaser' => [
            'label' => 'Kurzbeschreibung',
            'description' => 'Ein, zwei Sätze für die Artikelliste.',
            'config' => [
                'type' => 'text',
                'rows' => 3,
                'cols' => 50,
                'max' => 500,
            ],
        ],
        'description' => [
            'label' => 'Beschreibung',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
                'rows' => 12,
                'cols' => 50,
            ],
        ],
        'price' => [
            'label' => 'Preis (brutto, €)',
            'description' => 'Der Endpreis, den Verbraucher zahlen – inklusive Umsatzsteuer.',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'size' => 12,
                'range' => ['lower' => 0],
                'required' => true,
                'default' => 0,
            ],
        ],
        'price_old' => [
            'label' => 'Früherer Preis (brutto, €)',
            'description' => 'Für einen Streichpreis. 0 = keiner.',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'size' => 12,
                'range' => ['lower' => 0],
                'default' => 0,
            ],
        ],
        'tax_rate' => [
            'label' => 'Umsatzsteuersatz',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '19 % (Regelsatz)', 'value' => '19.00'],
                    ['label' => '7 % (ermäßigt)', 'value' => '7.00'],
                    ['label' => '0 % (Kleinunternehmer / steuerfrei)', 'value' => '0.00'],
                ],
                'default' => '19.00',
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
        'stock_managed' => [
            'label' => 'Bestand führen',
            'description' => 'Aus: Artikel ist immer lieferbar (z. B. Vorbestellung, Download).',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 1,
            ],
        ],
        'max_per_order' => [
            'label' => 'Höchstmenge je Bestellung',
            'config' => [
                'type' => 'number',
                'size' => 6,
                'range' => ['lower' => 1, 'upper' => 99],
                'default' => 10,
            ],
        ],
        'weight' => [
            'label' => 'Gewicht (kg)',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'size' => 10,
                'range' => ['lower' => 0],
                'default' => 0,
            ],
        ],
        'delivery_time' => [
            'label' => 'Lieferzeit',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'placeholder' => '2–4 Werktage',
            ],
        ],
        'featured' => [
            'label' => 'Auf der Startseite hervorheben',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'images' => [
            'label' => 'Bilder',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'maxitems' => 8,
            ],
        ],
        'categories' => [
            'label' => 'Kategorien',
            'config' => [
                'type' => 'category',
            ],
        ],
        'variants' => [
            'label' => 'Ausführungen',
            'description' => 'Zum Beispiel Größen. Gibt es Ausführungen, muss beim Bestellen eine gewählt werden.',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_marianshop_domain_model_productvariant',
                'foreign_field' => 'product',
                'foreign_sortby' => 'sorting',
                'appearance' => [
                    'collapseAll' => false,
                    'useSortable' => true,
                    'showNewRecordLink' => true,
                    'newRecordLinkAddTitle' => true,
                    'levelLinksPosition' => 'bottom',
                ],
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'title, subtitle, slug, sku, teaser, description,
                --div--;Preis, price, price_old, tax_rate,
                --div--;Bestand, stock_managed, stock, max_per_order, delivery_time, weight,
                --div--;Ausführungen, variants,
                --div--;Bilder & Einordnung, images, categories, featured,
                --div--;Zugriff, hidden, starttime, endtime',
        ],
    ],
];
