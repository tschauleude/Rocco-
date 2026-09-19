<?php

declare(strict_types=1);

use Marian\Hub\Controller\AbiController;
use Marian\Hub\Controller\ProjectController;
use Marian\Hub\Controller\SensorController;
use Marian\Hub\Controller\WikiController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die();

// Abi-Organisation
ExtensionUtility::configurePlugin(
    'MarianHub',
    'Abi',
    [
        AbiController::class => 'board, countdown, committees',
    ],
    [],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

// Wiki und journalistische Arbeit
ExtensionUtility::configurePlugin(
    'MarianHub',
    'Wiki',
    [
        WikiController::class => 'list, index, search, show',
    ],
    [
        // Die Suche hängt an einer freien Eingabe und darf nicht im Seiten-Cache landen.
        WikiController::class => 'search',
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

// Projekte
ExtensionUtility::configurePlugin(
    'MarianHub',
    'Projects',
    [
        ProjectController::class => 'list, show',
    ],
    [],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

// Sensorik
ExtensionUtility::configurePlugin(
    'MarianHub',
    'Sensors',
    [
        SensorController::class => 'dashboard, show',
    ],
    [
        // Messwerte sollen live sein, nicht aus dem Cache von vorgestern.
        SensorController::class => 'dashboard, show',
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);
