<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:marian_hub/Resources/Private/Language/locallang_db.xlf:tx_marianhub_domain_model_abitask',
        'label' => 'title',
        'label_alt' => 'assignee',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'title,description,assignee',
        'iconfile' => 'EXT:marian_hub/Resources/Public/Icons/task.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'title' => [
            'label' => 'Aufgabe',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'description' => [
            'label' => 'Details',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 40,
            ],
        ],
        'phase' => [
            'label' => 'Spalte',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Ideen', 'value' => 'idea'],
                    ['label' => 'Geplant', 'value' => 'planned'],
                    ['label' => 'Läuft', 'value' => 'doing'],
                    ['label' => 'Erledigt', 'value' => 'done'],
                ],
                'default' => 'idea',
            ],
        ],
        'priority' => [
            'label' => 'Priorität',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Niedrig', 'value' => 0],
                    ['label' => 'Normal', 'value' => 1],
                    ['label' => 'Hoch', 'value' => 2],
                    ['label' => 'Brennt', 'value' => 3],
                ],
                'default' => 1,
            ],
        ],
        'due_date' => [
            'label' => 'Fällig am',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'default' => 0,
            ],
        ],
        'assignee' => [
            'label' => 'Wer macht es',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'estimate' => [
            'label' => 'Aufwand (Stunden)',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'size' => 10,
                'range' => ['lower' => 0],
                'default' => 0,
            ],
        ],
        'budget' => [
            'label' => 'Budget (€)',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'size' => 10,
                'range' => ['lower' => 0],
                'default' => 0,
            ],
        ],
        'committee' => [
            'label' => 'Komitee',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_marianhub_domain_model_committee',
                'items' => [
                    ['label' => '– keins –', 'value' => 0],
                ],
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'title, description, phase, priority, due_date, assignee,
                --div--;Planung, estimate, budget, committee,
                --div--;Zugriff, hidden',
        ],
    ],
];
