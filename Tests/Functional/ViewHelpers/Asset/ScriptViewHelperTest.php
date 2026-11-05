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
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3Fluid\Fluid\Core\Parser\Exception as ParserException;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\InvalidArgumentException;
use TYPO3Fluid\Fluid\View\TemplateView;

final class ScriptViewHelperTest extends AbstractViteFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'useDevServer' => '0',
            'devServerUri' => 'https://localhost:5173',
            'defaultManifest' => 'fileadmin/Fixtures/ValidManifest/.vite/manifest.json',
        ]);
    }

    /**
     * @return array<string, array{string, array<string, string>}>
     */
    public static function scriptTypeAndLoadingDataProvider(): array
    {
        return [
            'module' => ['', ['type' => 'module']],
            'async module' => ['async="{true}"', ['type' => 'module', 'async' => 'async']],
            'async classic script' => ['module="{false}" async="{true}"', ['async' => 'async']],
            'deferred classic script' => ['module="{false}" defer="{true}"', ['defer' => 'defer']],
            'blocking classic script' => ['module="{false}"', []],
        ];
    }

    /**
     * @param array<string, string> $expectedAttributes
     */
    #[Test]
    #[DataProvider('scriptTypeAndLoadingDataProvider')]
    public function scriptTypeAndLoading(string $arguments, array $expectedAttributes): void
    {
        $this->render('
            <vite:asset entry="Main.js">
                <vite:asset.script ' . $arguments . ' />
            </vite:asset>
        ');
        self::assertSame($expectedAttributes, $this->get(AssetCollector::class)->getJavaScripts()['vite:Main.js']['attributes']);
    }

    #[Test]
    public function nomoduleWithClassicScript(): void
    {
        $this->render('
            <vite:asset entry="Main.js">
                <vite:asset.script module="{false}" defer="{true}" nomodule="{true}" />
            </vite:asset>
        ');
        self::assertSame(
            ['defer' => 'defer', 'nomodule' => 'nomodule'],
            $this->get(AssetCollector::class)->getJavaScripts()['vite:Main.js']['attributes'],
        );
    }

    #[Test]
    public function ignore(): void
    {
        $this->render('
            <vite:asset entry="Main.js">
                <vite:asset.script ignore="{true}" />
            </vite:asset>
        ');
        self::assertSame([], $this->get(AssetCollector::class)->getJavaScripts());
        self::assertCount(1, $this->get(AssetCollector::class)->getStyleSheets());
    }

    #[Test]
    public function configurationDoesNotLeakIntoSubsequentAssets(): void
    {
        $this->render('
            <vite:asset entry="Main.js">
                <vite:asset.script ignore="{true}" />
            </vite:asset>
            <vite:asset manifest="fileadmin/Fixtures/DefaultManifest/.vite/manifest.json" entry="Default.js" />
        ');
        self::assertSame(['vite:Default.js'], array_keys($this->get(AssetCollector::class)->getJavaScripts()));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidCombinationThrowsExceptionDataProvider(): array
    {
        return [
            'module and nomodule' => ['nomodule="{true}"'],
            'module and defer' => ['defer="{true}"'],
            'async and defer' => ['module="{false}" async="{true}" defer="{true}"'],
        ];
    }

    #[Test]
    #[DataProvider('invalidCombinationThrowsExceptionDataProvider')]
    public function invalidCombinationThrowsException(string $arguments): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1793528981);
        $this->render('
            <vite:asset entry="Main.js">
                <vite:asset.script ' . $arguments . ' />
            </vite:asset>
        ');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidParentDataProvider(): array
    {
        return [
            'without parent' => ['<vite:asset.script priority="{true}" />'],
            'nested in other ViewHelper' => ['
                <vite:asset entry="Main.js">
                    <f:if condition="{true}"><vite:asset.script priority="{true}" /></f:if>
                </vite:asset>
            '],
        ];
    }

    #[Test]
    #[DataProvider('invalidParentDataProvider')]
    public function invalidParentThrowsException(string $template): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionCode(1793529909);
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
