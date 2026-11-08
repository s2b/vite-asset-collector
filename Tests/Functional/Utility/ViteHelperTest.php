<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\Utility;

use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Asset\Asset;
use Praetorius\ViteAssetCollector\Asset\AssetFile;
use Praetorius\ViteAssetCollector\Context\ViteContext;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Tests\Functional\AbstractViteFunctionalTestCase;
use Praetorius\ViteAssetCollector\Utility\ViteHelper;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Page\AssetCollector;

final class ViteHelperTest extends AbstractViteFunctionalTestCase
{
    private const ValidManifest = 'fileadmin/Fixtures/ValidManifest/.vite/manifest.json';

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'vite_asset_collector' => [
                'useDevServer' => '0',
                'devServerUri' => 'https://localhost:5173',
                'defaultManifest' => 'fileadmin/Fixtures/DefaultManifest/.vite/manifest.json',
            ],
        ],
    ];

    #[Test]
    public function renderAssetUsesManifestWithoutDevServer(): void
    {
        $this->get(ViteHelper::class)->renderAsset(
            Asset::create('Main.js'),
            $this->createRequestWithViteContext(false),
            self::ValidManifest,
        );

        $javaScripts = $this->get(AssetCollector::class)->getJavaScripts();
        self::assertSame(['vite:fileadmin/Fixtures/ValidManifest/assets/Main-4483b920.js'], array_keys($javaScripts));
        self::assertStringEndsWith('fileadmin/Fixtures/ValidManifest/assets/Main-4483b920.js', $javaScripts['vite:fileadmin/Fixtures/ValidManifest/assets/Main-4483b920.js']['source']);
        self::assertSame(
            ['vite:fileadmin/Fixtures/ValidManifest/assets/Main-973bb662.css:' . md5(serialize([]))],
            array_keys($this->get(AssetCollector::class)->getStyleSheets()),
        );
    }

    #[Test]
    public function renderAssetUsesDefaultManifestIfNoneIsSpecified(): void
    {
        $this->get(ViteHelper::class)->renderAsset(
            Asset::create('Default.js'),
            $this->createRequestWithViteContext(false),
        );

        $javaScripts = $this->get(AssetCollector::class)->getJavaScripts();
        self::assertSame(['vite:fileadmin/Fixtures/DefaultManifest/assets/Default-4483b920.js'], array_keys($javaScripts));
        self::assertStringEndsWith('fileadmin/Fixtures/DefaultManifest/assets/Default-4483b920.js', $javaScripts['vite:fileadmin/Fixtures/DefaultManifest/assets/Default-4483b920.js']['source']);
    }

    #[Test]
    public function renderAssetUsesDevServer(): void
    {
        $this->get(ViteHelper::class)->renderAsset(
            Asset::create('path/to/Main.js'),
            $this->createRequestWithViteContext(true, 'https://vite.example.com:1234'),
            self::ValidManifest,
        );

        $javaScripts = $this->get(AssetCollector::class)->getJavaScripts();
        self::assertSame(['vite', 'vite:path/to/Main.js'], array_keys($javaScripts));
        self::assertSame('https://vite.example.com:1234/@vite/client', $javaScripts['vite']['source']);
        self::assertSame('https://vite.example.com:1234/path/to/Main.js', $javaScripts['vite:path/to/Main.js']['source']);
        self::assertSame([], $this->get(AssetCollector::class)->getStyleSheets());
    }

    #[Test]
    public function renderAssetWithDevServerDoesNotRequireManifest(): void
    {
        $this->get(ViteHelper::class)->renderAsset(
            Asset::create('path/to/Main.js'),
            $this->createRequestWithViteContext(true),
            'fileadmin/Fixtures/NonExisting/.vite/manifest.json',
        );

        self::assertArrayHasKey('vite:path/to/Main.js', $this->get(AssetCollector::class)->getJavaScripts());
    }

    #[Test]
    public function renderAssetFallsBackToManifestWithoutViteContext(): void
    {
        $this->get(ViteHelper::class)->renderAsset(
            Asset::create('Main.js'),
            $this->createRequest(),
            self::ValidManifest,
        );

        self::assertSame(['vite:fileadmin/Fixtures/ValidManifest/assets/Main-4483b920.js'], array_keys($this->get(AssetCollector::class)->getJavaScripts()));
    }

    #[Test]
    public function renderAssetThrowsExceptionForInvalidEntry(): void
    {
        $this->expectException(ViteException::class);
        $this->expectExceptionCode(1683200524);
        $this->get(ViteHelper::class)->renderAsset(
            Asset::create('NonExisting.js'),
            $this->createRequestWithViteContext(false),
            self::ValidManifest,
        );
    }

    #[Test]
    public function generateUriUsesManifestWithoutDevServer(): void
    {
        $uri = $this->get(ViteHelper::class)->generateUri(
            AssetFile::create('Main.css'),
            $this->createRequestWithViteContext(false),
            self::ValidManifest,
        );
        self::assertSame('fileadmin/Fixtures/ValidManifest/assets/Main-973bb662.css', (string)$uri);
    }

    #[Test]
    public function generateUriUsesDefaultManifestIfNoneIsSpecified(): void
    {
        $uri = $this->get(ViteHelper::class)->generateUri(
            AssetFile::create('Default.css'),
            $this->createRequestWithViteContext(false),
        );
        self::assertSame('fileadmin/Fixtures/DefaultManifest/assets/Default-973bb662.css', (string)$uri);
    }

    #[Test]
    public function generateUriUsesDevServer(): void
    {
        $uri = $this->get(ViteHelper::class)->generateUri(
            AssetFile::create('path/to/image.jpg'),
            $this->createRequestWithViteContext(true, 'https://vite.example.com:1234'),
            self::ValidManifest,
        );
        self::assertSame('https://vite.example.com:1234/path/to/image.jpg', (string)$uri);
    }

    #[Test]
    public function generateUriFallsBackToManifestWithoutViteContext(): void
    {
        $uri = $this->get(ViteHelper::class)->generateUri(
            AssetFile::create('Main.css'),
            $this->createRequest(),
            self::ValidManifest,
        );
        self::assertSame('fileadmin/Fixtures/ValidManifest/assets/Main-973bb662.css', (string)$uri);
    }

    #[Test]
    public function generateUriThrowsExceptionForInvalidFile(): void
    {
        $this->expectException(ViteException::class);
        $this->expectExceptionCode(1690735353);
        $this->get(ViteHelper::class)->generateUri(
            AssetFile::create('NonExisting.css'),
            $this->createRequestWithViteContext(false),
            self::ValidManifest,
        );
    }

    private function createRequestWithViteContext(bool $useDevServer, string $devServerUri = 'https://localhost:5173'): ServerRequestInterface
    {
        return $this->createRequest()->withAttribute(
            'vite.context',
            new ViteContext(useDevServer: $useDevServer, devServer: new Uri($devServerUri)),
        );
    }
}
