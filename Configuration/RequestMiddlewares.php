<?php

return [
    'frontend' => [
        'praetorius/vite-asset-collector/vite-assets' => [
            'target' => 'Praetorius\ViteAssetCollector\Middleware\ViteAssetsMiddleware',
            'after' => [
                'typo3/cms-frontend/csp-headers',
            ],
        ],
    ],
    'backend' => [
        'praetorius/vite-asset-collector/vite-assets' => [
            'target' => 'Praetorius\ViteAssetCollector\Middleware\ViteAssetsMiddleware',
            'after' => [
                'typo3/cms-backend/csp-headers',
            ],
        ],
    ],
];
