<?php

declare(strict_types=1);

use Marian\Hub\Middleware\SensorIngestMiddleware;

return [
    'frontend' => [
        'marian/hub/sensor-ingest' => [
            'target' => SensorIngestMiddleware::class,
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
            ],
        ],
    ],
];
