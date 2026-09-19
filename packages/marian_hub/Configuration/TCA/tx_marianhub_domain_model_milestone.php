<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:marian_hub/Resources/Private/Language/locallang_db.xlf:tx_marianhub_domain_model_milestone',
        'label' => 'title',
        'label_alt' => 'date',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'date ASC',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'title,description',
        'iconfile' => 'EXT:marian_hub/Resources/Public/Icons/milestone.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'title' => [
            'label' => 'Termin',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'description' => [
            'label' => 'Worum geht es',
            'config' => [
                'type' => 'text',
                'rows' => 4,
                'cols' => 40,
            ],
        ],
        'date' => [
            'label' => 'Datum',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'required' => true,
                'default' => 0,
            ],
        ],
        'kind' => [
            'label' => 'Art',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Deadline', 'value' => 'deadline'],
                    ['label' => 'Klausur/Prüfung', 'value' => 'exam'],
                    ['label' => 'Veranstaltung', 'value' => 'event'],
                    ['label' => 'Mottowoche', 'value' => 'mottoweek'],
                    ['label' => 'Abiball', 'value' => 'ball'],
                    ['label' => 'Fahrt', 'value' => 'trip'],
                ],
                'default' => 'deadline',
            ],
        ],
        'is_countdown_target' => [
            'label' => 'Ziel des großen Countdowns',
            'description' => 'Genau einen Termin markieren – auf den zählt die Startseite herunter.',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'title, date, kind, description, is_countdown_target,
                --div--;Zugriff, hidden',
        ],
    ],
];
