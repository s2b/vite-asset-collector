<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Unit\Asset;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Asset\AssetFile;
use Praetorius\ViteAssetCollector\Asset\AssetPathResolver;
use Praetorius\ViteAssetCollector\Asset\Manifest\Manifest;
use Praetorius\ViteAssetCollector\Asset\Manifest\ManifestChunk;
use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AssetPathResolverTest extends UnitTestCase
{
    protected bool $backupEnvironment = true;

    protected function setUp(): void
    {
        parent::setUp();
        // Simulate composer mode: extensions are installed in vendor/, outside of public/
        Environment::initialize(
            Environment::getContext(),
            Environment::isCli(),
            true,
            '/project',
            '/project/public',
            '/project/var',
            '/project/config',
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function resolveOutputPathDataProvider(): array
    {
        return [
            'extension outside of public folder' => [
                'outputDir' => 'EXT:sitepackage/Resources/Public/Vite/',
                'expected' => 'EXT:sitepackage/Resources/Public/Vite/assets/Main-4483b920.js',
                'expectedAbsolute' => '/project/vendor/vendor/sitepackage/Resources/Public/Vite/assets/Main-4483b920.js',
            ],
            'extension inside of public folder' => [
                'outputDir' => 'EXT:legacy_ext/Resources/Public/Vite/',
                'expected' => 'typo3conf/ext/legacy_ext/Resources/Public/Vite/assets/Main-4483b920.js',
                'expectedAbsolute' => '/project/public/typo3conf/ext/legacy_ext/Resources/Public/Vite/assets/Main-4483b920.js',
            ],
            'public folder' => [
                'outputDir' => '/project/public/_assets/vite/',
                'expected' => '_assets/vite/assets/Main-4483b920.js',
                'expectedAbsolute' => '/project/public/_assets/vite/assets/Main-4483b920.js',
            ],
            'outside of public folder' => [
                'outputDir' => '/somewhere/else/',
                'expected' => '/somewhere/else/assets/Main-4483b920.js',
                'expectedAbsolute' => '/somewhere/else/assets/Main-4483b920.js',
            ],
        ];
    }

    #[Test]
    #[DataProvider('resolveOutputPathDataProvider')]
    public function resolveOutputPath(string $outputDir, string $expected, string $expectedAbsolute): void
    {
        $manifest = new Manifest(
            chunks: [
                'Main.js' => new ManifestChunk(
                    identifier: 'Main.js',
                    name: 'Main',
                    src: AssetFile::create('Main.js'),
                    file: OutputFile::create('assets/Main-4483b920.js'),
                    isEntry: true,
                    isDynamicEntry: false,
                    assets: [],
                    css: [],
                    imports: [],
                    dynamicImports: [],
                ),
            ],
            file: OutputFile::create('.vite/manifest.json'),
            outputDir: $outputDir,
        );
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('resolvePackagePath')->willReturnCallback(fn(string $path): string => str_replace(
            ['EXT:sitepackage/', 'EXT:legacy_ext/'],
            ['/project/vendor/vendor/sitepackage/', '/project/public/typo3conf/ext/legacy_ext/'],
            $path,
        ));
        $subject = new AssetPathResolver($packageManager);
        self::assertSame($expected, $subject->resolveOutputPath(AssetFile::create('Main.js'), $manifest));
        self::assertSame($expected, $subject->resolveOutputPath(OutputFile::create('assets/Main-4483b920.js'), $manifest));
        self::assertSame($expectedAbsolute, $subject->resolveOutputPath(AssetFile::create('Main.js'), $manifest, true));
    }
}
