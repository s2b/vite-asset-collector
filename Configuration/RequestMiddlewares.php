<?php

use Praetorius\ViteAssetCollector\Middleware\ViteContextMiddleware;

return [
    'frontend' => [
        'praetorius/vite-asset-collector/vite-context' => [
            'target' => ViteContextMiddleware::class,
            'after' => [
                'typo3/cms-frontend/csp-headers',
            ],
        ],
    ],
    'backend' => [
        'praetorius/vite-asset-collector/vite-context' => [
            'target' => ViteContextMiddleware::class,
            'after' => [
                'typo3/cms-backend/csp-headers',
            ],
        ],
    ],
];
