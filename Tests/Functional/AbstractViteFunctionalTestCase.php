<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
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

    protected function createRequest(?ServerRequestInterface $baseRequest = null): ServerRequestInterface
    {
        $request = $baseRequest ?? new ServerRequest();
        return $request
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request))
            ->withAttribute('extbase', new ExtbaseRequestParameters());
    }
}
