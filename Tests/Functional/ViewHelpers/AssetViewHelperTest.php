<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Context\ViteContext;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Tests\Functional\AbstractViteFunctionalTestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\View\TemplateView;

final class AssetViewHelperTest extends AbstractViteFunctionalTestCase
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function renderDataProvider(): array
    {
        $manifestDir = 'fileadmin/Fixtures/';
        return [
            'basic' => [
                'template' => '<vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js" />',
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'styleSheets' => [
                    'vite:' . $manifestDir . 'ValidManifest/assets/Main-973bb662.css:' . md5(serialize([])) => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                ],
            ],
            'withoutCss' => [
                'template' => '<vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js"><vite:asset.css ignore="{true}" /></vite:asset>',
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
            ],
            'defaultManifest' => [
                'template' => '<vite:asset entry="Default.js" />',
                'javaScripts' => [
                    'vite:Default.js' => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'DefaultManifest/assets/Default-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'styleSheets' => [
                    'vite:' . $manifestDir . 'DefaultManifest/assets/Default-973bb662.css:' . md5(serialize([])) => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'DefaultManifest/assets/Default-973bb662.css',
                        'attributes' => [],
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'preload' => [
                    '<link rel="preload" href="fileadmin/Fixtures/DefaultManifest/assets/dummy-57f5650e.woff2" as="font" type="font/woff2" crossorigin />',
                ],
            ],
            'autoEntry' => [
                'template' => '<vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" />',
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'styleSheets' => [
                    'vite:' . $manifestDir . 'ValidManifest/assets/Main-973bb662.css:' . md5(serialize([])) => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                ],
            ],
            'withAttributes' => [
                'template' => '
                    <vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js">
                        <vite:asset.script async="{true}" additionalAttributes="{foo: \'bar\'}" />
                        <vite:asset.css media="print" additionalAttributes="{foo: \'bar\'}" />
                    </vite:asset>
                ',
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'foo' => 'bar'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'styleSheets' => [
                    'vite:' . $manifestDir . 'ValidManifest/assets/Main-973bb662.css:' . md5(serialize(['media' => 'print', 'foo' => 'bar'])) => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => ['media' => 'print', 'foo' => 'bar'],
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                ],
            ],
            'withInlineCssWithoutPriority' => [
                'template' => '
                    <vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js">
                        <vite:asset.css inline="{true}" media="print" priority="{false}" />
                    </vite:asset>
                ',
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'inlineStyleSheets' => [
                    'vite:' . $manifestDir . 'ValidManifest/assets/Main-973bb662.css:' . md5(serialize(['media' => 'print'])) => [
                        'source' => ".main{color:red;}\n",
                        'attributes' => ['media' => 'print'],
                        'options' => ['external' => true],
                    ],
                ],
            ],
            'withInlineCss' => [
                'template' => '
                    <vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js">
                        <vite:asset.css inline="{true}" media="print" />
                    </vite:asset>
                ',
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'inlineStyleSheets' => [
                    'vite:' . $manifestDir . 'ValidManifest/assets/Main-973bb662.css:' . md5(serialize(['media' => 'print'])) => [
                        'source' => ".main{color:red;}\n",
                        'attributes' => ['media' => 'print'],
                        'options' => ['external' => true, 'priority' => true],
                    ],
                ],
            ],
            'withPriority' => [
                'template' => '
                    <vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js">
                        <vite:asset.script priority="{true}" />
                    </vite:asset>
                ',
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'styleSheets' => [
                    'vite:' . $manifestDir . 'ValidManifest/assets/Main-973bb662.css:' . md5(serialize([])) => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                ],
            ],
            'withCsp' => [
                'template' => '<vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js" csp="1" />',
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, self::cspOptionName() => true],
                    ],
                ],
                'styleSheets' => [
                    'vite:' . $manifestDir . 'ValidManifest/assets/Main-973bb662.css:' . md5(serialize([])) => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['external' => true, 'priority' => true, self::cspOptionName() => true],
                    ],
                ],
            ],
            'withoutCsp' => [
                'template' => '<vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js" csp="0" />',
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true],
                    ],
                ],
                'styleSheets' => [
                    'vite:' . $manifestDir . 'ValidManifest/assets/Main-973bb662.css:' . md5(serialize([])) => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['external' => true, 'priority' => true],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed[]> $javaScripts
     * @param array<string, mixed[]> $styleSheets
     * @param array<string, mixed[]> $inlineStyleSheets
     * @param string[] $preload
     */
    #[Test]
    #[DataProvider('renderDataProvider')]
    public function render(
        string $template,
        array $javaScripts = [],
        array $styleSheets = [],
        array $inlineStyleSheets = [],
        array $preload = [],
    ): void {
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'useDevServer' => '0',
            'devServerUri' => 'https://localhost:5173',
            'defaultManifest' => 'fileadmin/Fixtures/DefaultManifest/.vite/manifest.json',
        ]);

        $pageRenderer = $this->get(PageRenderer::class);
        $assetCollector = $this->get(AssetCollector::class);

        $context = $this->createRenderingContext(false, new Uri('https://localhost:5173'));
        $context->getTemplatePaths()->setTemplateSource($template);
        (new TemplateView($context))->render();

        self::assertSame(
            $javaScripts,
            $assetCollector->getJavaScripts()
        );
        self::assertSame(
            $styleSheets,
            $assetCollector->getStyleSheets()
        );
        self::assertSame(
            $inlineStyleSheets,
            $assetCollector->getInlineStyleSheets()
        );
        $headerDataAccessor = (new \ReflectionClass(PageRenderer::class))->getProperty('headerData');
        self::assertSame($preload, $headerDataAccessor->getValue($pageRenderer));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function renderWithDeprecatedArgumentsDataProvider(): array
    {
        $manifestDir = 'fileadmin/Fixtures/';
        return [
            'withoutCss' => [
                'template' => '<vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js" addCss="0" />',
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'deprecationMessage' => 'The "addCss" argument of <vite:asset> is deprecated. Use <vite:asset.css> instead to configure CSS embedding.',
            ],
            'withAttributes' => [
                'template' => '<vite:asset
                    manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json"
                    entry="Main.js"
                    scriptTagAttributes="{async: 1}"
                    cssTagAttributes="{media: \'print\'}"
                />',
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'styleSheets' => [
                    'vite:' . $manifestDir . 'ValidManifest/assets/Main-973bb662.css:' . md5(serialize(['media' => 'print'])) => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => ['media' => 'print'],
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'deprecationMessage' => 'The "scriptTagAttributes" argument of <vite:asset> is deprecated. Use <vite:asset.script> instead to configure script embedding.',
            ],
            'withInlineCss' => [
                'template' => '<vite:asset
                    manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json"
                    entry="Main.js"
                    inlineCss="1"
                    cssTagAttributes="{media: \'print\'}"
                />',
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'inlineStyleSheets' => [
                    'vite:' . $manifestDir . 'ValidManifest/assets/Main-973bb662.css:' . md5(serialize(['media' => 'print'])) => [
                        'source' => ".main{color:red;}\n",
                        'attributes' => ['media' => 'print'],
                        'options' => ['external' => true],
                    ],
                ],
                'deprecationMessage' => 'The "inlineCss" argument of <vite:asset> is deprecated. Use <vite:asset.css> instead to configure CSS embedding.',
            ],
            'withInlineCssAndPriority' => [
                'template' => '<vite:asset
                    manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json"
                    entry="Main.js"
                    cssTagAttributes="{media: \'print\'}"
                    inlineCss="1"
                    priority="1"
                />',
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'inlineStyleSheets' => [
                    'vite:' . $manifestDir . 'ValidManifest/assets/Main-973bb662.css:' . md5(serialize(['media' => 'print'])) => [
                        'source' => ".main{color:red;}\n",
                        'attributes' => ['media' => 'print'],
                        'options' => ['external' => true, 'priority' => true],
                    ],
                ],
                'deprecationMessage' => 'The "cssTagAttributes" argument of <vite:asset> is deprecated. Use <vite:asset.css> instead to configure CSS embedding.',
            ],
            'withPriority' => [
                'template' => '<vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js" priority="1" />',
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'styleSheets' => [
                    'vite:' . $manifestDir . 'ValidManifest/assets/Main-973bb662.css:' . md5(serialize([])) => [
                        'source' => self::rawAssetUriPrefix() . $manifestDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'deprecationMessage' => 'The "priority" argument of <vite:asset> is deprecated. Use <vite:asset.script> and/or <vite:asset.css> instead to configure embedding.',
            ],
        ];
    }

    /**
     * @param non-empty-string $deprecationMessage
     * @param array<string, mixed[]> $javaScripts
     * @param array<string, mixed[]> $styleSheets
     * @param array<string, mixed[]> $inlineStyleSheets
     * @param string[] $preload
     */
    #[Test]
    #[DataProvider('renderWithDeprecatedArgumentsDataProvider')]
    #[IgnoreDeprecations]
    public function renderWithDeprecatedArguments(
        string $template,
        string $deprecationMessage,
        array $javaScripts = [],
        array $styleSheets = [],
        array $inlineStyleSheets = [],
        array $preload = [],
    ): void {
        self::expectUserDeprecationMessage($deprecationMessage);
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'useDevServer' => '0',
            'devServerUri' => 'https://localhost:5173',
            'defaultManifest' => 'fileadmin/Fixtures/DefaultManifest/.vite/manifest.json',
        ]);

        $pageRenderer = $this->get(PageRenderer::class);
        $assetCollector = $this->get(AssetCollector::class);

        $context = $this->createRenderingContext(false, new Uri('https://localhost:5173'));
        $context->getTemplatePaths()->setTemplateSource($template);
        (new TemplateView($context))->render();

        self::assertSame(
            $javaScripts,
            $assetCollector->getJavaScripts()
        );
        self::assertSame(
            $styleSheets,
            $assetCollector->getStyleSheets()
        );
        self::assertSame(
            $inlineStyleSheets,
            $assetCollector->getInlineStyleSheets()
        );
        $headerDataAccessor = (new \ReflectionClass(PageRenderer::class))->getProperty('headerData');
        self::assertSame($preload, $headerDataAccessor->getValue($pageRenderer));
    }

    #[Test]
    public function renderWithDevServer(): void
    {
        $assetCollector = $this->get(AssetCollector::class);

        $context = $this->createRenderingContext(true, new Uri('https://localhost:5173'));
        $context->getTemplatePaths()->setTemplateSource('<vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js" />');
        (new TemplateView($context))->render();

        self::assertEquals(
            [
                'vite' => [
                    'source' => 'https://localhost:5173/@vite/client',
                    'attributes' => ['type' => 'module'],
                    'options' => ['external' => true, 'priority' => true],
                ],
            ],
            $assetCollector->getJavaScripts(true)
        );

        self::assertEquals(
            [
                'vite:Main.js' => [
                    'source' => 'https://localhost:5173/Main.js',
                    'attributes' => ['type' => 'module'],
                    'options' => ['external' => true],
                ],
            ],
            $assetCollector->getJavaScripts(false)
        );
    }

    #[Test]
    public function renderCssEntrypointWithDevServer(): void
    {
        $assetCollector = $this->get(AssetCollector::class);

        $context = $this->createRenderingContext(true, new Uri('https://localhost:5173'));
        $context->getTemplatePaths()->setTemplateSource('
            <vite:asset manifest="fileadmin/Fixtures/OnlyCssManifest/.vite/manifest.json" entry="Main.scss">
                <vite:asset.css media="print" disabled="{true}" />
            </vite:asset>
        ');
        (new TemplateView($context))->render();

        self::assertEquals(
            [
                'vite' => [
                    'source' => 'https://localhost:5173/@vite/client',
                    'attributes' => ['type' => 'module'],
                    'options' => ['external' => true, 'priority' => true],
                ],
            ],
            $assetCollector->getJavaScripts(true)
        );
        self::assertEquals(
            [
                'vite:Main.scss' => [
                    'source' => 'https://localhost:5173/Main.scss',
                    'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                    'options' => ['external' => true, 'priority' => true],
                ],
            ],
            $assetCollector->getStyleSheets(true)
        );
    }

    #[Test]
    public function renderWithoutManifest(): void
    {
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'useDevServer' => '0',
            'devServerUri' => 'https://localhost:5173',
            'defaultManifest' => '',
        ]);

        $context = $this->createRenderingContext(false, new Uri('https://localhost:5173'));
        $context->getTemplatePaths()->setTemplateSource('<vite:asset entry="Default.js" />');

        $this->expectException(ViteException::class);
        $this->expectExceptionCode(1684528724);
        (new TemplateView($context))->render();
    }

    #[Test]
    #[IgnoreDeprecations]
    public function nonceTriggersDeprecation(): void
    {
        self::expectUserDeprecationMessage('The "useNonce" argument of <vite:asset> is deprecated. Use "csp" instead.');
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'useDevServer' => '0',
        ]);

        $assetCollector = $this->get(AssetCollector::class);

        $context = $this->createRenderingContext(false, new Uri('https://localhost:5173'));
        $context->getTemplatePaths()->setTemplateSource('<vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js" useNonce="1" />');
        (new TemplateView($context))->render();

        self::assertEquals(
            [
                'vite:Main.js' => [
                    'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ValidManifest/assets/Main-4483b920.js',
                    'attributes' => ['type' => 'module'],
                    'options' => ['external' => true, self::cspOptionName() => true],
                ],
            ],
            $assetCollector->getJavaScripts(false)
        );
        self::assertEquals(
            [
                'vite:fileadmin/Fixtures/ValidManifest/assets/Main-973bb662.css:' . md5(serialize([])) => [
                    'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ValidManifest/assets/Main-973bb662.css',
                    'attributes' => [],
                    'options' => ['external' => true, 'priority' => true, self::cspOptionName() => true],
                ],
            ],
            $assetCollector->getStyleSheets(true)
        );
    }

    protected function createRenderingContext(bool $useDevServer, UriInterface $devServerUri): RenderingContextInterface
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getViewHelperResolver()->addNamespace('vite', 'Praetorius\\ViteAssetCollector\\ViewHelpers');

        $request = $this->createRequest()
            ->withAttribute('vite.context', new ViteContext(useDevServer: $useDevServer, devServer: $devServerUri));
        $context->setAttribute(ServerRequestInterface::class, $request);

        return $context;
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
