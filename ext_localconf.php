<?php

declare(strict_types=1);

use Praetorius\ViteAssetCollector\Configuration\VitePlaceholderProcessor;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['viteassetcollector_manifest']
    ??= [];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['viteassetcollector_manifest']['backend']
    ??= TransientMemoryBackend::class;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['yamlLoader']['placeholderProcessors'][VitePlaceholderProcessor::class] = [];
