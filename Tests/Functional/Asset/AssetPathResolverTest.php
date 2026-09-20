<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\Asset;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Asset\AssetFile;
use Praetorius\ViteAssetCollector\Asset\AssetPathResolver;
use Praetorius\ViteAssetCollector\Asset\Manifest\ManifestFactory;
use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Tests\Functional\AbstractViteFunctionalTestCase;
use TYPO3\CMS\Core\Package\Exception\UnknownPackagePathException;

final class AssetPathResolverTest extends AbstractViteFunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/vite_asset_collector',
        'typo3conf/ext/vite_asset_collector/Tests/Fixtures/test_extension',
    ];

    public static function resolveOutputPathDataProvider(): array
    {
        return [
            'from output file' => [
                OutputFile::create('assets/Default-4483b920.js'),
                'typo3conf/ext/vite_asset_collector/Tests/Fixtures/DefaultManifest/.vite/manifest.json',
                'typo3conf/ext/vite_asset_collector/Tests/Fixtures/DefaultManifest/assets/Default-4483b920.js',
            ],
            'from asset file' => [
                AssetFile::create('Default.js'),
                'typo3conf/ext/vite_asset_collector/Tests/Fixtures/DefaultManifest/.vite/manifest.json',
                'typo3conf/ext/vite_asset_collector/Tests/Fixtures/DefaultManifest/assets/Default-4483b920.js',
            ],
        ];
    }

    #[Test]
    #[DataProvider('resolveOutputPathDataProvider')]
    public function resolveOutputPath(AssetFile|OutputFile $file, string $manifestFile, string $expected): void
    {
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $manifest = $manifestFactory->createFromFilePath($manifestFile);
        /** @var AssetPathResolver */
        $subject = $this->get(AssetPathResolver::class);
        self::assertSame($expected, $subject->resolveOutputPath($file, $manifest));
        self::assertSame(self::getInstancePath() . '/' . $expected, $subject->resolveOutputPath($file, $manifest, true));
    }

    public static function resolveOutputPathThrowsExceptionDataProvider(): array
    {
        return [
            'non-existent chunk' => [
                AssetFile::create('NonExistent.js'),
                'typo3conf/ext/vite_asset_collector/Tests/Fixtures/DefaultManifest/.vite/manifest.json',
                ViteException::class,
                1690735353,
            ],
        ];
    }

    #[Test]
    #[DataProvider('resolveOutputPathThrowsExceptionDataProvider')]
    public function resolveOutputPathThrowsException(AssetFile|OutputFile $file, string $manifestFile, string $expectedException, int $expectedExceptionCode): void
    {
        self::expectException($expectedException);
        self::expectExceptionCode($expectedExceptionCode);
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $manifest = $manifestFactory->createFromFilePath($manifestFile);
        /** @var AssetPathResolver */
        $subject = $this->get(AssetPathResolver::class);
        $subject->resolveOutputPath($file, $manifest);
    }

    public static function resolveSourcePathDataProvider(): array
    {
        return [
            'asset in fileadmin' => [
                AssetFile::create('fileadmin/Fixtures/FileadminAsset/main.js'),
                'fileadmin/Fixtures/FileadminAsset/main.js',
            ],
            'asset in extension' => [
                AssetFile::create('EXT:test_extension/Resources/Private/JavaScript/Main.js'),
                'typo3conf/ext/test_extension/Resources/Private/JavaScript/Main.js',
            ],
            'non-existent asset in fileadmin' => [
                AssetFile::create('fileadmin/Fixtures/FileadminAsset/NonExistent.js'),
                'fileadmin/Fixtures/FileadminAsset/NonExistent.js',
            ],
            'non-existent asset in extension' => [
                AssetFile::create('EXT:test_extension/Resources/Private/JavaScript/NonExistent.js'),
                'typo3conf/ext/test_extension/Resources/Private/JavaScript/NonExistent.js',
            ],
        ];
    }

    #[Test]
    #[DataProvider('resolveSourcePathDataProvider')]
    public function resolveSourcePath(AssetFile $file, string $expected): void
    {
        /** @var AssetPathResolver */
        $subject = $this->get(AssetPathResolver::class);
        self::assertSame($expected, $subject->resolveSourcePath($file));
        self::assertSame(self::getInstancePath() . '/' . $expected, $subject->resolveSourcePath($file, true));
    }

    public static function resolveSourcePathThrowsExceptionDataProvider(): array
    {
        return [
            'asset in non-existent extension' => [
                AssetFile::create('EXT:nonexistent/Resources/Private/JavaScript/NonExistent.js'),
                UnknownPackagePathException::class,
                1631630087,
            ],
        ];
    }

    #[Test]
    #[DataProvider('resolveSourcePathThrowsExceptionDataProvider')]
    public function resolveSourcePathThrowsException(AssetFile $file, string $expectedException, int $expectedExceptionCode): void
    {
        self::expectException($expectedException);
        self::expectExceptionCode($expectedExceptionCode);
        /** @var AssetPathResolver */
        $subject = $this->get(AssetPathResolver::class);
        $subject->resolveSourcePath($file);
    }
}
