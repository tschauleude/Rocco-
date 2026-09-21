<?php

declare(strict_types=1);

use Marian\Shop\Middleware\StripeWebhookMiddleware;

return [
    'frontend' => [
        'marian/shop/stripe-webhook' => [
            'target' => StripeWebhookMiddleware::class,
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
            ],
        ],
    ],
];
