<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:marian_hub/Resources/Private/Language/locallang_db.xlf:tx_marianhub_domain_model_logentry',
        'label' => 'title',
        'label_alt' => 'entry_date',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'hideTable' => true,
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'title,bodytext',
        'iconfile' => 'EXT:marian_hub/Resources/Public/Icons/logentry.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'project' => [
            'label' => 'Projekt',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'title' => [
            'label' => 'Was war los',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'entry_date' => [
            'label' => 'Datum',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'default' => 0,
            ],
        ],
        'bodytext' => [
            'label' => 'Notiz',
            'config' => [
                'type' => 'text',
                'rows' => 6,
                'cols' => 40,
            ],
        ],
        'mood' => [
            'label' => 'Stimmung',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Läuft super', 'value' => 'great'],
                    ['label' => 'Normaler Fortschritt', 'value' => 'neutral'],
                    ['label' => 'Festgefahren', 'value' => 'stuck'],
                    ['label' => 'Durchbruch', 'value' => 'breakthrough'],
                ],
                'default' => 'neutral',
            ],
        ],
        'hours_spent' => [
            'label' => 'Zeit (Stunden)',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'size' => 8,
                'range' => ['lower' => 0],
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'title, entry_date, bodytext, mood, hours_spent, hidden',
        ],
    ],
];
