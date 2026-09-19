<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'marianhub-plugin-abi' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:marian_hub/Resources/Public/Icons/plugin-abi.svg',
    ],
    'marianhub-plugin-wiki' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:marian_hub/Resources/Public/Icons/plugin-wiki.svg',
    ],
    'marianhub-plugin-projects' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:marian_hub/Resources/Public/Icons/plugin-projects.svg',
    ],
    'marianhub-plugin-sensors' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:marian_hub/Resources/Public/Icons/plugin-sensors.svg',
    ],
];
