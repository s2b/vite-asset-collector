<?php

return [
    'frontend' => [
        'praetorius/vite-asset-collector/add-csp-nonce-meta-tag' => [
            'target' => 'Praetorius\ViteAssetCollector\Middleware\AddCspNonceMetaTag',
            'after' => [
                'typo3/cms-frontend/csp-headers',
            ],
        ],
    ],
];
