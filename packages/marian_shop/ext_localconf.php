<?php

declare(strict_types=1);

use Marian\Shop\Controller\AccountController;
use Marian\Shop\Controller\CartController;
use Marian\Shop\Controller\CheckoutController;
use Marian\Shop\Controller\ProductController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die();

// Katalog
ExtensionUtility::configurePlugin(
    'MarianShop',
    'Products',
    [ProductController::class => 'list, show'],
    [],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

// Warenkorb – nichts davon darf im Seiten-Cache landen.
ExtensionUtility::configurePlugin(
    'MarianShop',
    'Cart',
    [CartController::class => 'show, add, update, remove, clear'],
    [CartController::class => 'show, add, update, remove, clear'],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

// Kasse
ExtensionUtility::configurePlugin(
    'MarianShop',
    'Checkout',
    [CheckoutController::class => 'index, place, success, cancel'],
    [CheckoutController::class => 'index, place, success, cancel'],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

// Kundenkonto
ExtensionUtility::configurePlugin(
    'MarianShop',
    'Account',
    [
        AccountController::class => 'overview, orders, order, addresses, saveAddress, deleteAddress, '
            . 'profile, saveProfile, login, register, createAccount, registered, confirm',
    ],
    [
        AccountController::class => 'overview, orders, order, addresses, saveAddress, deleteAddress, '
            . 'profile, saveProfile, login, register, createAccount, registered, confirm',
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

// Vorlagen für die E-Mails des Shops
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][800]
    = 'EXT:marian_shop/Resources/Private/Templates/Email/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths'][800]
    = 'EXT:marian_shop/Resources/Private/Layouts/Email/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths'][800]
    = 'EXT:marian_shop/Resources/Private/Partials/Email/';

// Der Warenkorb hängt an der Sitzung: Seiten mit Shop-Plugins dürfen nicht
// für alle Besucher gleich ausgeliefert werden.
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_marianshop_account[token]';
