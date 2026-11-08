<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional;

use Praetorius\ViteAssetCollector\Context\ViteContext;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

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

    protected function renderTemplate(string $template, bool $useDevServer = false): string
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $request = $this->createRequest()
            ->withAttribute('vite.context', new ViteContext(useDevServer: $useDevServer, devServer: new Uri('https://localhost:5173')));
        $context->setAttribute(ServerRequestInterface::class, $request);
        $context->getTemplatePaths()->setTemplateSource($template);
        return (string)(new TemplateView($context))->render();
    }

    /**
     * @return array<string, mixed>
     */
    protected static function autoCspForNonInline(): array
    {
        // TODO remove this when support for TYPO3 v13 is dropped
        return (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() > 13 ? [self::cspOptionName() => true] : [];
    }

    protected static function cspOptionName(): string
    {
        // TODO remove this when support for TYPO3 v13 is dropped
        return (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() > 13 ? 'csp' : 'useNonce';
    }

    protected static function rawAssetUriPrefix(): string
    {
        // TODO remove this when support for TYPO3 v13 is dropped
        return (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() > 13 ? 'URI:' : '';
    }
}
