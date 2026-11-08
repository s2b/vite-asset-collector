<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\Asset\Manifest;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Asset\Manifest\ManifestFactory;
use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Tests\Functional\AbstractViteFunctionalTestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

final class ManifestTest extends AbstractViteFunctionalTestCase
{
    private const FixtureDir = __DIR__ . '/../../../Fixtures/';

    /**
     * @return array<string, array{identifier: string, expected: array<string, mixed>}>
     */
    public static function createFromFilePathParsesChunkDataProvider(): array
    {
        return [
            'all properties' => [
                'identifier' => 'Main.js',
                'expected' => [
                    'identifier' => 'Main.js',
                    'name' => 'Main',
                    'src' => 'Main.js',
                    'file' => 'assets/Main-4483b920.js',
                    'isEntry' => true,
                    'isDynamicEntry' => false,
                    'assets' => ['assets/typo3-57f5650e.svg', 'assets/dummy-57f5650e.woff2'],
                    'css' => ['assets/Main-973bb662.css'],
                    'imports' => ['_Shared-To-v4Zbq.js'],
                    'dynamicImports' => ['Lazy.js'],
                ],
            ],
            'dynamic entry' => [
                'identifier' => 'Lazy.js',
                'expected' => [
                    'identifier' => 'Lazy.js',
                    'name' => 'Lazy',
                    'src' => 'Lazy.js',
                    'file' => 'assets/Lazy-abcdef12.js',
                    'isEntry' => false,
                    'isDynamicEntry' => true,
                    'assets' => [],
                    'css' => [],
                    'imports' => [],
                    'dynamicImports' => [],
                ],
            ],
            'default values' => [
                'identifier' => '_Shared-To-v4Zbq.js',
                'expected' => [
                    'identifier' => '_Shared-To-v4Zbq.js',
                    'name' => null,
                    'src' => null,
                    'file' => 'assets/Shared-To-v4Zbq.js',
                    'isEntry' => false,
                    'isDynamicEntry' => false,
                    'assets' => [],
                    'css' => [],
                    'imports' => [],
                    'dynamicImports' => [],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $expected
     */
    #[Test]
    #[DataProvider('createFromFilePathParsesChunkDataProvider')]
    public function createFromFilePathParsesChunk(string $identifier, array $expected): void
    {
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $chunk = $manifestFactory->createFromFilePath(self::FixtureDir . 'ChunkProperties/.vite/manifest.json')->getChunk($identifier);
        self::assertNotNull($chunk);
        self::assertSame($expected, [
            'identifier' => $chunk->identifier,
            'name' => $chunk->name,
            'src' => $chunk->src?->locator,
            'file' => $chunk->file->locator,
            'isEntry' => $chunk->isEntry,
            'isDynamicEntry' => $chunk->isDynamicEntry,
            'assets' => array_map(fn(OutputFile $file): string => $file->locator, $chunk->assets),
            'css' => array_map(fn(OutputFile $file): string => $file->locator, $chunk->css),
            'imports' => $chunk->imports,
            'dynamicImports' => $chunk->dynamicImports,
        ]);
    }

    #[Test]
    public function getValidEntrypoints(): void
    {
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $subject = $manifestFactory->createFromFilePath(self::FixtureDir . 'MultipleEntries/.vite/manifest.json');
        self::assertSame(
            [
                'Main.js' => $subject->getChunk('Main.js'),
                'Alt.js' => $subject->getChunk('Alt.js'),
            ],
            $subject->getValidEntrypoints(),
        );
    }

    #[Test]
    public function getChunkReturnsNullForUndefinedChunk(): void
    {
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $subject = $manifestFactory->createFromFilePath(self::FixtureDir . 'MultipleEntries/.vite/manifest.json');
        self::assertNull($subject->getChunk('Undefined.js'));
    }

    /**
     * @return array<string, array{manifestFile: string, entrypoint: string, recursive: bool, expected: string[]}>
     */
    public static function getImportsForChunkDataProvider(): array
    {
        return [
            'direct import' => [
                'manifestFile' => self::FixtureDir . 'ImportJs/.vite/manifest.json',
                'entrypoint' => 'Main.js',
                'recursive' => false,
                'expected' => ['_Shared-To-v4Zbq.js'],
            ],
            'undefined chunk' => [
                'manifestFile' => self::FixtureDir . 'ImportJs/.vite/manifest.json',
                'entrypoint' => 'Undefined.js',
                'recursive' => false,
                'expected' => [],
            ],
            'nested import, not recursive' => [
                'manifestFile' => self::FixtureDir . 'ImportCssRecursive/.vite/manifest.json',
                'entrypoint' => 'Main.js',
                'recursive' => false,
                'expected' => ['_Shared-To-v4Zbq.js'],
            ],
            'nested import, recursive' => [
                'manifestFile' => self::FixtureDir . 'ImportCssRecursive/.vite/manifest.json',
                'entrypoint' => 'Main.js',
                'recursive' => true,
                'expected' => ['_Shared-To-v4Zbq.js', '_Nested-abcdef.js'],
            ],
            'self reference, not recursive' => [
                'manifestFile' => self::FixtureDir . 'ImportSelfReference/.vite/manifest.json',
                'entrypoint' => 'Main.js',
                'recursive' => false,
                'expected' => ['_Shared-To-v4Zbq.js', 'Main.js'],
            ],
            'self reference, recursive' => [
                'manifestFile' => self::FixtureDir . 'ImportSelfReference/.vite/manifest.json',
                'entrypoint' => 'Main.js',
                'recursive' => true,
                'expected' => ['_Shared-To-v4Zbq.js', '_Nested-abcdef.js', 'Main.js'],
            ],
        ];
    }

    /**
     * @param string[] $expected
     */
    #[Test]
    #[DataProvider('getImportsForChunkDataProvider')]
    public function getImportsForChunk(string $manifestFile, string $entrypoint, bool $recursive, array $expected): void
    {
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $subject = $manifestFactory->createFromFilePath($manifestFile);
        $expectedChunks = [];
        foreach ($expected as $identifier) {
            $expectedChunks[$identifier] = $subject->getChunk($identifier);
        }
        self::assertSame($expectedChunks, $subject->getImportsForChunk($entrypoint, $recursive));
    }

    /**
     * @return array<string, mixed>[]
     */
    public static function resolveAssetPathDataProvider(): array
    {
        return [
            [
                'manifestFile' => self::FixtureDir . 'ImportJs/.vite/manifest.json',
                'assetFile' => 'assets/Shared-To-v4Zbq.js',
                'expected' => self::FixtureDir . 'ImportJs/assets/Shared-To-v4Zbq.js',
            ],
            [
                'manifestFile' => self::FixtureDir . 'ImportJs/.vite/manifest.json',
                'assetFile' => '.vite/manifest.json',
                'expected' => self::FixtureDir . 'ImportJs/.vite/manifest.json',
            ],
        ];
    }

    #[Test]
    #[DataProvider('resolveAssetPathDataProvider')]
    public function resolveAssetPath(string $manifestFile, string $assetFile, string $expected): void
    {
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $subject = $manifestFactory->createFromFilePath($manifestFile);
        self::assertSame($expected, $subject->resolveAssetPath(OutputFile::create($assetFile)));
    }

    #[Test]
    public function getOnlyEntrypoint(): void
    {
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $subject = $manifestFactory->createFromFilePath(self::FixtureDir . 'ImportJs/.vite/manifest.json');
        self::assertSame($subject->getChunk('Main.js'), $subject->getOnlyEntrypoint());
    }

    /**
     * @return string[][]
     */
    public static function getOnlyEntrypointThrowsExceptionDataProvider(): array
    {
        return [
            [self::FixtureDir . 'MultipleEntries/.vite/manifest.json'],
            [self::FixtureDir . 'NoEntries/.vite/manifest.json'],
        ];
    }

    #[Test]
    #[DataProvider('getOnlyEntrypointThrowsExceptionDataProvider')]
    public function getOnlyEntrypointThrowsException(string $manifestFile): void
    {
        self::expectException(ViteException::class);
        self::expectExceptionCode(1683552723);
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $manifestFactory->createFromFilePath($manifestFile)->getOnlyEntrypoint();
    }

    #[Test]
    public function getDefaultManifest(): void
    {
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'defaultManifest' => self::FixtureDir . 'DefaultManifest/.vite/manifest.json',
        ]);
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $subject = $manifestFactory->getDefault();
        self::assertSame(self::FixtureDir . 'DefaultManifest/', $subject->outputDir);
        self::assertSame('.vite/manifest.json', $subject->file->locator);
    }

    /**
     * @return string[][]
     */
    public static function nonExistentManifestFilesDataProvider(): array
    {
        return [
            [''],
            [self::FixtureDir . 'NonExistent/.vite/manifest.json'],
        ];
    }

    #[Test]
    #[DataProvider('nonExistentManifestFilesDataProvider')]
    public function getDefaultManifestThrowsException(string $manifestFile): void
    {
        self::expectException(ViteException::class);
        self::expectExceptionCode(1684528724);
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'defaultManifest' => $manifestFile,
        ]);
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $manifestFactory->getDefault();
    }

    #[Test]
    #[DataProvider('nonExistentManifestFilesDataProvider')]
    public function createFromFilePathThrowsException(string $manifestFile): void
    {
        self::expectException(ViteException::class);
        self::expectExceptionCode(1683200522);
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $manifestFactory->createFromFilePath($manifestFile);
    }

    /**
     * @return array<string, mixed>[]
     */
    public static function createFromConfiguredPathDataProvider(): array
    {
        return [
            [
                'defaultManifest' => self::FixtureDir . 'DefaultManifest/.vite/manifest.json',
                'configuredManifest' => null,
                'expected' => self::FixtureDir . 'DefaultManifest/',
            ],
            [
                'defaultManifest' => self::FixtureDir . 'DefaultManifest/.vite/manifest.json',
                'configuredManifest' => self::FixtureDir . 'MultipleEntries/.vite/manifest.json',
                'expected' => self::FixtureDir . 'MultipleEntries/',
            ],
        ];
    }

    #[Test]
    #[DataProvider('createFromConfiguredPathDataProvider')]
    public function createFromConfiguredPath(string $defaultManifest, ?string $configuredManifest, string $expected): void
    {
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'defaultManifest' => $defaultManifest,
        ]);
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $subject = $manifestFactory->createFromConfiguredPath($configuredManifest);
        self::assertSame($expected, $subject->outputDir);
        self::assertSame('.vite/manifest.json', $subject->file->locator);
    }

    #[Test]
    public function cacheReturnsSameManifest(): void
    {
        $manifestFile = self::FixtureDir . 'DefaultManifest/.vite/manifest.json';
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        self::assertSame($manifestFactory->createFromFilePath($manifestFile), $manifestFactory->createFromFilePath($manifestFile));
    }
}
