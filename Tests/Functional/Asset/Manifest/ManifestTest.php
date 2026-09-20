<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\Asset\Manifest;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Asset\AssetFile;
use Praetorius\ViteAssetCollector\Asset\Manifest\ManifestChunk;
use Praetorius\ViteAssetCollector\Asset\Manifest\ManifestFactory;
use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Tests\Functional\AbstractViteFunctionalTestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

final class ManifestTest extends AbstractViteFunctionalTestCase
{
    private const FixtureDir = __DIR__ . '/../../../Fixtures/';

    #[Test]
    public function getValidEntrypoints(): void
    {
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $subject = $manifestFactory->createFromFilePath(self::FixtureDir . 'MultipleEntries/.vite/manifest.json');
        self::assertEquals(
            [
                'Main.js' => new ManifestChunk('Main.js', 'Main', AssetFile::create('Main.js'), OutputFile::create('assets/Main-4483b920.js'), true, false, [], [OutputFile::create('assets/Main-973bb662.css')], [], []),
                'Alt.js' => new ManifestChunk('Alt.js', null, AssetFile::create('Alt.js'), OutputFile::create('assets/Alt-4483b920.js'), true, false, [], [OutputFile::create('assets/Alt-973bb662.css')], [], []),
            ],
            $subject->getValidEntrypoints()
        );
    }

    public static function getChunkDataProvider(): array
    {
        return [
            ['Main.js', new ManifestChunk('Main.js', 'Main', AssetFile::create('Main.js'), OutputFile::create('assets/Main-4483b920.js'), true, false, [], [OutputFile::create('assets/Main-973bb662.css')], [], [])],
            ['Undefined.js', null],
        ];
    }

    #[Test]
    #[DataProvider('getChunkDataProvider')]
    public function getChunk(string $identifier, mixed $expected): void
    {
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $subject = $manifestFactory->createFromFilePath(self::FixtureDir . 'MultipleEntries/.vite/manifest.json');
        self::assertEquals(
            $expected,
            $subject->getChunk($identifier)
        );
    }

    public static function getImportsForChunkDataProvider(): array
    {
        return [
            [
                'manifestFile' => self::FixtureDir . 'ImportJs/.vite/manifest.json',
                'entrypoint' => 'Main.js',
                'recursive' => false,
                'expected' => ['_Shared-To-v4Zbq.js' => new ManifestChunk('_Shared-To-v4Zbq.js', null, null, OutputFile::create('assets/Shared-To-v4Zbq.js'), false, false, [], [], [], [])],
            ],
            [
                'manifestFile' => self::FixtureDir . 'ImportJs/.vite/manifest.json',
                'entrypoint' => 'Undefined.js',
                'recursive' => false,
                'expected' => [],
            ],
            [
                'manifestFile' => self::FixtureDir . 'ImportCssRecursive/.vite/manifest.json',
                'entrypoint' => 'Main.js',
                'recursive' => false,
                'expected' => [
                    '_Shared-To-v4Zbq.js' => new ManifestChunk(
                        '_Shared-To-v4Zbq.js',
                        null,
                        null,
                        OutputFile::create('assets/Shared-To-v4Zbq.js'),
                        false,
                        false,
                        [],
                        [OutputFile::create('assets/Shared-pjWofKK4.css')],
                        ['_Nested-abcdef.js'],
                        [],
                    ),
                ],
            ],
            [
                'manifestFile' => self::FixtureDir . 'ImportCssRecursive/.vite/manifest.json',
                'entrypoint' => 'Main.js',
                'recursive' => true,
                'expected' => [
                    '_Shared-To-v4Zbq.js' => new ManifestChunk(
                        '_Shared-To-v4Zbq.js',
                        null,
                        null,
                        OutputFile::create('assets/Shared-To-v4Zbq.js'),
                        false,
                        false,
                        [],
                        [OutputFile::create('assets/Shared-pjWofKK4.css')],
                        ['_Nested-abcdef.js'],
                        [],
                    ),
                    '_Nested-abcdef.js' => new ManifestChunk(
                        '_Nested-abcdef.js',
                        null,
                        null,
                        OutputFile::create('assets/Nested-abcdef.js'),
                        false,
                        false,
                        [],
                        [OutputFile::create('assets/Nested-defghi.css')],
                        [],
                        [],
                    ),
                ],
            ],
            [
                'manifestFile' => self::FixtureDir . 'ImportSelfReference/.vite/manifest.json',
                'entrypoint' => 'Main.js',
                'recursive' => false,
                'expected' => [
                    '_Shared-To-v4Zbq.js' => new ManifestChunk(
                        '_Shared-To-v4Zbq.js',
                        null,
                        null,
                        OutputFile::create('assets/Shared-To-v4Zbq.js'),
                        false,
                        false,
                        [],
                        [OutputFile::create('assets/Shared-pjWofKK4.css')],
                        ['_Nested-abcdef.js', 'Main.js'],
                        [],
                    ),
                    'Main.js' => new ManifestChunk(
                        'Main.js',
                        null,
                        AssetFile::create('Main.js'),
                        OutputFile::create('assets/Main-4483b920.js'),
                        true,
                        false,
                        [],
                        [],
                        ['_Shared-To-v4Zbq.js', 'Main.js'],
                        [],
                    ),
                ],
            ],
            [
                'manifestFile' => self::FixtureDir . 'ImportSelfReference/.vite/manifest.json',
                'entrypoint' => 'Main.js',
                'recursive' => true,
                'expected' => [
                    '_Shared-To-v4Zbq.js' => new ManifestChunk(
                        '_Shared-To-v4Zbq.js',
                        null,
                        null,
                        OutputFile::create('assets/Shared-To-v4Zbq.js'),
                        false,
                        false,
                        [],
                        [OutputFile::create('assets/Shared-pjWofKK4.css')],
                        ['_Nested-abcdef.js', 'Main.js'],
                        [],
                    ),
                    '_Nested-abcdef.js' => new ManifestChunk(
                        '_Nested-abcdef.js',
                        null,
                        null,
                        OutputFile::create('assets/Nested-abcdef.js'),
                        false,
                        false,
                        [],
                        [OutputFile::create('assets/Nested-defghi.css')],
                        [],
                        [],
                    ),
                    'Main.js' => new ManifestChunk(
                        'Main.js',
                        null,
                        AssetFile::create('Main.js'),
                        OutputFile::create('assets/Main-4483b920.js'),
                        true,
                        false,
                        [],
                        [],
                        ['_Shared-To-v4Zbq.js', 'Main.js'],
                        [],
                    ),
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('getImportsForChunkDataProvider')]
    public function getImportsForChunk(string $manifestFile, string $entrypoint, bool $recursive, mixed $expected): void
    {
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $subject = $manifestFactory->createFromFilePath($manifestFile);
        self::assertEquals($expected, $subject->getImportsForChunk($entrypoint, $recursive));
    }

    public static function resolveAssetPathDataProvider(): array
    {
        return [
            [self::FixtureDir . 'ImportJs/.vite/manifest.json', 'assets/Shared-To-v4Zbq.js', self::FixtureDir . 'ImportJs/assets/Shared-To-v4Zbq.js'],
            [self::FixtureDir . 'ImportJs/.vite/manifest.json', '.vite/manifest.json', self::FixtureDir . 'ImportJs/.vite/manifest.json'],
        ];
    }

    #[Test]
    #[DataProvider('resolveAssetPathDataProvider')]
    public function resolveAssetPath(string $manifestFile, string $assetFile, string $expected): void
    {
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $subject = $manifestFactory->createFromFilePath($manifestFile);
        self::assertEquals($expected, $subject->resolveAssetPath(OutputFile::create($assetFile)));
    }

    #[Test]
    public function getOnlyEntrypoint(): void
    {
        /** @var ManifestFactory */
        $manifestFactory = $this->get(ManifestFactory::class);
        $subject = $manifestFactory->createFromFilePath(self::FixtureDir . 'ImportJs/.vite/manifest.json');
        self::assertEquals(
            new ManifestChunk('Main.js', '', AssetFile::create('Main.js'), OutputFile::create('assets/Main-4483b920.js'), true, false, [], [OutputFile::create('assets/Main-973bb662.css')], ['_Shared-To-v4Zbq.js'], []),
            $subject->getOnlyEntrypoint(),
        );
    }

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
