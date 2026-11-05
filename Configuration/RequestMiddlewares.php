<?php

use Praetorius\ViteAssetCollector\Middleware\ViteContextMiddleware;

// The vite context needs to be added to the request before the CSP headers
// middleware is reached, because the CSP policy is built with the request
// object that has been passed to the CSP middleware. Otherwise, the vite
// context is not available in MutateContentSecurityPolicy event.
return [
    'frontend' => [
        'praetorius/vite-asset-collector/vite-context' => [
            'target' => ViteContextMiddleware::class,
            // Pin middleware to a point where site and language are available,
            // which could be useful for custom BuildViteContextEvent listeners.
            // Ensure that the context is available during page resolution,
            // e. g. for error pages.
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/maintenance-mode',
                'typo3/cms-frontend/page-resolver',
                'typo3/cms-frontend/csp-headers',
            ],
        ],
    ],
    'backend' => [
        'praetorius/vite-asset-collector/vite-context' => [
            'target' => ViteContextMiddleware::class,
            // Pin middleware to a point where backend user is available,
            // which could be useful for custom BuildViteContextEvent listeners.
            'after' => [
                'typo3/cms-backend/authentication',
            ],
            'before' => [
                'typo3/cms-backend/csp-headers',
            ],
        ],
    ],
];
