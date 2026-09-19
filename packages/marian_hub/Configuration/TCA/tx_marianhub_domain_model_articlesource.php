<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:marian_hub/Resources/Private/Language/locallang_db.xlf:tx_marianhub_domain_model_articlesource',
        'label' => 'title',
        'label_alt' => 'url',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'hideTable' => true,
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'title,url,note',
        'iconfile' => 'EXT:marian_hub/Resources/Public/Icons/source.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'article' => [
            'label' => 'Artikel',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'title' => [
            'label' => 'Bezeichnung',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'url' => [
            'label' => 'Link',
            'config' => [
                'type' => 'link',
                'size' => 40,
                'allowedTypes' => ['page', 'url', 'file', 'record'],
            ],
        ],
        'kind' => [
            'label' => 'Art der Quelle',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Website', 'value' => 'web'],
                    ['label' => 'Interview', 'value' => 'interview'],
                    ['label' => 'Buch', 'value' => 'book'],
                    ['label' => 'Fachartikel', 'value' => 'paper'],
                    ['label' => 'Dokument', 'value' => 'document'],
                    ['label' => 'Eigene Recherche', 'value' => 'own'],
                ],
                'default' => 'web',
            ],
        ],
        'accessed_at' => [
            'label' => 'Abgerufen am',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'default' => 0,
            ],
        ],
        'note' => [
            'label' => 'Notiz',
            'config' => [
                'type' => 'text',
                'rows' => 3,
                'cols' => 40,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'title, url, kind, accessed_at, note, hidden',
        ],
    ],
];
