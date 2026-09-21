<?php

declare(strict_types=1);

use Marian\Shop\Domain\Model\Address;
use Marian\Shop\Domain\Model\Customer;
use Marian\Shop\Domain\Model\Order;
use Marian\Shop\Domain\Model\OrderItem;
use Marian\Shop\Domain\Model\PaymentMethod;
use Marian\Shop\Domain\Model\Product;
use Marian\Shop\Domain\Model\ProductVariant;
use Marian\Shop\Domain\Model\ShippingMethod;

/**
 * Aus dem Namespace "Marian\Shop" würde Extbase tx_shop_domain_model_* ableiten.
 * Der Extension-Key ist marian_shop, und so heißen auch die Tabellen.
 */
return [
    Product::class => [
        'tableName' => 'tx_marianshop_domain_model_product',
    ],
    ProductVariant::class => [
        'tableName' => 'tx_marianshop_domain_model_productvariant',
    ],
    Order::class => [
        'tableName' => 'tx_marianshop_domain_model_order',
    ],
    OrderItem::class => [
        'tableName' => 'tx_marianshop_domain_model_orderitem',
    ],
    Address::class => [
        'tableName' => 'tx_marianshop_domain_model_address',
    ],
    ShippingMethod::class => [
        'tableName' => 'tx_marianshop_domain_model_shippingmethod',
    ],
    PaymentMethod::class => [
        'tableName' => 'tx_marianshop_domain_model_paymentmethod',
    ],
    Customer::class => [
        'tableName' => 'fe_users',
        'properties' => [
            'phone' => ['fieldName' => 'tx_marianshop_phone'],
            'addresses' => ['fieldName' => 'tx_marianshop_addresses'],
            'confirmationToken' => ['fieldName' => 'tx_marianshop_token'],
            'confirmationTokenExpires' => ['fieldName' => 'tx_marianshop_token_expires'],
        ],
    ],
];
