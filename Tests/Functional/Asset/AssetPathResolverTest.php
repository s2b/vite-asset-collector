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

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function resolveOutputPathDataProvider(): array
    {
        return [
            'from output file' => [
                'file' => OutputFile::create('assets/Default-4483b920.js'),
                'manifestFile' => 'typo3conf/ext/vite_asset_collector/Tests/Fixtures/DefaultManifest/.vite/manifest.json',
                'expected' => 'typo3conf/ext/vite_asset_collector/Tests/Fixtures/DefaultManifest/assets/Default-4483b920.js',
            ],
            'from asset file' => [
                'file' => AssetFile::create('Default.js'),
                'manifestFile' => 'typo3conf/ext/vite_asset_collector/Tests/Fixtures/DefaultManifest/.vite/manifest.json',
                'expected' => 'typo3conf/ext/vite_asset_collector/Tests/Fixtures/DefaultManifest/assets/Default-4483b920.js',
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

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function resolveOutputPathThrowsExceptionDataProvider(): array
    {
        return [
            'non-existent chunk' => [
                'file' => AssetFile::create('NonExistent.js'),
                'manifestFile' => 'typo3conf/ext/vite_asset_collector/Tests/Fixtures/DefaultManifest/.vite/manifest.json',
                'expectedException' => ViteException::class,
                'expectedExceptionCode' => 1690735353,
            ],
        ];
    }

    /**
     * @param class-string<\Throwable> $expectedException
     */
    #[Test]
    #[DataProvider('resolveOutputPathThrowsExceptionDataProvider')]
    public function resolveOutputPathThrowsException(AssetFile|OutputFile $file, string $manifestFile, string $expectedException, int $expectedExceptionCode): void
    {
        $this->expectException($expectedException);
        $this->expectExceptionCode($expectedExceptionCode);
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $manifest = $manifestFactory->createFromFilePath($manifestFile);
        /** @var AssetPathResolver */
        $subject = $this->get(AssetPathResolver::class);
        $subject->resolveOutputPath($file, $manifest);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function resolveSourcePathDataProvider(): array
    {
        return [
            'asset in fileadmin' => [
                'file' => AssetFile::create('fileadmin/Fixtures/FileadminAsset/main.js'),
                'expected' => 'fileadmin/Fixtures/FileadminAsset/main.js',
            ],
            'asset in extension' => [
                'file' => AssetFile::create('EXT:test_extension/Resources/Private/JavaScript/Main.js'),
                'expected' => 'typo3conf/ext/test_extension/Resources/Private/JavaScript/Main.js',
            ],
            'non-existent asset in fileadmin' => [
                'file' => AssetFile::create('fileadmin/Fixtures/FileadminAsset/NonExistent.js'),
                'expected' => 'fileadmin/Fixtures/FileadminAsset/NonExistent.js',
            ],
            'non-existent asset in extension' => [
                'file' => AssetFile::create('EXT:test_extension/Resources/Private/JavaScript/NonExistent.js'),
                'expected' => 'typo3conf/ext/test_extension/Resources/Private/JavaScript/NonExistent.js',
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

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function resolveSourcePathThrowsExceptionDataProvider(): array
    {
        return [
            'asset in non-existent extension' => [
                'file' => AssetFile::create('EXT:nonexistent/Resources/Private/JavaScript/NonExistent.js'),
                'expectedException' => UnknownPackagePathException::class,
                'expectedExceptionCode' => 1631630087,
            ],
        ];
    }

    /**
     * @param class-string<\Throwable> $expectedException
     */
    #[Test]
    #[DataProvider('resolveSourcePathThrowsExceptionDataProvider')]
    public function resolveSourcePathThrowsException(AssetFile $file, string $expectedException, int $expectedExceptionCode): void
    {
        $this->expectException($expectedException);
        $this->expectExceptionCode($expectedExceptionCode);
        /** @var AssetPathResolver */
        $subject = $this->get(AssetPathResolver::class);
        $subject->resolveSourcePath($file);
    }
}
