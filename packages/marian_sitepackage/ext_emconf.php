<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Marians Werkstatt – Sitepackage',
    'description' => 'Seitenlayouts, Navigation und Gestaltung der Website.',
    'category' => 'templates',
    'author' => 'Marian',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
            'marian_hub' => '1.0.0-1.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
