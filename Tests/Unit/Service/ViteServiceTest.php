<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Service\ViteService;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ViteServiceTest extends UnitTestCase
{
    #[Test]
    public function getDefaultManifestFile(): void
    {
        self::assertEquals('myDefaultManifest.json', $this->createViteService(defaultManifest: 'myDefaultManifest.json')->getDefaultManifestFile());
    }

    public static function useDevServerDataProvider(): array
    {
        return [
            'auto' => ['auto', false],
            'enabled' => ['1', true],
            'disabled' => ['0', false],
        ];
    }

    #[Test]
    #[DataProvider('useDevServerDataProvider')]
    public function useDevServer(string $useDevServer, bool $expected): void
    {
        self::assertEquals($expected, $this->createViteService(useDevServer: $useDevServer)->useDevServer());
    }

    public static function determineDevServerDataProvider(): array
    {
        return [
            'auto with no env' => [
                'auto',
                [],
                'https://some.ddev.site:5173',
            ],
            'auto with uri provided as env' => [
                'auto',
                ['VITE_SERVER_URI' => 'https://example.com:1234'],
                'https://example.com:1234',
            ],
            'auto with port provided as env' => [
                'auto',
                ['VITE_PRIMARY_PORT' => '5174'],
                'https://some.ddev.site:5174',
            ],
            'auth with both uri and port provided as env' => [
                'auto',
                [
                    'VITE_SERVER_URI' => 'https://example.com:1234',
                    'VITE_PRIMARY_PORT' => '5174',
                ],
                'https://example.com:1234',
            ],
            'manual' => [
                'https://devserver.localhost:5173',
                [],
                'https://devserver.localhost:5173',
            ],
        ];
    }

    #[Test]
    #[DataProvider('determineDevServerDataProvider')]
    public function determineDevServer(string $configOption, array $envVars, string $expected): void
    {
        $request = new ServerRequest(new Uri('https://some.ddev.site/path/to/file'));

        // Set environment variables
        $originalVariables = [];
        foreach ($envVars as $name => $value) {
            $originalVariables[$name] = getenv($name);
            putenv($name . '=' . $value);
        }

        self::assertEquals(
            $expected,
            (string)$this->createViteService(devServerUri: $configOption)->determineDevServer($request)
        );

        // Unset environment variables
        foreach ($originalVariables as $name => $value) {
            putenv($name . '=' . (string)$value);
        }
    }

    #[Test]
    public function determineEntrypointFromManifest(): void
    {
        self::assertEquals(
            'Main.js',
            $this->createViteService()->determineEntrypointFromManifest(
                realpath(__DIR__ . '/../../Fixtures/ValidManifest/manifest.json')
            )
        );
    }

    #[Test]
    public function determineEntrypointFromManifestWithMultipleEntries(): void
    {
        $this->expectException(ViteException::class);
        $this->expectExceptionCode(1683552723);
        $this->createViteService()->determineEntrypointFromManifest(
            realpath(__DIR__ . '/../../Fixtures/MultipleEntries/manifest.json')
        );
    }

    #[Test]
    public function determineEntrypointFromManifestWithNoEntries(): void
    {
        $this->expectException(ViteException::class);
        $this->expectExceptionCode(1683552723);
        $this->createViteService()->determineEntrypointFromManifest(
            realpath(__DIR__ . '/../../Fixtures/NoEntries/manifest.json')
        );
    }

    public static function addAssetsFromDevServerDataProvider(): array
    {
        return [
            'withoutPriority' => [
                'entry' => 'path/to/Main.js',
                'options' => [],
                'javaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                    'vite:path/to/Main.js' => [
                        'source' => 'https://localhost:5173/path/to/Main.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                ],
            ],
            'withPriority' => [
                'entry' => 'path/to/Main.js',
                'options' => ['priority' => true],
                'priorityJavaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['priority' => true, 'external' => self::useExternalFlag()],
                    ],
                    'vite:path/to/Main.js' => [
                        'source' => 'https://localhost:5173/path/to/Main.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['priority' => true, 'external' => self::useExternalFlag()],
                    ],
                ],
            ],
            'withExtPath' => [
                'entry' => 'EXT:test_extension/Resources/Private/JavaScript/Main.js',
                'options' => [],
                'javaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                    'vite:Tests/Fixtures/test_extension/Resources/Private/JavaScript/Main.js' => [
                        'source' => 'https://localhost:5173/Tests/Fixtures/test_extension/Resources/Private/JavaScript/Main.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                ],
            ],
            'withSymlinkedExtPath' => [
                'entry' => 'EXT:symlink_extension/Resources/Private/JavaScript/Main.js',
                'options' => [],
                'javaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                    'vite:Tests/Fixtures/test_extension/Resources/Private/JavaScript/Main.js' => [
                        'source' => 'https://localhost:5173/Tests/Fixtures/test_extension/Resources/Private/JavaScript/Main.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                ],
            ],
            'withCssEntrypoint' => [
                'entry' => 'path/to/Main.css',
                'options' => [],
                'javaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                ],
                'styleSheets' => [
                    'vite:path/to/Main.css' => [
                        'source' => 'https://localhost:5173/path/to/Main.css',
                        'attributes' => ['media' => 'screen', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                ],
            ],
            'withCssEntrypointAndPriority' => [
                'entry' => 'path/to/Main.css',
                'options' => ['priority' => true],
                'priorityJavaScripts' => [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['priority' => true, 'external' => self::useExternalFlag()],
                    ],
                ],
                'priorityStyleSheets' => [
                    'vite:path/to/Main.css' => [
                        'source' => 'https://localhost:5173/path/to/Main.css',
                        'attributes' => ['media' => 'screen', 'otherAttribute' => 'otherValue'],
                        'options' => ['priority' => true, 'external' => self::useExternalFlag()],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('addAssetsFromDevServerDataProvider')]
    public function addAssetsFromDevServer(
        string $entry,
        array $options,
        array $javaScripts = [],
        array $priorityJavaScripts = [],
        array $styleSheets = [],
        array $priorityStyleSheets = []
    ): void {
        $assetCollector = new AssetCollector();
        $this->createViteService($assetCollector)->addAssetsFromDevServer(
            new Uri('https://localhost:5173'),
            $entry,
            $options,
            ['async' => true, 'otherAttribute' => 'otherValue'],
            ['media' => 'screen', 'otherAttribute' => 'otherValue'],
        );

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

    public static function addAssetsFromManifestDataProvider(): array
    {
        $fixtureDir = realpath(__DIR__ . '/../../Fixtures') . '/';
        return [
            'withoutCss' => [
                'manifestFile' => $fixtureDir . 'ValidManifest/manifest.json',
                'entry' => 'Main.js',
                'options' => [],
                'addCss' => false,
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' =>  $fixtureDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                ],
            ],
            'withCss' => [
                'manifestFile' => $fixtureDir . 'ValidManifest/manifest.json',
                'entry' => 'Main.js',
                'options' => [],
                'addCss' => true,
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' =>  $fixtureDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                ],
                'styleSheets' => [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' =>  $fixtureDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                ],
            ],
            'onlyCss' => [
                'manifestFile' => $fixtureDir . 'OnlyCssManifest/manifest.json',
                'entry' => 'Main.scss',
                'options' => [],
                'addCss' => true,
                'styleSheets' => [
                    'vite:Main.scss' => [
                        'source' =>  $fixtureDir . 'OnlyCssManifest/assets/Main-4483b920.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                ],
            ],
            'onlyCssFlagDisabled' => [
                'manifestFile' => $fixtureDir . 'OnlyCssManifest/manifest.json',
                'entry' => 'Main.scss',
                'options' => [],
                'addCss' => false,
            ],
            'withCssAndPriority' => [
                'manifestFile' => $fixtureDir . 'ValidManifest/manifest.json',
                'entry' => 'Main.js',
                'options' => ['priority' => true],
                'addCss' => true,
                'priorityJavaScripts' => [
                    'vite:Main.js' => [
                        'source' =>  $fixtureDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['priority' => true, 'external' => self::useExternalFlag()],
                    ],
                ],
                'priorityStyleSheets' => [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' =>  $fixtureDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => ['priority' => true, 'external' => self::useExternalFlag()],
                    ],
                ],
            ],
            'withExtPath' => [
                'manifestFile' => $fixtureDir . 'ExtPathManifest/manifest.json',
                'entry' => 'EXT:test_extension/Resources/Private/JavaScript/Main.js',
                'options' => [],
                'addCss' => false,
                'javaScripts' => [
                    'vite:Tests/Fixtures/test_extension/Resources/Private/JavaScript/Main.js' => [
                        'source' =>  $fixtureDir . 'ExtPathManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                ],
            ],
            'withSymlinkedExtPath' => [
                'manifestFile' => $fixtureDir . 'ExtPathManifest/manifest.json',
                'entry' => 'EXT:symlink_extension/Resources/Private/JavaScript/Main.js',
                'options' => [],
                'addCss' => false,
                'javaScripts' => [
                    'vite:Tests/Fixtures/test_extension/Resources/Private/JavaScript/Main.js' => [
                        'source' =>  $fixtureDir . 'ExtPathManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                ],
            ],
            'withImportedJs' => [
                'manifestFile' => $fixtureDir . 'ImportJs/manifest.json',
                'entry' => 'Main.js',
                'options' => [],
                'addCss' => true,
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' =>  $fixtureDir . 'ImportJs/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                ],
                'styleSheets' => [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' =>  $fixtureDir . 'ImportJs/assets/Main-973bb662.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                ],
            ],
            'withImportedJsAndCss' => [
                'manifestFile' => $fixtureDir . 'ImportJsAndCss/manifest.json',
                'entry' => 'Main.js',
                'options' => [],
                'addCss' => true,
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                ],
                'styleSheets' => [
                    'vite:6a181085b68130ba16f066fdaaf2da09:assets/Shared-pjWofKK4.css' => [
                        'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Shared-pjWofKK4.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Main-973bb662.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                ],
            ],
            'vite5' => [
                'manifestFile' => $fixtureDir . 'Vite5Manifest/.vite/manifest.json',
                'entry' => 'Default.js',
                'options' => [],
                'addCss' => true,
                'javaScripts' => [
                    'vite:Default.js' => [
                        'source' =>  $fixtureDir . 'Vite5Manifest/assets/Default-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                ],
                'styleSheets' => [
                    'vite:Default.js:assets/Default-973bb662.css' => [
                        'source' =>  $fixtureDir . 'Vite5Manifest/assets/Default-973bb662.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                ],
            ],
            'vite5PathFallback' => [
                'manifestFile' => $fixtureDir . 'DefaultManifest/.vite/manifest.json',
                'entry' => 'Default.js',
                'options' => [],
                'addCss' => true,
                'javaScripts' => [
                    'vite:Default.js' => [
                        'source' =>  $fixtureDir . 'DefaultManifest/assets/Default-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                ],
                'styleSheets' => [
                    'vite:Default.js:assets/Default-973bb662.css' => [
                        'source' =>  $fixtureDir . 'DefaultManifest/assets/Default-973bb662.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => ['external' => self::useExternalFlag()],
                    ],
                ],
            ],
            'overrideExternalFlag' => [
                'manifestFile' => $fixtureDir . 'ValidManifest/manifest.json',
                'entry' => 'Main.js',
                'options' => ['external' => true],
                'addCss' => false,
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' =>  $fixtureDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['external' => true],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('addAssetsFromManifestDataProvider')]
    public function addAssetsFromManifest(
        string $manifestFile,
        string $entry,
        array $options,
        bool $addCss,
        array $javaScripts = [],
        array $priorityJavaScripts = [],
        array $styleSheets = [],
        array $priorityStyleSheets = [],
    ): void {
        $assetCollector = new AssetCollector();
        $this->createViteService($assetCollector)->addAssetsFromManifest(
            $manifestFile,
            $entry,
            $addCss,
            $options,
            ['async' => true, 'otherAttribute' => 'otherValue'],
            ['media' => 'print', 'disabled' => true],
        );

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

    #[Test]
    public function addAssetsFromManifestPreventDuplicateCss(): void
    {
        $assetCollector = new AssetCollector();
        $viteService = $this->createViteService($assetCollector);

        $fixtureDir = realpath(__DIR__ . '/../../Fixtures') . '/';
        $manifestFile = $fixtureDir . 'ImportJsAndCss/manifest.json';
        $viteService->addAssetsFromManifest($manifestFile, 'Main.js');
        $viteService->addAssetsFromManifest($manifestFile, 'Alternative.js');

        self::assertEquals(
            [
                'vite:4c3e6cf2811f4c91dfa15ba7d99e10a8:assets/Shared-pjWofKK4.css' => [
                    'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Shared-pjWofKK4.css',
                    'attributes' => [],
                    'options' => ['external' => self::useExternalFlag()],
                ],
                'vite:Main.js:assets/Main-973bb662.css' => [
                    'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Main-973bb662.css',
                    'attributes' => [],
                    'options' => ['external' => self::useExternalFlag()],
                ],
                'vite:Alternative.js:assets/Alternative-973bb662.css' => [
                    'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Alternative-973bb662.css',
                    'attributes' => [],
                    'options' => ['external' => self::useExternalFlag()],
                ],
            ],
            $assetCollector->getStyleSheets(false)
        );
    }

    #[Test]
    public function addAssetsFromManifestAddDuplicateCssWithDifferentSettings(): void
    {
        $assetCollector = new AssetCollector();
        $viteService = $this->createViteService($assetCollector);

        $fixtureDir = realpath(__DIR__ . '/../../Fixtures') . '/';
        $manifestFile = $fixtureDir . 'ImportJsAndCss/manifest.json';
        $viteService->addAssetsFromManifest($manifestFile, 'Main.js', cssTagAttributes: ['media' => 'print']);
        $viteService->addAssetsFromManifest($manifestFile, 'Alternative.js');

        self::assertEquals(
            [
                'vite:88713ee6f56256eb987323824e723146:assets/Shared-pjWofKK4.css' => [
                    'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Shared-pjWofKK4.css',
                    'attributes' => ['media' => 'print'],
                    'options' => ['external' => self::useExternalFlag()],
                ],
                'vite:Main.js:assets/Main-973bb662.css' => [
                    'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Main-973bb662.css',
                    'attributes' => ['media' => 'print'],
                    'options' => ['external' => self::useExternalFlag()],
                ],
                'vite:4c3e6cf2811f4c91dfa15ba7d99e10a8:assets/Shared-pjWofKK4.css' => [
                    'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Shared-pjWofKK4.css',
                    'attributes' => [],
                    'options' => ['external' => self::useExternalFlag()],
                ],
                'vite:Alternative.js:assets/Alternative-973bb662.css' => [
                    'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Alternative-973bb662.css',
                    'attributes' => [],
                    'options' => ['external' => self::useExternalFlag()],
                ],
            ],
            $assetCollector->getStyleSheets(false)
        );
    }

    public static function addAssetsFromManifestFileErrorHandlingDataProvider(): array
    {
        $fixtureDir = realpath(__DIR__ . '/../../Fixtures') . '/';
        return [
            'invalidJson' => [
                $fixtureDir . 'InvalidManifest/manifest.json',
                'Main.js',
                1683200523,
            ],
            'nonExistentFile' => [
                $fixtureDir . 'InvalidManifest/manifest123.json',
                'Main.js',
                1683200522,
            ],
            'invalidEntry' => [
                $fixtureDir . 'ValidManifest/manifest.json',
                'Main.css',
                1683200524,
            ],
            'nonExistentEntry' => [
                $fixtureDir . 'ValidManifest/manifest.json',
                'NonExistentEntry.js',
                1683200524,
            ],
        ];
    }

    #[Test]
    #[DataProvider('addAssetsFromManifestFileErrorHandlingDataProvider')]
    public function addAssetsFromManifestFileErrorHandling(
        string $manifestFile,
        string $entry,
        int $exceptionCode
    ): void {
        $this->expectException(ViteException::class);
        $this->expectExceptionCode($exceptionCode);
        $this->createViteService()->addAssetsFromManifest($manifestFile, $entry);
    }

    public static function getAssetPathFromDevServerDataProvider(): array
    {
        return [
            ['path/to/file.jpg', 'https://localhost:5173/path/to/file.jpg'],
            ['EXT:test_extension/Resources/Private/Assets/test.txt', 'https://localhost:5173/Tests/Fixtures/test_extension/Resources/Private/Assets/test.txt'],
        ];
    }

    #[Test]
    #[DataProvider('getAssetPathFromDevServerDataProvider')]
    public function getAssetPathFromDevServer(string $file, string $expected): void
    {
        self::assertEquals(
            $expected,
            $this->createViteService()->getAssetPathFromDevServer(
                new Uri('https://localhost:5173'),
                $file
            )
        );
    }

    #[Test]
    public function getAssetPathFromManifest(): void
    {
        $fixtureDir = realpath(__DIR__ . '/../../Fixtures') . '/';
        $manifestDir = realpath(__DIR__ . '/../../Fixtures/ValidManifest') . '/';
        self::assertEquals(
            $manifestDir . 'assets/Main-973bb662.css',
            $this->createViteService()->getAssetPathFromManifest($fixtureDir . 'ValidManifest/manifest.json', 'Main.css')
        );
    }

    #[Test]
    public function getAssetWithExtPathFromManifest(): void
    {
        $fixtureDir = realpath(__DIR__ . '/../../Fixtures') . '/';
        $manifestDir = realpath(__DIR__ . '/../../Fixtures/ExtPathManifest') . '/';
        self::assertEquals(
            $manifestDir . 'assets/Main-4483b920.js',
            $this->createViteService()->getAssetPathFromManifest(
                $fixtureDir . 'ExtPathManifest/manifest.json',
                'EXT:symlink_extension/Resources/Private/JavaScript/Main.js'
            )
        );
    }

    public static function getAssetPathFromManifestErrorHandlingDataProvider(): array
    {
        $fixtureDir = realpath(__DIR__ . '/../../Fixtures') . '/';
        return [
            [
                $fixtureDir . 'ValidManifest/manifest.json',
                'NonExistentEntry.css',
                1690735353,
            ],
            [
                $fixtureDir . 'ValidManifest/manifest.json',
                'EXT:test_extension/Resources/Private/JavaScript/NonExistent/NonExistent.js',
                1696238083,
            ],
        ];
    }

    #[Test]
    #[DataProvider('getAssetPathFromManifestErrorHandlingDataProvider')]
    public function getAssetPathFromManifestErrorHandling(string $manifestFile, string $entry, int $exceptionCode): void
    {
        $this->expectException(ViteException::class);
        $this->expectExceptionCode($exceptionCode);

        $this->createViteService()->getAssetPathFromManifest($manifestFile, $entry);
    }

    private function createViteService(
        ?AssetCollector $assetCollector = null,
        string $defaultManifest = '_assets/vite/.vite/manifest.json',
        string $useDevServer = 'auto',
        string $devServerUri = 'auto'
    ) {
        $assetCollector ??= new AssetCollector();

        $fixtureDir = realpath(__DIR__ . '/../../Fixtures') . '/';
        $packageManager = self::createStub(PackageManager::class);
        $packageManager
            ->method('resolvePackagePath')
            ->willReturnMap([
                [
                    'EXT:test_extension/Resources/Private/JavaScript/Main.js',
                    $fixtureDir . 'test_extension/Resources/Private/JavaScript/Main.js',
                ],
                [
                    'EXT:test_extension/Resources/Private/Assets/test.txt',
                    $fixtureDir . 'test_extension/Resources/Private/Assets/test.txt',
                ],
                [
                    'EXT:symlink_extension/Resources/Private/JavaScript/Main.js',
                    $fixtureDir . 'symlink_extension/Resources/Private/JavaScript/Main.js',
                ],
                [
                    'EXT:test_extension/Resources/Private/JavaScript/NonExistent/NonExistent.js',
                    $fixtureDir . 'test_extension/Resources/Private/NonExistent/NonExistent.js',
                ],
            ]);

        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration
            ->method('get')
            ->willReturnMap([
                ['vite_asset_collector', 'defaultManifest', $defaultManifest],
                ['vite_asset_collector', 'useDevServer', $useDevServer],
                ['vite_asset_collector', 'devServerUri', $devServerUri],
            ]);

        return new ViteService(
            new NullFrontend('manifest'),
            $assetCollector,
            $packageManager,
            $extensionConfiguration
        );
    }

    protected static function useExternalFlag(): bool
    {
        // TODO remove this when support for TYPO3 v12 is dropped
        return (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 13;
    }
}
