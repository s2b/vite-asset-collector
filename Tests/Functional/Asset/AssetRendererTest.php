<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\Asset;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Asset\Asset;
use Praetorius\ViteAssetCollector\Asset\AssetRenderer;
use Praetorius\ViteAssetCollector\Asset\Embedding\CssEmbedding;
use Praetorius\ViteAssetCollector\Asset\Embedding\ScriptEmbedding;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AssetRendererTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/vite_asset_collector',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/vite_asset_collector/Tests/Fixtures' => 'fileadmin/Fixtures/',
    ];

    public static function renderDevAssetDataProvider(): array
    {
        return [
            'withoutPriority' => [
                'asset' => Asset::create(entry: 'path/to/Main.js'),
                'priorityJavaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module'],
                        'options' => ['priority' => true, 'external' => true],
                    ],
                ],
                'javaScripts' => [
                    'vite:path/to/Main.js' => [
                        'source' => 'https://localhost:5173/path/to/Main.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => true],
                    ],
                ],
            ],
            'withPriority' => [
                'asset' => Asset::create(
                    entry: 'path/to/Main.js',
                    scriptEmbedding: new ScriptEmbedding(priority: true),
                ),
                'priorityJavaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module'],
                        'options' => ['priority' => true, 'external' => true],
                    ],
                    'vite:path/to/Main.js' => [
                        'source' => 'https://localhost:5173/path/to/Main.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['priority' => true, 'external' => true],
                    ],
                ],
            ],
            'withExtPath' => [
                'asset' => Asset::create(entry: 'EXT:test_extension/Resources/Private/JavaScript/Main.js'),
                'priorityJavaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module'],
                        'options' => ['priority' => true, 'external' => true],
                    ],
                ],
                'javaScripts' => [
                    'vite:Tests/Fixtures/test_extension/Resources/Private/JavaScript/Main.js' => [
                        'source' => 'https://localhost:5173/Tests/Fixtures/test_extension/Resources/Private/JavaScript/Main.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => true],
                    ],
                ],
            ],
            'withSymlinkedExtPath' => [
                'asset' => Asset::create(entry: 'EXT:symlink_extension/Resources/Private/JavaScript/Main.js'),
                'priorityJavaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module'],
                        'options' => ['priority' => true, 'external' => true],
                    ],
                ],
                'javaScripts' => [
                    'vite:Tests/Fixtures/symlink_extension/Resources/Private/JavaScript/Main.js' => [
                        'source' => 'https://localhost:5173/Tests/Fixtures/symlink_extension/Resources/Private/JavaScript/Main.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => true],
                    ],
                ],
            ],
            'withCssEntrypoint' => [
                'asset' => Asset::create(
                    entry: 'path/to/Main.css',
                    cssEmbedding: new CssEmbedding(priority: true, media: 'screen', additionalAttributes: ['otherAttribute' => 'otherValue']),
                ),
                'priorityJavaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module'],
                        'options' => ['priority' => true, 'external' => true],
                    ],
                ],
                'styleSheets' => [
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
                    cssEmbedding: new CssEmbedding(priority: true, media: 'screen', additionalAttributes: ['otherAttribute' => 'otherValue']),
                ),
                'priorityJavaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module'],
                        'options' => ['priority' => true, 'external' => true],
                    ],
                ],
                'priorityStyleSheets' => [
                    'vite:path/to/Main.css' => [
                        'source' => 'https://localhost:5173/path/to/Main.css',
                        'attributes' => ['media' => 'screen', 'otherAttribute' => 'otherValue'],
                        'options' => ['priority' => true, 'external' => true],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('renderDevAssetDataProvider')]
    public function renderDevAsset(
        Asset $asset,
        array $javaScripts = [],
        array $priorityJavaScripts = [],
        array $styleSheets = [],
        array $priorityStyleSheets = [],
    ): void {
        self::markTestSkipped('To be implemented');
        $request = new ServerRequest(new Uri('https://some.ddev.site/path/to/file'));
        $subject = $this->get(AssetRenderer::class);
        $subject->renderDevAsset($asset, new Uri('https://localhost:5173'), $request);
        $assetCollector = $this->get(AssetCollector::class);
        self::assertEquals(
            $javaScripts,
            $assetCollector->getJavaScripts(false)
        );
        self::assertEquals(
            $priorityJavaScripts,
            $assetCollector->getJavaScripts(true)
        );
        self::assertEquals(
            $styleSheets,
            $assetCollector->getStyleSheets(false)
        );
        self::assertEquals(
            $priorityStyleSheets,
            $assetCollector->getStyleSheets(true)
        );
    }

    // public function renderAsset(Asset $asset, ServerRequestInterface $request): void
    // {

    // }
}
