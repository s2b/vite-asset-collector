<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\ViewHelpers\Asset;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Context\ViteContext;
use Praetorius\ViteAssetCollector\Tests\Functional\AbstractViteFunctionalTestCase;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3Fluid\Fluid\Core\Parser\Exception as ParserException;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\View\TemplateView;

final class ImageViewHelperTest extends AbstractViteFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'useDevServer' => '0',
            'devServerUri' => 'https://localhost:5173',
            'defaultManifest' => 'fileadmin/Fixtures/ReferencedAssets/.vite/manifest.json',
        ]);
    }

    /**
     * @return array<string, array{arguments: string, expected: string[]}>
     */
    public static function preloadingDataProvider(): array
    {
        return [
            'default' => [
                'arguments' => '',
                'expected' => [
                    '<link rel="preload" href="fileadmin/Fixtures/ReferencedAssets/assets/Font-57f5650e.woff2" as="font" type="font/woff2" crossorigin />',
                ],
            ],
            'enabled' => [
                'arguments' => 'preload="{true}"',
                'expected' => [
                    '<link rel="preload" href="fileadmin/Fixtures/ReferencedAssets/assets/Font-57f5650e.woff2" as="font" type="font/woff2" crossorigin />',
                    '<link rel="preload" href="fileadmin/Fixtures/ReferencedAssets/assets/Image-57f5650e.svg" as="image" type="image/svg+xml" />',
                    '<link rel="preload" href="fileadmin/Fixtures/ReferencedAssets/assets/Image-57f5650e.png" as="image" type="image/png" />',
                ],
            ],
            'single format' => [
                'arguments' => 'preload="{true}" formats="svg"',
                'expected' => [
                    '<link rel="preload" href="fileadmin/Fixtures/ReferencedAssets/assets/Font-57f5650e.woff2" as="font" type="font/woff2" crossorigin />',
                    '<link rel="preload" href="fileadmin/Fixtures/ReferencedAssets/assets/Image-57f5650e.svg" as="image" type="image/svg+xml" />',
                ],
            ],
        ];
    }

    /**
     * @param string[] $expected
     */
    #[Test]
    #[DataProvider('preloadingDataProvider')]
    public function preloading(string $arguments, array $expected): void
    {
        $this->render('
            <vite:asset entry="Main.js">
                <vite:asset.image ' . $arguments . ' />
            </vite:asset>
        ');
        $headerDataAccessor = (new \ReflectionClass(PageRenderer::class))->getProperty('headerData');
        self::assertSame($expected, $headerDataAccessor->getValue($this->get(PageRenderer::class)));
    }

    #[Test]
    public function multipleUsageThrowsException(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionCode(1794142772);
        $this->render('
            <vite:asset entry="Main.js">
                <vite:asset.image />
                <vite:asset.image />
            </vite:asset>
        ');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidParentDataProvider(): array
    {
        return [
            'without parent' => ['<vite:asset.image />'],
            'nested in other ViewHelper' => ['
                <vite:asset entry="Main.js">
                    <f:if condition="{true}"><vite:asset.image /></f:if>
                </vite:asset>
            '],
        ];
    }

    #[Test]
    #[DataProvider('invalidParentDataProvider')]
    public function invalidParentThrowsException(string $template): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionCode(1794142771);
        $this->render($template);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function deprecatedArgumentOfParentThrowsException(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionCode(1794142773);
        $this->render('
            <vite:asset entry="Main.js" priority="{true}">
                <vite:asset.image />
            </vite:asset>
        ');
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
