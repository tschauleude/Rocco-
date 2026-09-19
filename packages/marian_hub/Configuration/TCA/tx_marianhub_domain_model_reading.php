<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:marian_hub/Resources/Private/Language/locallang_db.xlf:tx_marianhub_domain_model_reading',
        'label' => 'value',
        'label_alt' => 'measured_at',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'measured_at DESC',
        'hideTable' => true,
        'searchFields' => 'payload',
        'iconfile' => 'EXT:marian_hub/Resources/Public/Icons/reading.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'sensor' => [
            'label' => 'Sensor',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'value' => [
            'label' => 'Wert',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'size' => 12,
                'default' => 0,
            ],
        ],
        'measured_at' => [
            'label' => 'Gemessen um',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
            ],
        ],
        'payload' => [
            'label' => 'Rohdaten',
            'config' => [
                'type' => 'text',
                'rows' => 3,
                'cols' => 40,
                'readOnly' => true,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'value, measured_at, payload',
        ],
    ],
];
