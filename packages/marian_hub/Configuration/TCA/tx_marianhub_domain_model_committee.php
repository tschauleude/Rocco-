<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:marian_hub/Resources/Private/Language/locallang_db.xlf:tx_marianhub_domain_model_committee',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'title ASC',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'title,description,lead,members',
        'iconfile' => 'EXT:marian_hub/Resources/Public/Icons/committee.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'title' => [
            'label' => 'Komitee',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'description' => [
            'label' => 'Aufgabe des Komitees',
            'config' => [
                'type' => 'text',
                'rows' => 4,
                'cols' => 40,
            ],
        ],
        'lead' => [
            'label' => 'Verantwortlich',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'color' => [
            'label' => 'Farbe',
            'config' => [
                'type' => 'color',
                'size' => 10,
                'default' => '#6366f1',
            ],
        ],
        'members' => [
            'label' => 'Mitglieder (eine Person pro Zeile)',
            'config' => [
                'type' => 'text',
                'rows' => 6,
                'cols' => 30,
            ],
        ],
        'tasks' => [
            'label' => 'Aufgaben',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_marianhub_domain_model_abitask',
                'foreign_field' => 'committee',
                'appearance' => [
                    'collapseAll' => true,
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
            'showitem' => 'title, description, lead, color, members,
                --div--;Aufgaben, tasks,
                --div--;Zugriff, hidden',
        ],
    ],
];
