<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\View\TemplateView;

final class ViewHelperAliasesTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/vite_asset_collector',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/vite_asset_collector/Tests/Fixtures' => 'fileadmin/Fixtures/',
    ];

    #[Test]
    public function resourceAliasViewHelperCanBeUsed(): void
    {
        $context = $this->createRenderingContext();
        $context->getTemplatePaths()->setTemplateSource(
            '<vac:resource.vite manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" file="Main.css" />'
        );
        self::assertEquals(
            'fileadmin/Fixtures/ValidManifest/assets/Main-973bb662.css',
            (new TemplateView($context))->render()
        );
    }

    #[Test]
    public function assetAliasViewHelperCanBeUsed(): void
    {
        $assetCollector = $this->get(AssetCollector::class);

        $context = $this->createRenderingContext();
        $context->getTemplatePaths()->setTemplateSource(
            '<vac:asset.vite manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js" addCss="0" />'
        );
        (new TemplateView($context))->render();

        $javaScripts = $assetCollector->getJavaScripts(false);
        self::assertTrue(
            isset($javaScripts['vite:Main.js'])
        );
    }

    protected function createRenderingContext(): RenderingContextInterface
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getViewHelperResolver()->addNamespace('vac', 'Praetorius\\ViteAssetCollector\\ViewHelpers');

        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('extbase', new ExtbaseRequestParameters());

        // TODO remove this when support for TYPO3 v12 is dropped
        if (method_exists($context, 'setRequest')) {
            @$context->setRequest($request);
        }

        $context->setAttribute(ServerRequestInterface::class, $request);

        return $context;
    }
}
