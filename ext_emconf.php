<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Vite AssetCollector',
    'description' => 'Bundle your TYPO3 frontend assets with Vite',
    'category' => 'fe',
    'author' => 'Simon Praetorius',
    'author_email' => 'simon@praetorius.me',
    'state' => 'stable',
    'version' => '1.14.1',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Praetorius\\ViteAssetCollector\\' => 'Classes/',
        ],
    ],
];
