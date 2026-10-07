<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\Asset;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Asset\AssetFile;
use Praetorius\ViteAssetCollector\Asset\AssetUriGenerator;
use Praetorius\ViteAssetCollector\Asset\Manifest\ManifestFactory;
use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;
use Praetorius\ViteAssetCollector\Tests\Functional\AbstractViteFunctionalTestCase;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\Uri;

final class AssetUriGeneratorTest extends AbstractViteFunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/vite_asset_collector',
        'typo3conf/ext/vite_asset_collector/Tests/Fixtures/test_extension',
    ];

    public static function generateDevUriDataProvider(): array
    {
        return [
            'vite client' => [
                'file' => AssetFile::create('@vite/client'),
                'devServerBase' => new Uri('https://localhost:5173'),
                'expected' => 'https://localhost:5173/@vite/client',
            ],
            'extension path' => [
                'file' => AssetFile::create('EXT:test_extension/Resources/Private/JavaScript/main.js'),
                'devServerBase' => new Uri('https://localhost:5173'),
                'expected' => 'https://localhost:5173/typo3conf/ext/test_extension/Resources/Private/JavaScript/main.js',
            ],
            'fileadmin path' => [
                'file' => AssetFile::create('fileadmin/Fixtures/FileadminAsset/main.js'),
                'devServerBase' => new Uri('https://localhost:5173'),
                'expected' => 'https://localhost:5173/fileadmin/Fixtures/FileadminAsset/main.js',
            ],
            'dev server with path' => [
                'file' => AssetFile::create('fileadmin/Fixtures/FileadminAsset/main.js'),
                'devServerBase' => new Uri('https://localhost:5173/prefix/'),
                'expected' => 'https://localhost:5173/prefix/fileadmin/Fixtures/FileadminAsset/main.js',
            ],
        ];
    }

    #[Test]
    #[DataProvider('generateDevUriDataProvider')]
    public function generateDevUri(AssetFile $file, UriInterface $devServerBase, string $expected): void
    {
        /** @var AssetUriGenerator */
        $subject = $this->get(AssetUriGenerator::class);
        self::assertEquals($expected, (string)$subject->generateDevUri($file, $devServerBase, $this->createRequest()));
    }

    public static function generateUriDataProvider(): array
    {
        return [
            'asset file' => [
                'file' => AssetFile::create('Default.js'),
                'manifestFile' => 'EXT:vite_asset_collector/Tests/Fixtures/DefaultManifest/.vite/manifest.json',
                'expected' => 'typo3conf/ext/vite_asset_collector/Tests/Fixtures/DefaultManifest/assets/Default-4483b920.js',
            ],
            'asset file in extension' => [
                'file' => AssetFile::create('EXT:test_extension/Resources/Private/JavaScript/Main.js'),
                'manifestFile' => 'EXT:vite_asset_collector/Tests/Fixtures/ExtPathManifest/.vite/manifest.json',
                'expected' => 'typo3conf/ext/vite_asset_collector/Tests/Fixtures/ExtPathManifest/assets/Main-4483b920.js',
            ],
            'output file' => [
                'file' => OutputFile::create('assets/Default-4483b920.js'),
                'manifestFile' => 'EXT:vite_asset_collector/Tests/Fixtures/DefaultManifest/.vite/manifest.json',
                'expected' => 'typo3conf/ext/vite_asset_collector/Tests/Fixtures/DefaultManifest/assets/Default-4483b920.js',
            ],
        ];
    }

    #[Test]
    #[DataProvider('generateUriDataProvider')]
    public function generateUri(AssetFile|OutputFile $file, string $manifestFile, string $expected): void
    {
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $manifest = $manifestFactory->createFromFilePath($manifestFile);
        /** @var AssetUriGenerator */
        $subject = $this->get(AssetUriGenerator::class);
        self::assertEquals($expected, (string)$subject->generateUri($file, $manifest, $this->createRequest()));
    }
}
