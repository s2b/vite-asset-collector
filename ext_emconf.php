<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Vite AssetCollector',
    'description' => 'Use AssetCollector to embed frontend assets generated by vite',
    'category' => 'fe',
    'author' => 'Simon Praetorius',
    'author_email' => 'simon@praetorius.me',
    'state' => 'stable',
    'version' => '1.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Praetorius\\ViteAssetCollector\\' => 'Classes/',
        ],
    ],
];
