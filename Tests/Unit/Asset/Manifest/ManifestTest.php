<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Unit\Asset\Manifest;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Asset\AssetFile;
use Praetorius\ViteAssetCollector\Asset\Manifest\ManifestChunk;
use Praetorius\ViteAssetCollector\Asset\Manifest\ManifestFactory;
use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ManifestTest extends UnitTestCase
{
    #[Test]
    public function getValidEntrypoints(): void
    {
        $manifestPath = realpath(__DIR__ . '/../../../Fixtures/MultipleEntries/.vite/manifest.json');
        self::assertEquals(
            [
                'Main.js' => new ManifestChunk('Main.js', 'Main', AssetFile::create('Main.js'), OutputFile::create('assets/Main-4483b920.js'), true, false, [], [OutputFile::create('assets/Main-973bb662.css')], [], []),
                'Alt.js' => new ManifestChunk('Alt.js', null, AssetFile::create('Alt.js'), OutputFile::create('assets/Alt-4483b920.js'), true, false, [], [OutputFile::create('assets/Alt-973bb662.css')], [], []),
            ],
            ($this->getManifestFactory()->createFromFilePath($manifestPath))->getValidEntrypoints()
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
        $manifestPath = realpath(__DIR__ . '/../../../Fixtures/MultipleEntries/.vite/manifest.json');
        self::assertEquals(
            $expected,
            ($this->getManifestFactory()->createFromFilePath($manifestPath))->getChunk($identifier)
        );
    }

    public static function getImportsForChunkDataProvider(): array
    {
        $fixtureDir = realpath(__DIR__ . '/../../../Fixtures') . '/';
        return [
            [
                $fixtureDir . 'ImportJs/.vite/manifest.json',
                'Main.js',
                false,
                ['_Shared-To-v4Zbq.js' => new ManifestChunk('_Shared-To-v4Zbq.js', null, null, OutputFile::create('assets/Shared-To-v4Zbq.js'), false, false, [], [], [], [])],
            ],
            [
                $fixtureDir . 'ImportJs/.vite/manifest.json',
                'Undefined.js',
                false,
                [],
            ],
            [
                $fixtureDir . 'ImportCssRecursive/.vite/manifest.json',
                'Main.js',
                false,
                [
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
                $fixtureDir . 'ImportCssRecursive/.vite/manifest.json',
                'Main.js',
                true,
                [
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
                $fixtureDir . 'ImportSelfReference/.vite/manifest.json',
                'Main.js',
                false,
                [
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
                $fixtureDir . 'ImportSelfReference/.vite/manifest.json',
                'Main.js',
                true,
                [
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
        self::assertEquals(
            $expected,
            ($this->getManifestFactory()->createFromFilePath($manifestFile))->getImportsForChunk($entrypoint, $recursive)
        );
    }

    private function getManifestFactory(): ManifestFactory
    {
        return new ManifestFactory(new NullFrontend('manifest'), self::createStub(ExtensionConfiguration::class));
    }
}
