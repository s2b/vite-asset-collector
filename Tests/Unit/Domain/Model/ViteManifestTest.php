<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Domain\Model\ViteManifest;
use Praetorius\ViteAssetCollector\Domain\Model\ViteManifestItem;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ViteManifestTest extends UnitTestCase
{
    #[Test]
    public function getValidEntrypoints(): void
    {
        $manifestPath = realpath(__DIR__ . '/../../../Fixtures/MultipleEntries/manifest.json');
        self::assertEquals(
            [
                'Main.js' => new ViteManifestItem('Main.js', 'Main.js', 'assets/Main-4483b920.js', true, false, [], ['assets/Main-973bb662.css'], [], []),
                'Alt.js' => new ViteManifestItem('Alt.js', 'Alt.js', 'assets/Alt-4483b920.js', true, false, [], ['assets/Alt-973bb662.css'], [], []),
            ],
            (ViteManifest::fromFile($manifestPath))->getValidEntrypoints()
        );
    }

    public static function getItemDataProvider(): array
    {
        return [
            ['Main.js', new ViteManifestItem('Main.js', 'Main.js', 'assets/Main-4483b920.js', true, false, [], ['assets/Main-973bb662.css'], [], [])],
            ['Undefined.js', null],
        ];
    }

    #[Test]
    #[DataProvider('getItemDataProvider')]
    public function getItem(string $identifier, mixed $expected): void
    {
        $manifestPath = realpath(__DIR__ . '/../../../Fixtures/MultipleEntries/manifest.json');
        self::assertEquals(
            $expected,
            (ViteManifest::fromFile($manifestPath))->get($identifier)
        );
    }

    public static function getImportsForEntrypointDataProvider(): array
    {
        $fixtureDir = realpath(__DIR__ . '/../../../Fixtures') . '/';
        return [
            [
                $fixtureDir . 'ImportJs/manifest.json',
                'Main.js',
                false,
                ['_Shared-To-v4Zbq.js' => new ViteManifestItem('_Shared-To-v4Zbq.js', null, 'assets/Shared-To-v4Zbq.js', false, false, [], [], [], [])],
            ],
            [
                $fixtureDir . 'ImportJs/manifest.json',
                'Undefined.js',
                false,
                [],
            ],
            [
                $fixtureDir . 'ImportCssRecursive/manifest.json',
                'Main.js',
                false,
                [
                    '_Shared-To-v4Zbq.js' => new ViteManifestItem(
                        '_Shared-To-v4Zbq.js',
                        null,
                        'assets/Shared-To-v4Zbq.js',
                        false,
                        false,
                        [],
                        ['assets/Shared-pjWofKK4.css'],
                        ['_Nested-abcdef.js'],
                        [],
                    ),
                ],
            ],
            [
                $fixtureDir . 'ImportCssRecursive/manifest.json',
                'Main.js',
                true,
                [
                    '_Shared-To-v4Zbq.js' => new ViteManifestItem(
                        '_Shared-To-v4Zbq.js',
                        null,
                        'assets/Shared-To-v4Zbq.js',
                        false,
                        false,
                        [],
                        ['assets/Shared-pjWofKK4.css'],
                        ['_Nested-abcdef.js'],
                        [],
                    ),
                    '_Nested-abcdef.js' => new ViteManifestItem(
                        '_Nested-abcdef.js',
                        null,
                        'assets/Nested-abcdef.js',
                        false,
                        false,
                        [],
                        ['assets/Nested-defghi.css'],
                        [],
                        [],
                    ),
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('getImportsForEntrypointDataProvider')]
    public function getImportsForEntrypoint(string $manifestFile, string $entrypoint, bool $recursive, mixed $expected): void
    {
        self::assertEquals(
            $expected,
            (ViteManifest::fromFile($manifestFile))->getImportsForEntrypoint($entrypoint, $recursive)
        );
    }
}
