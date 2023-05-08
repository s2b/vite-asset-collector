<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['viteassetcollector_manifest']
    ??= [];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['viteassetcollector_manifest']['backend']
    ??= TransientMemoryBackend::class;
