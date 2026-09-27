<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractViteFunctionalTestCase extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/vite_asset_collector',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/vite_asset_collector/Tests/Fixtures' => 'fileadmin/Fixtures/',
    ];

    protected bool $initializeDatabase = false;
}
