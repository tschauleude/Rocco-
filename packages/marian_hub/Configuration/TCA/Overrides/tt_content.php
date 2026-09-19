<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die();

// Eigene Gruppe im Auswahlfenster "Neues Inhaltselement"
$GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemGroups']['marianhub'] = 'Marians Werkstatt';

$plugins = [
    ['Abi', 'Abi-Board & Countdown', 'Kanban-Board der Komitees, Countdown und Terminleiste.', 'marianhub-plugin-abi'],
    ['Wiki', 'Wiki & Artikel', 'Artikelliste, Register, Suche und Einzelansicht mit Quellen.', 'marianhub-plugin-wiki'],
    ['Projects', 'Projekte', 'Projektübersicht und Projektdetails mit Logbuch.', 'marianhub-plugin-projects'],
    ['Sensors', 'Sensor-Dashboard', 'Live-Werte der Arduino-Messstellen samt Verlauf.', 'marianhub-plugin-sensors'],
];

foreach ($plugins as [$pluginName, $title, $description, $icon]) {
    ExtensionUtility::registerPlugin(
        'MarianHub',
        $pluginName,
        $title,
        $icon,
        'marianhub',
        $description
    );
}

$flexForms = [
    'marianhub_wiki' => 'Wiki',
    'marianhub_projects' => 'Projects',
    'marianhub_sensors' => 'Sensors',
    'marianhub_abi' => 'Abi',
];

foreach ($flexForms as $cType => $file) {
    $GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds']['*,' . $cType]
        = 'FILE:EXT:marian_hub/Configuration/FlexForms/' . $file . '.xml';

    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        '--div--;Einstellungen, pi_flexform',
        $cType,
        'after:palette:headers'
    );
}
