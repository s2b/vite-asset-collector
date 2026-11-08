<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\ViewHelpers\Asset;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Context\ViteContext;
use Praetorius\ViteAssetCollector\Tests\Functional\AbstractViteFunctionalTestCase;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3Fluid\Fluid\Core\Parser\Exception as ParserException;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\InvalidArgumentException;
use TYPO3Fluid\Fluid\View\TemplateView;

final class CssViewHelperTest extends AbstractViteFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'useDevServer' => '0',
            'devServerUri' => 'https://localhost:5173',
            'defaultManifest' => 'fileadmin/Fixtures/DefaultManifest/.vite/manifest.json',
        ]);
    }

    #[Test]
    public function disablePreloading(): void
    {
        $this->render('
            <vite:asset entry="Default.js">
                <vite:asset.css preloadFonts="{false}" preloadImages="{false}" />
            </vite:asset>
        ');
        $headerDataAccessor = (new \ReflectionClass(PageRenderer::class))->getProperty('headerData');
        self::assertSame([], $headerDataAccessor->getValue($this->get(PageRenderer::class)));
        self::assertCount(1, $this->get(AssetCollector::class)->getStyleSheets());
    }

    #[Test]
    public function disablePreloadingOfFontsOnly(): void
    {
        $this->render('
            <vite:asset entry="Default.js">
                <vite:asset.css preloadFonts="{false}" />
            </vite:asset>
        ');
        $headerDataAccessor = (new \ReflectionClass(PageRenderer::class))->getProperty('headerData');
        self::assertSame(
            ['<link rel="preload" href="fileadmin/Fixtures/DefaultManifest/assets/typo3-57f5650e.svg" as="image" type="image/svg+xml" />'],
            $headerDataAccessor->getValue($this->get(PageRenderer::class)),
        );
    }

    #[Test]
    public function disabledAttribute(): void
    {
        $this->render('
            <vite:asset entry="Default.js">
                <vite:asset.css disabled="{true}" priority="{false}" />
            </vite:asset>
        ');
        $styleSheets = $this->get(AssetCollector::class)->getStyleSheets(false);
        self::assertCount(1, $styleSheets);
        self::assertSame(['disabled' => 'disabled'], array_values($styleSheets)[0]['attributes']);
        self::assertSame([], $this->get(AssetCollector::class)->getStyleSheets(true));
    }

    #[Test]
    public function lastConfigurationWins(): void
    {
        $this->render('
            <vite:asset entry="Default.js">
                <vite:asset.css media="screen" />
                <vite:asset.css media="print" />
            </vite:asset>
        ');
        $styleSheets = $this->get(AssetCollector::class)->getStyleSheets();
        self::assertSame(['media' => 'print'], array_values($styleSheets)[0]['attributes']);
    }

    #[Test]
    public function configurationDoesNotLeakIntoSubsequentAssets(): void
    {
        $this->render('
            <vite:asset entry="Default.js">
                <vite:asset.css ignore="{true}" />
            </vite:asset>
            <vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js" />
        ');
        self::assertSame(
            ['vite:fileadmin/Fixtures/ValidManifest/assets/Main-973bb662.css:' . md5(serialize([]))],
            array_keys($this->get(AssetCollector::class)->getStyleSheets()),
        );
    }

    #[Test]
    public function inlineAndDisabledThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1793528982);
        $this->render('
            <vite:asset entry="Default.js">
                <vite:asset.css inline="{true}" disabled="{true}" />
            </vite:asset>
        ');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidParentDataProvider(): array
    {
        return [
            'without parent' => ['<vite:asset.css inline="{true}" />'],
            'nested in other ViewHelper' => ['
                <vite:asset entry="Default.js">
                    <f:if condition="{true}"><vite:asset.css inline="{true}" /></f:if>
                </vite:asset>
            '],
        ];
    }

    #[Test]
    #[DataProvider('invalidParentDataProvider')]
    public function invalidParentThrowsException(string $template): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionCode(1793529908);
        $this->render($template);
    }

    private function render(string $template): void
    {
        $context = $this->createRenderingContext();
        $context->getTemplatePaths()->setTemplateSource($template);
        (new TemplateView($context))->render();
    }

    private function createRenderingContext(): RenderingContextInterface
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getViewHelperResolver()->addNamespace('vite', 'Praetorius\\ViteAssetCollector\\ViewHelpers');
        $request = $this->createRequest()
            ->withAttribute('vite.context', new ViteContext(useDevServer: false, devServer: new Uri('https://localhost:5173')));
        $context->setAttribute(ServerRequestInterface::class, $request);
        return $context;
    }
}
