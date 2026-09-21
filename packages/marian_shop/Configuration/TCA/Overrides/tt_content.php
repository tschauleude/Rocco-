<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die();

$GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemGroups']['marianshop'] = 'Shop';

$plugins = [
    ['Products', 'Shop: Artikel', 'Artikelliste und Artikeldetails.', 'marianshop-plugin-products'],
    ['Cart', 'Shop: Warenkorb', 'Der Warenkorb mit Mengenänderung und Zwischensumme.', 'marianshop-plugin-cart'],
    ['Checkout', 'Shop: Kasse', 'Anschrift, Versand, Zahlung und Bestellabschluss.', 'marianshop-plugin-checkout'],
    ['Account', 'Shop: Kundenkonto', 'Anmeldung, Registrierung, Bestellungen und Adressbuch.', 'marianshop-plugin-account'],
];

foreach ($plugins as [$pluginName, $title, $description, $icon]) {
    ExtensionUtility::registerPlugin(
        'MarianShop',
        $pluginName,
        $title,
        $icon,
        'marianshop',
        $description
    );
}

$flexForms = [
    'marianshop_products' => 'Products',
    'marianshop_cart' => 'Cart',
    'marianshop_checkout' => 'Checkout',
    'marianshop_account' => 'Account',
];

foreach ($flexForms as $cType => $file) {
    $GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds']['*,' . $cType]
        = 'FILE:EXT:marian_shop/Configuration/FlexForms/' . $file . '.xml';

    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        '--div--;Einstellungen, pi_flexform',
        $cType,
        'after:palette:headers'
    );
}
