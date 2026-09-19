<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Marians Werkstatt',
    'description' => 'Abi-Organisation, Wiki/Journalismus, Sensorik und Projekte in einer Extension.',
    'category' => 'plugin',
    'author' => 'Marian',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
