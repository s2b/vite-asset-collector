<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Vite AssetCollector Vite Context Event Listener',
    'description' => 'Captures BuildViteContextEvent for functional tests',
    'category' => 'fe',
    'author' => 'Simon Praetorius',
    'author_email' => 'simon@praetorius.me',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'vite_asset_collector' => '',
        ],
    ],
];
