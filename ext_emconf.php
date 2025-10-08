<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Vite AssetCollector',
    'description' => 'Bundle your TYPO3 frontend assets with Vite',
    'category' => 'fe',
    'author' => 'Simon Praetorius',
    'author_email' => 'simon@praetorius.me',
    'state' => 'stable',
    'version' => '2.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.0.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Praetorius\\ViteAssetCollector\\' => 'Classes/',
        ],
    ],
];
