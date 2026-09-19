<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:marian_hub/Resources/Private/Language/locallang_db.xlf:tx_marianhub_domain_model_sensor',
        'label' => 'title',
        'label_alt' => 'identifier',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'location ASC, title ASC',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'title,identifier,description,location',
        'iconfile' => 'EXT:marian_hub/Resources/Public/Icons/sensor.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'title' => [
            'label' => 'Sensor',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'identifier' => [
            'label' => 'Kennung',
            'description' => 'Wird im Sketch als "sensor" mitgeschickt. Kleinbuchstaben, Ziffern, Bindestrich.',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,lower,nospace,unique',
                'is_in' => 'abcdefghijklmnopqrstuvwxyz0123456789-_',
                'required' => true,
            ],
        ],
        'description' => [
            'label' => 'Beschreibung',
            'config' => [
                'type' => 'text',
                'rows' => 4,
                'cols' => 40,
            ],
        ],
        'location' => [
            'label' => 'Standort',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'kind' => [
            'label' => 'Messgröße',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Temperatur', 'value' => 'temperature'],
                    ['label' => 'Luftfeuchte', 'value' => 'humidity'],
                    ['label' => 'Luftdruck', 'value' => 'pressure'],
                    ['label' => 'CO₂', 'value' => 'co2'],
                    ['label' => 'Helligkeit', 'value' => 'brightness'],
                    ['label' => 'Abstand', 'value' => 'distance'],
                    ['label' => 'Bodenfeuchte', 'value' => 'moisture'],
                    ['label' => 'Lautstärke', 'value' => 'noise'],
                    ['label' => 'Leistung', 'value' => 'power'],
                    ['label' => 'Sonstiges', 'value' => 'generic'],
                ],
                'default' => 'generic',
            ],
        ],
        'unit' => [
            'label' => 'Einheit',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'trim',
                'placeholder' => '°C',
            ],
        ],
        'decimals' => [
            'label' => 'Nachkommastellen',
            'config' => [
                'type' => 'number',
                'size' => 5,
                'range' => ['lower' => 0, 'upper' => 4],
                'default' => 1,
            ],
        ],
        'token_hash' => [
            'label' => 'API-Token',
            'description' => 'Token erzeugen: vendor/bin/typo3 marian:sensor:token <Kennung>. Gespeichert wird nur der Hash.',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'readOnly' => true,
                'placeholder' => 'noch kein Token vergeben',
            ],
        ],
        'warn_min' => [
            'label' => 'Warnung unter',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'size' => 10,
                'nullable' => true,
                'default' => null,
            ],
        ],
        'warn_max' => [
            'label' => 'Warnung über',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'size' => 10,
                'nullable' => true,
                'default' => null,
            ],
        ],
        'last_seen' => [
            'label' => 'Zuletzt gemeldet',
            'config' => [
                'type' => 'datetime',
                'readOnly' => true,
                'default' => 0,
            ],
        ],
        'retention_days' => [
            'label' => 'Messwerte aufbewahren (Tage)',
            'description' => '0 = für immer. Aufräumen erledigt marian:sensor:purge.',
            'config' => [
                'type' => 'number',
                'size' => 6,
                'range' => ['lower' => 0],
                'default' => 90,
            ],
        ],
        'active' => [
            'label' => 'Nimmt Messwerte an',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 1,
            ],
        ],
        'project' => [
            'label' => 'Gehört zu Projekt',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_marianhub_domain_model_project',
                'items' => [
                    ['label' => '– keins –', 'value' => 0],
                ],
                'default' => 0,
            ],
        ],
        'readings' => [
            'label' => 'Messwerte',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_marianhub_domain_model_reading',
                'foreign_field' => 'sensor',
                'foreign_sortby' => 'measured_at',
                'appearance' => [
                    'collapseAll' => true,
                    'expandSingle' => true,
                    'enabledControls' => [
                        'new' => false,
                    ],
                ],
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'title, identifier, location, description,
                --div--;Messung, kind, unit, decimals, warn_min, warn_max,
                --div--;Gerät, token_hash, active, last_seen, retention_days, project,
                --div--;Zugriff, hidden',
        ],
    ],
];
