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
use Praetorius\ViteAssetCollector\Asset\Manifest\ManifestFactory;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Tests\Functional\AbstractViteFunctionalTestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;

final class AssetRendererTest extends AbstractViteFunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/vite_asset_collector',
        'typo3conf/ext/vite_asset_collector/Tests/Fixtures/test_extension',
    ];

    protected array $pathsToLinkInTestInstance = [
        'typo3conf/ext/vite_asset_collector/Tests/Fixtures/test_extension' => 'typo3conf/ext/symlink_extension',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
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
                        async: true,
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
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => true],
                    ],
                ],
            ],
            'classicScriptIsEmbeddedAsModule' => [
                'asset' => Asset::create(
                    entry: 'path/to/Main.js',
                    scriptEmbedding: new ScriptEmbedding(module: false, defer: true),
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
            'nomoduleIsSkipped' => [
                'asset' => Asset::create(
                    entry: 'path/to/Main.js',
                    scriptEmbedding: new ScriptEmbedding(module: false, nomodule: true),
                ),
                'devServerUri' => new Uri('https://localhost:5173'),
            ],
            'ignoredScriptIsEmbeddedForCss' => [
                'asset' => Asset::create(
                    entry: 'path/to/Main.js',
                    scriptEmbedding: new ScriptEmbedding(ignore: true),
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
            'ignoredScriptAndCssIsSkipped' => [
                'asset' => Asset::create(
                    entry: 'path/to/Main.js',
                    cssEmbedding: new CssEmbedding(ignore: true),
                    scriptEmbedding: new ScriptEmbedding(ignore: true),
                ),
                'devServerUri' => new Uri('https://localhost:5173'),
            ],
            'ignoredCssEntrypointIsSkipped' => [
                'asset' => Asset::create(
                    entry: 'path/to/Main.css',
                    cssEmbedding: new CssEmbedding(ignore: true),
                ),
                'devServerUri' => new Uri('https://localhost:5173'),
            ],
            'otherEntrypointTypeIsSkipped' => [
                'asset' => Asset::create(
                    entry: 'path/to/Image.svg',
                ),
                'devServerUri' => new Uri('https://localhost:5173'),
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
                        'options' => ['external' => true, 'priority' => true],
                    ],
                ],
            ],
            'withCssEntrypointWithoutPriority' => [
                'asset' => Asset::create(
                    entry: 'path/to/Main.css',
                    cssEmbedding: new CssEmbedding(
                        priority: false,
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
                        'options' => ['external' => true, 'priority' => true],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed[]> $expectedDevJavaScripts
     * @param array<string, mixed[]> $expectedDevStyleSheets
     */
    #[Test]
    #[DataProvider('renderDevAssetDataProvider')]
    public function renderDevAsset(
        Asset $asset,
        UriInterface $devServerUri,
        ?ServerRequestInterface $request = null,
        array $expectedDevJavaScripts = [],
        array $expectedDevStyleSheets = [],
    ): void {
        /** @var PageRenderer */
        $pageRenderer = $this->get(PageRenderer::class);
        /** @var AssetCollector */
        $assetCollector = $this->get(AssetCollector::class);
        /** @var AssetRenderer */
        $assetRenderer = $this->get(AssetRenderer::class);
        $assetRenderer->renderDevAsset($asset, $devServerUri, $this->createRequest($request));
        self::assertSame($expectedDevJavaScripts, $assetCollector->getJavaScripts(), 'javaScripts');
        self::assertSame($expectedDevStyleSheets, $assetCollector->getStyleSheets(), 'styleSheets');
        $headerDataAccessor = (new \ReflectionClass(PageRenderer::class))->getProperty('headerData');
        self::assertSame([], $headerDataAccessor->getValue($pageRenderer));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function renderBuildAssetDataProvider(): array
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
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                ],
            ],
            'withAttributes' => [
                'manifestFile' => 'fileadmin/Fixtures/ValidManifest/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Main.js',
                    cssEmbedding: new CssEmbedding(media: 'print', disabled: true),
                    scriptEmbedding: new ScriptEmbedding(
                        async: true,
                        additionalAttributes: ['otherAttribute' => 'otherValue'],
                    ),
                ),
                'expectedJavaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'expectedStyleSheets' => [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ValidManifest/assets/Main-973bb662.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
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
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
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
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'expectedPreloadTags' => [
                    '<link rel="modulepreload" href="fileadmin/Fixtures/ImportJs/assets/Shared-To-v4Zbq.js" />',
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
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJsAndCss/assets/Main-973bb662.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'expectedPreloadTags' => [
                    '<link rel="modulepreload" href="fileadmin/Fixtures/ImportJsAndCss/assets/Shared-To-v4Zbq.js" />',
                ],
            ],
            'withImportedJsAsClassicScript' => [
                'manifestFile' => 'fileadmin/Fixtures/ImportJs/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Main.js',
                    scriptEmbedding: new ScriptEmbedding(module: false, async: true),
                ),
                'expectedJavaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJs/assets/Main-4483b920.js',
                        'attributes' => ['async' => 'async'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'expectedStyleSheets' => [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJs/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'expectedPreloadTags' => [
                    '<link rel="preload" href="fileadmin/Fixtures/ImportJs/assets/Shared-To-v4Zbq.js" as="script" />',
                ],
            ],
            'withImportedJsAsAsyncModule' => [
                'manifestFile' => 'fileadmin/Fixtures/ImportJs/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Main.js',
                    scriptEmbedding: new ScriptEmbedding(async: true),
                ),
                'expectedJavaScripts' => [
                    'vite:Main.js' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJs/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'expectedStyleSheets' => [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJs/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'expectedPreloadTags' => [
                    '<link rel="modulepreload" href="fileadmin/Fixtures/ImportJs/assets/Shared-To-v4Zbq.js" />',
                ],
            ],
            'withImportedJsWithoutPreload' => [
                'manifestFile' => 'fileadmin/Fixtures/ImportJs/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Main.js',
                    scriptEmbedding: new ScriptEmbedding(preloadImports: false),
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
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
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
                        'options' => ['external' => true, 'priority' => true],
                    ],
                ],
            ],
            'withInlineCssWithoutPriority' => [
                'manifestFile' => 'fileadmin/Fixtures/ValidManifest/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Main.js',
                    cssEmbedding: new CssEmbedding(media: 'print', inline: true, priority: false),
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
            'withInlineCssAndPathRewrite' => [
                'manifestFile' => 'fileadmin/Fixtures/DefaultManifest/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Default.js',
                    cssEmbedding: new CssEmbedding(inline: true),
                ),
                'expectedJavaScripts' => [
                    'vite:Default.js' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/DefaultManifest/assets/Default-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['external' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'expectedInlineStyleSheets' => [
                    'vite:Default.js:assets/Default-973bb662.css' => [
                        'source' => "body{background:url('fileadmin/Fixtures/DefaultManifest/assets/./typo3-57f5650e.svg');}\n",
                        'attributes' => [],
                        'options' => ['external' => true, 'priority' => true],
                    ],
                ],
                'expectedPreloadTags' => [
                    '<link rel="preload" href="fileadmin/Fixtures/DefaultManifest/assets/typo3-57f5650e.svg" as="image" type="image/svg+xml" />',
                    '<link rel="preload" href="fileadmin/Fixtures/DefaultManifest/assets/dummy-57f5650e.woff2" as="font" type="font/woff2" crossorigin />',
                ],
            ],
            'withoutFontPreload' => [
                'manifestFile' => 'fileadmin/Fixtures/DefaultManifest/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Default.js',
                    scriptEmbedding: new ScriptEmbedding(ignore: true),
                    cssEmbedding: new CssEmbedding(preloadFonts: false),
                ),
                'expectedStyleSheets' => [
                    'vite:Default.js:assets/Default-973bb662.css' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/DefaultManifest/assets/Default-973bb662.css',
                        'attributes' => [],
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'expectedPreloadTags' => [
                    '<link rel="preload" href="fileadmin/Fixtures/DefaultManifest/assets/typo3-57f5650e.svg" as="image" type="image/svg+xml" />',
                ],
            ],
            'withoutImagePreload' => [
                'manifestFile' => 'fileadmin/Fixtures/DefaultManifest/.vite/manifest.json',
                'asset' => Asset::create(
                    entry: 'Default.js',
                    scriptEmbedding: new ScriptEmbedding(ignore: true),
                    cssEmbedding: new CssEmbedding(preloadImages: false),
                ),
                'expectedStyleSheets' => [
                    'vite:Default.js:assets/Default-973bb662.css' => [
                        'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/DefaultManifest/assets/Default-973bb662.css',
                        'attributes' => [],
                        'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                    ],
                ],
                'expectedPreloadTags' => [
                    '<link rel="preload" href="fileadmin/Fixtures/DefaultManifest/assets/dummy-57f5650e.woff2" as="font" type="font/woff2" crossorigin />',
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
                        'options' => ['external' => true, 'priority' => true, self::cspOptionName() => true],
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
                        'options' => ['external' => true, 'priority' => true],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed[]> $expectedJavaScripts
     * @param array<string, mixed[]> $expectedStyleSheets
     * @param array<string, mixed[]> $expectedInlineStyleSheets
     * @param string[] $expectedPreloadTags
     */
    #[Test]
    #[DataProvider('renderBuildAssetDataProvider')]
    public function renderBuildAsset(
        string $manifestFile,
        Asset $asset,
        ?ServerRequestInterface $request = null,
        array $expectedJavaScripts = [],
        array $expectedStyleSheets = [],
        array $expectedInlineStyleSheets = [],
        array $expectedPreloadTags = [],
    ): void {
        /** @var PageRenderer */
        $pageRenderer = $this->get(PageRenderer::class);
        /** @var AssetCollector */
        $assetCollector = $this->get(AssetCollector::class);
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $manifest = $manifestFactory->createFromFilePath($manifestFile);
        /** @var AssetRenderer */
        $assetRenderer = $this->get(AssetRenderer::class);
        $assetRenderer->renderBuildAsset($asset, $manifest, $this->createRequest($request));
        self::assertSame($expectedJavaScripts, $assetCollector->getJavaScripts(), 'javaScripts');
        self::assertSame($expectedStyleSheets, $assetCollector->getStyleSheets(), 'styleSheets');
        self::assertSame($expectedInlineStyleSheets, $assetCollector->getInlineStyleSheets(), 'inlineStyleSheets');
        $headerDataAccessor = (new \ReflectionClass(PageRenderer::class))->getProperty('headerData');
        self::assertSame($expectedPreloadTags, $headerDataAccessor->getValue($pageRenderer));
    }

    #[Test]
    public function renderBuildAssetPreventDuplicates(): void
    {
        $manifestFile = 'fileadmin/Fixtures/ImportJsAndCss/.vite/manifest.json';
        /** @var AssetCollector */
        $assetCollector = $this->get(AssetCollector::class);
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $manifest = $manifestFactory->createFromFilePath($manifestFile);
        /** @var AssetRenderer */
        $assetRenderer = $this->get(AssetRenderer::class);
        $request = $this->createRequest();
        $assetRenderer->renderBuildAsset(Asset::create(entry: 'Main.js'), $manifest, $request);
        $assetRenderer->renderBuildAsset(Asset::create(entry: 'Alternative.js'), $manifest, $request);
        $assetRenderer->renderBuildAsset(Asset::create(entry: 'Main.js'), $manifest, $request);
        $assetRenderer->renderBuildAsset(Asset::create(entry: 'Alternative.js'), $manifest, $request);
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
                    'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                ],
                'vite:Main.js:assets/Main-973bb662.css' => [
                    'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJsAndCss/assets/Main-973bb662.css',
                    'attributes' => [],
                    'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                ],
                'vite:Alternative.js:assets/Alternative-973bb662.css' => [
                    'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJsAndCss/assets/Alternative-973bb662.css',
                    'attributes' => [],
                    'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                ],
            ],
            $assetCollector->getStyleSheets(),
        );
    }

    #[Test]
    public function renderBuildAssetAddDuplicateCssWithDifferentSettings(): void
    {
        $manifestFile = 'fileadmin/Fixtures/ImportJsAndCss/.vite/manifest.json';
        /** @var AssetCollector */
        $assetCollector = $this->get(AssetCollector::class);
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $manifest = $manifestFactory->createFromFilePath($manifestFile);
        /** @var AssetRenderer */
        $assetRenderer = $this->get(AssetRenderer::class);
        $request = $this->createRequest();
        $assetRenderer->renderBuildAsset(Asset::create(entry: 'Main.js', cssEmbedding: new CssEmbedding(media: 'print')), $manifest, $request);
        $assetRenderer->renderBuildAsset(Asset::create(entry: 'Alternative.js'), $manifest, $request);
        self::assertEquals(
            [
                'vite:88713ee6f56256eb987323824e723146:assets/Shared-pjWofKK4.css' => [
                    'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJsAndCss/assets/Shared-pjWofKK4.css',
                    'attributes' => ['media' => 'print'],
                    'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                ],
                'vite:Main.js:assets/Main-973bb662.css' => [
                    'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJsAndCss/assets/Main-973bb662.css',
                    'attributes' => ['media' => 'print'],
                    'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                ],
                'vite:4c3e6cf2811f4c91dfa15ba7d99e10a8:assets/Shared-pjWofKK4.css' => [
                    'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJsAndCss/assets/Shared-pjWofKK4.css',
                    'attributes' => [],
                    'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                ],
                'vite:Alternative.js:assets/Alternative-973bb662.css' => [
                    'source' => self::rawAssetUriPrefix() . 'fileadmin/Fixtures/ImportJsAndCss/assets/Alternative-973bb662.css',
                    'attributes' => [],
                    'options' => ['external' => true, 'priority' => true, ...self::autoCspForNonInline()],
                ],
            ],
            $assetCollector->getStyleSheets(),
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
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
        $assetRenderer->renderBuildAsset($asset, $manifest, $this->createRequest());
    }

    /**
     * @return array<string, mixed>
     */
    private static function autoCspForNonInline(): array
    {
        // TODO remove this when support for TYPO3 v13 is dropped
        return (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() > 13 ? [self::cspOptionName() => true] : [];
    }

    private static function cspOptionName(): string
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
