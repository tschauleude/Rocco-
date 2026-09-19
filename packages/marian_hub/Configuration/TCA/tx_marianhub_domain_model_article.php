<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:marian_hub/Resources/Private/Language/locallang_db.xlf:tx_marianhub_domain_model_article',
        'label' => 'title',
        'label_alt' => 'author',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'published_at DESC, title ASC',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'title,subtitle,teaser,bodytext,author,slug',
        'iconfile' => 'EXT:marian_hub/Resources/Public/Icons/article.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'title' => [
            'label' => 'Titel',
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
            'label' => 'Anriss',
            'description' => 'Zwei, drei Sätze für Übersichten und Suchergebnisse.',
            'config' => [
                'type' => 'text',
                'rows' => 3,
                'cols' => 50,
                'max' => 600,
            ],
        ],
        'bodytext' => [
            'label' => 'Text',
            'description' => 'Mit [[artikel-slug]] oder [[artikel-slug|Linktext]] auf andere Wiki-Artikel verweisen.',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
                'rows' => 20,
                'cols' => 50,
            ],
        ],
        'status' => [
            'label' => 'Status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Entwurf', 'value' => 'draft'],
                    ['label' => 'Recherche', 'value' => 'research'],
                    ['label' => 'Gegenlesen', 'value' => 'review'],
                    ['label' => 'Veröffentlicht', 'value' => 'published'],
                ],
                'default' => 'draft',
            ],
        ],
        'author' => [
            'label' => 'Autor',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => 'Marian',
            ],
        ],
        'published_at' => [
            'label' => 'Veröffentlicht am',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
            ],
        ],
        'revision' => [
            'label' => 'Fassung',
            'config' => [
                'type' => 'number',
                'size' => 6,
                'range' => ['lower' => 1],
                'default' => 1,
            ],
        ],
        'change_note' => [
            'label' => 'Was hat sich geändert',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ],
        ],
        'categories' => [
            'label' => 'Themen',
            'config' => [
                'type' => 'category',
            ],
        ],
        'sources' => [
            'label' => 'Quellen',
            'description' => 'Ohne Beleg keine Behauptung.',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_marianhub_domain_model_articlesource',
                'foreign_field' => 'article',
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
        'cover_image' => [
            'label' => 'Aufmacherbild',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'maxitems' => 1,
            ],
        ],
        'related_project' => [
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
    ],
    'types' => [
        '0' => [
            'showitem' => 'title, subtitle, slug, teaser, bodytext,
                --div--;Redaktion, status, author, published_at, revision, change_note,
                --div--;Quellen, sources,
                --div--;Medien & Einordnung, cover_image, categories, related_project,
                --div--;Zugriff, hidden, starttime, endtime',
        ],
    ],
];
