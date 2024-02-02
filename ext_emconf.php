<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Vite AssetCollector',
    'description' => 'Bundle your TYPO3 frontend assets with Vite',
    'category' => 'fe',
    'author' => 'Simon Praetorius',
    'author_email' => 'simon@praetorius.me',
    'state' => 'stable',
    'version' => '1.9.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-13.9.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Praetorius\\ViteAssetCollector\\' => 'Classes/',
        ],
    ],
];
