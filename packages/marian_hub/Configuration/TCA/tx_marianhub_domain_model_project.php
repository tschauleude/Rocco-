<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:marian_hub/Resources/Private/Language/locallang_db.xlf:tx_marianhub_domain_model_project',
        'label' => 'title',
        'label_alt' => 'status',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'started_at DESC, title ASC',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'title,subtitle,teaser,description,slug',
        'iconfile' => 'EXT:marian_hub/Resources/Public/Icons/project.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'title' => [
            'label' => 'Projekt',
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
        'subtitle' => [
            'label' => 'Untertitel',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ],
        ],
        'teaser' => [
            'label' => 'Kurzfassung',
            'config' => [
                'type' => 'text',
                'rows' => 3,
                'cols' => 50,
                'max' => 600,
            ],
        ],
        'description' => [
            'label' => 'Beschreibung',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
                'rows' => 15,
                'cols' => 50,
            ],
        ],
        'status' => [
            'label' => 'Status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Idee', 'value' => 'idea'],
                    ['label' => 'Planung', 'value' => 'planning'],
                    ['label' => 'Im Bau', 'value' => 'building'],
                    ['label' => 'Läuft', 'value' => 'running'],
                    ['label' => 'Fertig', 'value' => 'done'],
                    ['label' => 'Pausiert', 'value' => 'paused'],
                ],
                'default' => 'idea',
            ],
        ],
        'progress' => [
            'label' => 'Fortschritt (%)',
            'config' => [
                'type' => 'number',
                'size' => 6,
                'range' => ['lower' => 0, 'upper' => 100],
                'slider' => [
                    'step' => 5,
                    'width' => 200,
                ],
                'default' => 0,
            ],
        ],
        'started_at' => [
            'label' => 'Begonnen',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'default' => 0,
            ],
        ],
        'finished_at' => [
            'label' => 'Abgeschlossen',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'default' => 0,
            ],
        ],
        'repository_url' => [
            'label' => 'Code / Doku',
            'config' => [
                'type' => 'link',
                'size' => 40,
                'allowedTypes' => ['url'],
            ],
        ],
        'bill_of_materials' => [
            'label' => 'Stückliste',
            'description' => 'Eine Zeile pro Bauteil, optional: Bauteil | Anzahl | Preis',
            'config' => [
                'type' => 'text',
                'rows' => 8,
                'cols' => 50,
            ],
        ],
        'cover_image' => [
            'label' => 'Bild',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'maxitems' => 1,
            ],
        ],
        'categories' => [
            'label' => 'Themen',
            'config' => [
                'type' => 'category',
            ],
        ],
        'log_entries' => [
            'label' => 'Logbuch',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_marianhub_domain_model_logentry',
                'foreign_field' => 'project',
                'foreign_sortby' => 'sorting',
                'appearance' => [
                    'collapseAll' => true,
                    'expandSingle' => true,
                    'useSortable' => true,
                    'showNewRecordLink' => true,
                    'newRecordLinkAddTitle' => true,
                    'levelLinksPosition' => 'top',
                ],
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'title, subtitle, slug, teaser, description,
                --div--;Stand, status, progress, started_at, finished_at, repository_url,
                --div--;Logbuch, log_entries,
                --div--;Material, bill_of_materials,
                --div--;Medien & Einordnung, cover_image, categories,
                --div--;Zugriff, hidden',
        ],
    ],
];
