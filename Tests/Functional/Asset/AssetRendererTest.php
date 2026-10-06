<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\Asset;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Asset\Asset;
use Praetorius\ViteAssetCollector\Asset\AssetRenderer;
use Praetorius\ViteAssetCollector\Asset\Embedding\ContentSecurityMode;
use Praetorius\ViteAssetCollector\Asset\Embedding\CssEmbedding;
use Praetorius\ViteAssetCollector\Asset\Embedding\ScriptEmbedding;
use Praetorius\ViteAssetCollector\Asset\Embedding\ScriptLoading;
use Praetorius\ViteAssetCollector\Asset\Manifest\ManifestFactory;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Tests\Functional\AbstractViteFunctionalTestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Page\AssetCollector;

final class AssetRendererTest extends AbstractViteFunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/vite_asset_collector',
        'typo3conf/ext/vite_asset_collector/Tests/Fixtures/test_extension',
    ];

    protected array $pathsToLinkInTestInstance = [
        'typo3conf/ext/vite_asset_collector/Tests/Fixtures/test_extension' => 'typo3conf/ext/symlink_extension',
    ];

    public static function renderDevAssetDataProvider(): array
    {
        return [
            'withoutPriority' => [
                'asset' => Asset::create(
                    entry: 'path/to/Main.js',
                ),
                'devServerUri' => new Uri('https://localhost:5173'),
                'expectedDevJavaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, 'priority' => true],
                    ],
                    'vite:path/to/Main.js' => [
                        'source' => 'https://localhost:5173/path/to/Main.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true],
                    ],
                ],
            ],
            'withPriority' => [
                'asset' => Asset::create(
                    entry: 'path/to/Main.js',
                    scriptEmbedding: new ScriptEmbedding(
                        priority: true,
                    ),
                ),
                'devServerUri' => new Uri('https://localhost:5173'),
                'expectedDevJavaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, 'priority' => true],
                    ],
                    'vite:path/to/Main.js' => [
                        'source' => 'https://localhost:5173/path/to/Main.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, 'priority' => true],
                    ],
                ],
            ],
            'withAttributes' => [
                'asset' => Asset::create(
                    entry: 'path/to/Main.js',
                    scriptEmbedding: new ScriptEmbedding(
                        loading: ScriptLoading::Async,
                        additionalAttributes: ['otherAttribute' => 'otherValue'],
                    ),
                ),
                'devServerUri' => new Uri('https://localhost:5173'),
                'expectedDevJavaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, 'priority' => true],
                    ],
                    'vite:path/to/Main.js' => [
                        'source' => 'https://localhost:5173/path/to/Main.js',
                        'attributes' => ['async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => true],
                    ],
                ],
            ],
            'withExtPath' => [
                'asset' => Asset::create(
                    entry: 'EXT:test_extension/Resources/Private/JavaScript/Main.js',
                ),
                'devServerUri' => new Uri('https://localhost:5173'),
                'expectedDevJavaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, 'priority' => true],
                    ],
                    'vite:typo3conf/ext/test_extension/Resources/Private/JavaScript/Main.js' => [
                        'source' => 'https://localhost:5173/typo3conf/ext/test_extension/Resources/Private/JavaScript/Main.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true],
                    ],
                ],
            ],
            'withSymlinkedExtPath' => [
                'asset' => Asset::create(
                    entry: 'typo3conf/ext/symlink_extension/Resources/Private/JavaScript/Main.js',
                ),
                'devServerUri' => new Uri('https://localhost:5173'),
                'expectedDevJavaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, 'priority' => true],
                    ],
                    'vite:typo3conf/ext/symlink_extension/Resources/Private/JavaScript/Main.js' => [
                        'source' => 'https://localhost:5173/typo3conf/ext/symlink_extension/Resources/Private/JavaScript/Main.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true],
                    ],
                ],
            ],
            'withCssEntrypoint' => [
                'asset' => Asset::create(
                    entry: 'path/to/Main.css',
                    cssEmbedding: new CssEmbedding(
                        media: 'screen',
                        additionalAttributes: ['otherAttribute' => 'otherValue'],
                    ),
                ),
                'devServerUri' => new Uri('https://localhost:5173'),
                'expectedDevJavaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, 'priority' => true],
                    ],
                ],
                'expectedDevStyleSheets' => [
                    'vite:path/to/Main.css' => [
                        'source' => 'https://localhost:5173/path/to/Main.css',
                        'attributes' => ['media' => 'screen', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => true],
                    ],
                ],
            ],
            'withCssEntrypointAndPriority' => [
                'asset' => Asset::create(
                    entry: 'path/to/Main.css',
                    cssEmbedding: new CssEmbedding(
                        priority: true,
                        media: 'screen',
                        additionalAttributes: ['otherAttribute' => 'otherValue'],
                    ),
                ),
                'devServerUri' => new Uri('https://localhost:5173'),
                'expectedDevJavaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, 'priority' => true],
                    ],
                ],
                'expectedDevStyleSheets' => [
                    'vite:path/to/Main.css' => [
                        'source' => 'https://localhost:5173/path/to/Main.css',
                        'attributes' => ['media' => 'screen', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => true, 'priority' => true],
                    ],
                ],
            ],
            'withInlineCss' => [
                'asset' => Asset::create(
                    entry: 'path/to/Main.css',
                    cssEmbedding: new CssEmbedding(inline: true),
                ),
                'devServerUri' => new Uri('https://localhost:5173'),
                'expectedDevJavaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, 'priority' => true],
                    ],
                ],
                'expectedDevStyleSheets' => [
                    'vite:path/to/Main.css' => [
                        'source' => 'https://localhost:5173/path/to/Main.css',
                        'attributes' => [],
                        'options' => ['external' => true],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('renderDevAssetDataProvider')]
    public function renderDevAsset(
        Asset $asset,
        UriInterface $devServerUri,
        ?ServerRequestInterface $request = null,
        array $expectedDevJavaScripts = [],
        array $expectedDevStyleSheets = [],
    ): void {
        /** @var AssetCollector */
        $assetCollector = $this->get(AssetCollector::class);
        /** @var AssetRenderer */
        $assetRenderer = $this->get(AssetRenderer::class);
        $assetRenderer->renderDevAsset($asset, $devServerUri, $request ?? new ServerRequest());
        self::assertSame($expectedDevJavaScripts, $assetCollector->getJavaScripts(), 'javaScripts');
        self::assertSame($expectedDevStyleSheets, $assetCollector->getStyleSheets(), 'styleSheets');
    }

    public static function renderAssetDataProvider(): array
    {
        return [
            'withoutCss' => [
                'manifestFile' => 'fileadmin/Fixtures/ValidManifest/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Main.js',
                    cssEmbedding: new CssEmbedding(ignore: true),
                ),
                'expectedJavaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
            ],
            'withCss' => [
                'manifestFile' => 'fileadmin/Fixtures/ValidManifest/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Main.js',
                    cssEmbedding: new CssEmbedding(media: 'print', disabled: true),
                ),
                'expectedJavaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'expectedStyleSheets' => [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ValidManifest/assets/Main-973bb662.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
            ],
            'withAttributes' => [
                'manifestFile' => 'fileadmin/Fixtures/ValidManifest/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Main.js',
                    cssEmbedding: new CssEmbedding(media: 'print', disabled: true),
                    scriptEmbedding: new ScriptEmbedding(
                        loading: ScriptLoading::Async,
                        additionalAttributes: ['otherAttribute' => 'otherValue'],
                    ),
                ),
                'expectedJavaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'expectedStyleSheets' => [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ValidManifest/assets/Main-973bb662.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
            ],
            'onlyCss' => [
                'manifestFile' => 'fileadmin/Fixtures/OnlyCssManifest/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Main.scss',
                    cssEmbedding: new CssEmbedding(media: 'print', disabled: true),
                ),
                'expectedStyleSheets' => [
                    'vite:Main.scss' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/OnlyCssManifest/assets/Main-4483b920.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
            ],
            'onlyCssIgnored' => [
                'manifestFile' => 'fileadmin/Fixtures/OnlyCssManifest/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Main.scss',
                    cssEmbedding: new CssEmbedding(ignore: true),
                ),
                'expectedStyleSheets' => [],
            ],
            'withCssAndPriority' => [
                'manifestFile' => 'fileadmin/Fixtures/ValidManifest/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Main.js',
                    scriptEmbedding: new ScriptEmbedding(priority: true),
                    cssEmbedding: new CssEmbedding(priority: true),
                ),
                'expectedJavaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'expectedStyleSheets' => [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ValidManifest/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                ],
            ],
            'withExtPath' => [
                'manifestFile' => 'fileadmin/Fixtures/ExtPathManifest/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'EXT:test_extension/Resources/Private/JavaScript/Main.js',
                ),
                'expectedJavaScripts' => [
                    'vite:typo3conf/ext/test_extension/Resources/Private/JavaScript/Main.js' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ExtPathManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
            ],
            'withImportedJs' => [
                'manifestFile' => 'fileadmin/Fixtures/ImportJs/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Main.js',
                ),
                'expectedJavaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJs/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'expectedStyleSheets' => [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJs/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
            ],
            'withImportedJsAndCss' => [
                'manifestFile' => 'fileadmin/Fixtures/ImportJsAndCss/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Main.js',
                    cssEmbedding: new CssEmbedding(media: 'print', disabled: true),
                ),
                'expectedJavaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJsAndCss/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'expectedStyleSheets' => [
                    'vite:6a181085b68130ba16f066fdaaf2da09:assets/Shared-pjWofKK4.css' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJsAndCss/assets/Shared-pjWofKK4.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJsAndCss/assets/Main-973bb662.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
            ],
            'withInlineCss' => [
                'manifestFile' => 'fileadmin/Fixtures/ValidManifest/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Main.js',
                    cssEmbedding: new CssEmbedding(media: 'print', inline: true),
                ),
                'expectedJavaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'expectedInlineStyleSheets' => [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => ".main{color:red;}\n",
                        'attributes' => ['media' => 'print'],
                        'options' => ['external' => true],
                    ],
                ],
            ],
            'withInlineCssAndPriority' => [
                'manifestFile' => 'fileadmin/Fixtures/ValidManifest/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Main.js',
                    cssEmbedding: new CssEmbedding(media: 'print', inline: true, priority: true),
                ),
                'expectedJavaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'expectedInlineStyleSheets' => [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => ".main{color:red;}\n",
                        'attributes' => ['media' => 'print'],
                        'options' => ['external' => true, 'priority' => true],
                    ],
                ],
            ],
            'withCsp' => [
                'manifestFile' => 'fileadmin/Fixtures/ValidManifest/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Main.js',
                    csp: ContentSecurityMode::Enabled,
                ),
                'expectedJavaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, self::cspOptionName() => true],
                    ],
                ],
                'expectedStyleSheets' => [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ValidManifest/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['external' => true, self::cspOptionName() => true],
                    ],
                ],
            ],
            'withoutCsp' => [
                'manifestFile' => 'fileadmin/Fixtures/ValidManifest/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Main.js',
                    csp: ContentSecurityMode::Disabled,
                ),
                'expectedJavaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true],
                    ],
                ],
                'expectedStyleSheets' => [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ValidManifest/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['external' => true],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('renderAssetDataProvider')]
    public function renderAsset(
        string $manifestFile,
        Asset $asset,
        ?ServerRequestInterface $request = null,
        array $expectedJavaScripts = [],
        array $expectedStyleSheets = [],
        array $expectedInlineStyleSheets = [],
    ): void {
        /** @var AssetCollector */
        $assetCollector = $this->get(AssetCollector::class);
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $manifest = $manifestFactory->createFromFilePath($manifestFile);
        /** @var AssetRenderer */
        $assetRenderer = $this->get(AssetRenderer::class);
        $assetRenderer->renderAsset($asset, $manifest, $request ?? new ServerRequest());
        self::assertSame($expectedJavaScripts, $assetCollector->getJavaScripts(), 'javaScripts');
        self::assertSame($expectedStyleSheets, $assetCollector->getStyleSheets(), 'styleSheets');
        self::assertSame($expectedInlineStyleSheets, $assetCollector->getInlineStyleSheets(), 'inlineStyleSheets');
    }

    #[Test]
    public function renderAssetPreventDuplicates(): void
    {
        $manifestFile = 'fileadmin/Fixtures/ImportJsAndCss/.vite/manifest.json';
        /** @var AssetCollector */
        $assetCollector = $this->get(AssetCollector::class);
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $manifest = $manifestFactory->createFromFilePath($manifestFile);
        /** @var AssetRenderer */
        $assetRenderer = $this->get(AssetRenderer::class);
        $request = new ServerRequest();
        $assetRenderer->renderAsset(Asset::create(entry: 'Main.js'), $manifest, $request);
        $assetRenderer->renderAsset(Asset::create(entry: 'Alternative.js'), $manifest, $request);
        $assetRenderer->renderAsset(Asset::create(entry: 'Main.js'), $manifest, $request);
        $assetRenderer->renderAsset(Asset::create(entry: 'Alternative.js'), $manifest, $request);
        self::assertEquals(
            [
                'vite:Main.js' => [
                    'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJsAndCss/assets/Main-4483b920.js',
                    'attributes' => ['type' => 'module'],
                    'options' => ['external' => true, ...self::autoCspForNonInline()],
                ],
                'vite:Alternative.js' => [
                    'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJsAndCss/assets/Alternative-4483b920.js',
                    'attributes' => ['type' => 'module'],
                    'options' => ['external' => true, ...self::autoCspForNonInline()],
                ],
            ],
            $assetCollector->getJavaScripts(),
        );
        self::assertEquals(
            [
                'vite:4c3e6cf2811f4c91dfa15ba7d99e10a8:assets/Shared-pjWofKK4.css' => [
                    'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJsAndCss/assets/Shared-pjWofKK4.css',
                    'attributes' => [],
                    'options' => ['external' => true, ...self::autoCspForNonInline()],
                ],
                'vite:Main.js:assets/Main-973bb662.css' => [
                    'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJsAndCss/assets/Main-973bb662.css',
                    'attributes' => [],
                    'options' => ['external' => true, ...self::autoCspForNonInline()],
                ],
                'vite:Alternative.js:assets/Alternative-973bb662.css' => [
                    'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJsAndCss/assets/Alternative-973bb662.css',
                    'attributes' => [],
                    'options' => ['external' => true, ...self::autoCspForNonInline()],
                ],
            ],
            $assetCollector->getStyleSheets(),
        );
    }

    #[Test]
    public function renderAssetAddDuplicateCssWithDifferentSettings(): void
    {
        $manifestFile = 'fileadmin/Fixtures/ImportJsAndCss/.vite/manifest.json';
        /** @var AssetCollector */
        $assetCollector = $this->get(AssetCollector::class);
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $manifest = $manifestFactory->createFromFilePath($manifestFile);
        /** @var AssetRenderer */
        $assetRenderer = $this->get(AssetRenderer::class);
        $request = new ServerRequest();
        $assetRenderer->renderAsset(Asset::create(entry: 'Main.js', cssEmbedding: new CssEmbedding(media: 'print')), $manifest, $request);
        $assetRenderer->renderAsset(Asset::create(entry: 'Alternative.js'), $manifest, $request);
        self::assertEquals(
            [
                'vite:88713ee6f56256eb987323824e723146:assets/Shared-pjWofKK4.css' => [
                    'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJsAndCss/assets/Shared-pjWofKK4.css',
                    'attributes' => ['media' => 'print'],
                    'options' => ['external' => true, ...self::autoCspForNonInline()],
                ],
                'vite:Main.js:assets/Main-973bb662.css' => [
                    'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJsAndCss/assets/Main-973bb662.css',
                    'attributes' => ['media' => 'print'],
                    'options' => ['external' => true, ...self::autoCspForNonInline()],
                ],
                'vite:4c3e6cf2811f4c91dfa15ba7d99e10a8:assets/Shared-pjWofKK4.css' => [
                    'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJsAndCss/assets/Shared-pjWofKK4.css',
                    'attributes' => [],
                    'options' => ['external' => true, ...self::autoCspForNonInline()],
                ],
                'vite:Alternative.js:assets/Alternative-973bb662.css' => [
                    'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJsAndCss/assets/Alternative-973bb662.css',
                    'attributes' => [],
                    'options' => ['external' => true, ...self::autoCspForNonInline()],
                ],
            ],
            $assetCollector->getStyleSheets(),
        );
    }

    public static function addAssetsFromManifestFileErrorHandlingDataProvider(): array
    {
        return [
            'invalidJson' => [
                'manifestFile' => 'fileadmin/Fixtures/InvalidManifest/.vite/manifest.json',
                'asset' => Asset::create(entry: 'Main.js'),
                'exceptionCode' => 1683200523,
            ],
            'nonExistentFile' => [
                'manifestFile' => 'fileadmin/Fixtures/InvalidManifest/.vite/manifest123.json',
                'asset' => Asset::create(entry: 'Main.js'),
                'exceptionCode' => 1683200522,
            ],
            'invalidEntry' => [
                'manifestFile' => 'fileadmin/Fixtures/ValidManifest/.vite/manifest.json',
                'asset' => Asset::create(entry: 'Main.css'),
                'exceptionCode' => 1683200524,
            ],
            'nonExistentEntry' => [
                'manifestFile' => 'fileadmin/Fixtures/ValidManifest/.vite/manifest.json',
                'asset' => Asset::create(entry: 'NonExistentEntry.js'),
                'exceptionCode' => 1683200524,
            ],
        ];
    }

    #[Test]
    #[DataProvider('addAssetsFromManifestFileErrorHandlingDataProvider')]
    public function addAssetsFromManifestFileErrorHandling(
        string $manifestFile,
        Asset $asset,
        int $exceptionCode
    ): void {
        self::expectException(ViteException::class);
        self::expectExceptionCode($exceptionCode);
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $manifest = $manifestFactory->createFromFilePath($manifestFile);
        /** @var AssetRenderer */
        $assetRenderer = $this->get(AssetRenderer::class);
        $assetRenderer->renderAsset($asset, $manifest, new ServerRequest());
    }

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

    private static function rawAssetUriPrefix(): string
    {
        // TODO remove this when support for TYPO3 v13 is dropped
        return (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() > 13 ? 'URI:' : '';
    }
}
