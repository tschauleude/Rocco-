<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'marianshop-plugin-products' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:marian_shop/Resources/Public/Icons/plugin-products.svg',
    ],
    'marianshop-plugin-cart' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:marian_shop/Resources/Public/Icons/plugin-cart.svg',
    ],
    'marianshop-plugin-checkout' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:marian_shop/Resources/Public/Icons/plugin-checkout.svg',
    ],
    'marianshop-plugin-account' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:marian_shop/Resources/Public/Icons/plugin-account.svg',
    ],
];
