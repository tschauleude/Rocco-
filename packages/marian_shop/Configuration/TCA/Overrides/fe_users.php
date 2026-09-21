<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

$shopColumns = [
    'tx_marianshop_phone' => [
        'label' => 'Telefon',
        'config' => [
            'type' => 'input',
            'size' => 25,
            'eval' => 'trim',
        ],
    ],
    'tx_marianshop_addresses' => [
        'label' => 'Adressbuch',
        'config' => [
            'type' => 'inline',
            'foreign_table' => 'tx_marianshop_domain_model_address',
            'foreign_field' => 'fe_user',
            'appearance' => [
                'collapseAll' => true,
                'showNewRecordLink' => true,
                'newRecordLinkAddTitle' => true,
            ],
        ],
    ],
    'tx_marianshop_token' => [
        'label' => 'Bestätigungs-Token',
        'description' => 'Wird beim Bestätigen der Registrierung automatisch geleert.',
        'config' => [
            'type' => 'input',
            'size' => 40,
            'readOnly' => true,
        ],
    ],
    'tx_marianshop_token_expires' => [
        'label' => 'Token gültig bis',
        'config' => [
            'type' => 'datetime',
            'readOnly' => true,
            'default' => 0,
        ],
    ],
];

ExtensionManagementUtility::addTCAcolumns('fe_users', $shopColumns);

ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    '--div--;Shop, tx_marianshop_phone, tx_marianshop_addresses, tx_marianshop_token, tx_marianshop_token_expires'
);
