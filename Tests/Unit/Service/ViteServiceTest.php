<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Unit\Service;

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
    /**
     * @test
     */
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

    /**
     * @test
     * @dataProvider useDevServerDataProvider
     */
    public function useDevServer(string $useDevServer, bool $expected): void
    {
        self::assertEquals($expected, $this->createViteService(useDevServer: $useDevServer)->useDevServer());
    }

    public static function determineDevServerDataProvider(): array
    {
        return [
            'auto' => ['auto', 'https://localhost:5173'],
            'uri' => ['https://devserver.localhost:5173', 'https://devserver.localhost:5173'],
        ];
    }

    /**
     * @test
     * @dataProvider determineDevServerDataProvider
     */
    public function determineDevServer(string $devServerUri, string $expected): void
    {
        $request = new ServerRequest(new Uri('https://localhost/path/to/file'));
        self::assertEquals(
            $expected,
            (string)$this->createViteService(devServerUri: $devServerUri)->determineDevServer($request)
        );
    }

    /**
     * @test
     */
    public function determineEntrypointFromManifest(): void
    {
        self::assertEquals(
            'Main.js',
            $this->createViteService()->determineEntrypointFromManifest(
                realpath(__DIR__ . '/../../Fixtures/ValidManifest/manifest.json')
            )
        );
    }

    /**
     * @test
     */
    public function determineEntrypointFromManifestWithMultipleEntries(): void
    {
        $this->expectException(ViteException::class);
        $this->expectExceptionCode(1683552723);
        $this->createViteService()->determineEntrypointFromManifest(
            realpath(__DIR__ . '/../../Fixtures/MultipleEntries/manifest.json')
        );
    }

    /**
     * @test
     */
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
                'path/to/Main.js',
                [],
                [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => [],
                    ],
                    'vite:path/to/Main.js' => [
                        'source' => 'https://localhost:5173/path/to/Main.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => [],
                    ],
                ],
                [],
            ],
            'withPriority' => [
                'path/to/Main.js',
                ['priority' => true],
                [],
                [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['priority' => true],
                    ],
                    'vite:path/to/Main.js' => [
                        'source' => 'https://localhost:5173/path/to/Main.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['priority' => true],
                    ],
                ],
            ],
            'withExtPath' => [
                'EXT:test_extension/Resources/Private/JavaScript/Main.js',
                [],
                [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => [],
                    ],
                    'vite:Tests/Fixtures/test_extension/Resources/Private/JavaScript/Main.js' => [
                        'source' => 'https://localhost:5173/Tests/Fixtures/test_extension/Resources/Private/JavaScript/Main.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => [],
                    ],
                ],
                [],
            ],
            'withSymlinkedExtPath' => [
                'EXT:symlink_extension/Resources/Private/JavaScript/Main.js',
                [],
                [
                    'vite' => [
                        'source' => 'https://localhost:5173/@vite/client',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => [],
                    ],
                    'vite:Tests/Fixtures/test_extension/Resources/Private/JavaScript/Main.js' => [
                        'source' => 'https://localhost:5173/Tests/Fixtures/test_extension/Resources/Private/JavaScript/Main.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => [],
                    ],
                ],
                [],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider addAssetsFromDevServerDataProvider
     */
    public function addAssetsFromDevServer(string $entry, array $options, array $javaScripts, array $priorityJavaScripts): void
    {
        $assetCollector = new AssetCollector();
        $this->createViteService($assetCollector)->addAssetsFromDevServer(
            new Uri('https://localhost:5173'),
            $entry,
            $options,
            ['async' => true, 'otherAttribute' => 'otherValue']
        );

        self::assertEquals(
            $javaScripts,
            $assetCollector->getJavaScripts(false)
        );
        self::assertEquals(
            $priorityJavaScripts,
            $assetCollector->getJavaScripts(true)
        );
    }

    public static function addAssetsFromManifestDataProvider(): array
    {
        $fixtureDir = realpath(__DIR__ . '/../../Fixtures') . '/';
        return [
            'withoutCss' => [
                $fixtureDir . 'ValidManifest/manifest.json',
                'Main.js',
                [],
                false,
                [
                    'vite:Main.js' => [
                        'source' =>  $fixtureDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => [],
                    ],
                ],
                [],
                [],
                [],
            ],
            'withCss' => [
                $fixtureDir . 'ValidManifest/manifest.json',
                'Main.js',
                [],
                true,
                [
                    'vite:Main.js' => [
                        'source' =>  $fixtureDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => [],
                    ],
                ],
                [],
                [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' =>  $fixtureDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => [],
                    ],
                ],
                [],
            ],
            'withCssAndPriority' => [
                $fixtureDir . 'ValidManifest/manifest.json',
                'Main.js',
                ['priority' => true],
                true,
                [],
                [
                    'vite:Main.js' => [
                        'source' =>  $fixtureDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['priority' => true],
                    ],
                ],
                [],
                [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' =>  $fixtureDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => ['priority' => true],
                    ],
                ],
            ],
            'withExtPath' => [
                $fixtureDir . 'ExtPathManifest/manifest.json',
                'EXT:test_extension/Resources/Private/JavaScript/Main.js',
                [],
                false,
                [
                    'vite:Tests/Fixtures/test_extension/Resources/Private/JavaScript/Main.js' => [
                        'source' =>  $fixtureDir . 'ExtPathManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => [],
                    ],
                ],
                [],
                [],
                [],
            ],
            'withSymlinkedExtPath' => [
                $fixtureDir . 'ExtPathManifest/manifest.json',
                'EXT:symlink_extension/Resources/Private/JavaScript/Main.js',
                [],
                false,
                [
                    'vite:Tests/Fixtures/test_extension/Resources/Private/JavaScript/Main.js' => [
                        'source' =>  $fixtureDir . 'ExtPathManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => [],
                    ],
                ],
                [],
                [],
                [],
            ],
            'withImportedJs' => [
                $fixtureDir . 'ImportJs/manifest.json',
                'Main.js',
                [],
                true,
                [
                    'vite:Main.js' => [
                        'source' =>  $fixtureDir . 'ImportJs/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => [],
                    ],
                ],
                [],
                [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' =>  $fixtureDir . 'ImportJs/assets/Main-973bb662.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => [],
                    ],
                ],
                [],
            ],
            'withImportedJsAndCss' => [
                $fixtureDir . 'ImportJsAndCss/manifest.json',
                'Main.js',
                [],
                true,
                [
                    'vite:Main.js' => [
                        'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => [],
                    ],
                ],
                [],
                [
                    'vite:18180c035c06afd22c13c58e8969eb4c:assets/Shared-pjWofKK4.css' => [
                        'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Shared-pjWofKK4.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => [],
                    ],
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Main-973bb662.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => [],
                    ],
                ],
                [],
            ],
            'vite5' => [
                $fixtureDir . 'Vite5Manifest/.vite/manifest.json',
                'Default.js',
                [],
                true,
                [
                    'vite:Default.js' => [
                        'source' =>  $fixtureDir . 'Vite5Manifest/assets/Default-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => [],
                    ],
                ],
                [],
                [
                    'vite:Default.js:assets/Default-973bb662.css' => [
                        'source' =>  $fixtureDir . 'Vite5Manifest/assets/Default-973bb662.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => [],
                    ],
                ],
                [],
            ],
            'vite5PathFallback' => [
                $fixtureDir . 'DefaultManifest/.vite/manifest.json',
                'Default.js',
                [],
                true,
                [
                    'vite:Default.js' => [
                        'source' =>  $fixtureDir . 'DefaultManifest/assets/Default-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => [],
                    ],
                ],
                [],
                [
                    'vite:Default.js:assets/Default-973bb662.css' => [
                        'source' =>  $fixtureDir . 'DefaultManifest/assets/Default-973bb662.css',
                        'attributes' => ['media' => 'print', 'disabled' => 'disabled'],
                        'options' => [],
                    ],
                ],
                [],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider addAssetsFromManifestDataProvider
     */
    public function addAssetsFromManifest(
        string $manifestFile,
        string $entry,
        array $options,
        bool $addCss,
        array $javaScripts,
        array $priorityJavaScripts,
        array $styleSheets,
        array $priorityStyleSheets
    ): void {
        $assetCollector = new AssetCollector();
        $this->createViteService($assetCollector)->addAssetsFromManifest(
            $manifestFile,
            $entry,
            $addCss,
            $options,
            ['async' => true, 'otherAttribute' => 'otherValue'],
            ['media' => 'print', 'disabled' => true]
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

    /**
     * @test
     */
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
                'vite:73c562ec32054af04c92dbddc14e1331:assets/Shared-pjWofKK4.css' => [
                    'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Shared-pjWofKK4.css',
                    'attributes' => [],
                    'options' => [],
                ],
                'vite:Main.js:assets/Main-973bb662.css' => [
                    'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Main-973bb662.css',
                    'attributes' => [],
                    'options' => [],
                ],
                'vite:Alternative.js:assets/Alternative-973bb662.css' => [
                    'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Alternative-973bb662.css',
                    'attributes' => [],
                    'options' => [],
                ],
            ],
            $assetCollector->getStyleSheets(false)
        );
    }

    /**
     * @test
     */
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
                'vite:3303cb4a84cd610d452f0d1a37be9266:assets/Shared-pjWofKK4.css' => [
                    'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Shared-pjWofKK4.css',
                    'attributes' => ['media' => 'print'],
                    'options' => [],
                ],
                'vite:Main.js:assets/Main-973bb662.css' => [
                    'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Main-973bb662.css',
                    'attributes' => ['media' => 'print'],
                    'options' => [],
                ],
                'vite:73c562ec32054af04c92dbddc14e1331:assets/Shared-pjWofKK4.css' => [
                    'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Shared-pjWofKK4.css',
                    'attributes' => [],
                    'options' => [],
                ],
                'vite:Alternative.js:assets/Alternative-973bb662.css' => [
                    'source' =>  $fixtureDir . 'ImportJsAndCss/assets/Alternative-973bb662.css',
                    'attributes' => [],
                    'options' => [],
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

    /**
     * @test
     * @dataProvider addAssetsFromManifestFileErrorHandlingDataProvider
     */
    public function addAssetsFromManifestFileErrorHandling(
        string $manifestFile,
        string $entry,
        int $exceptionCode
    ): void {
        $this->expectException(ViteException::class);
        $this->expectExceptionCode($exceptionCode);
        $this->createViteService()->addAssetsFromManifest($manifestFile, $entry);
    }

    /**
     * @test
     */
    public function getAssetPathFromManifest(): void
    {
        $fixtureDir = realpath(__DIR__ . '/../../Fixtures') . '/';
        $manifestDir = realpath(__DIR__ . '/../../Fixtures/ValidManifest') . '/';
        self::assertEquals(
            $manifestDir . 'assets/Main-973bb662.css',
            $this->createViteService()->getAssetPathFromManifest($fixtureDir . 'ValidManifest/manifest.json', 'Main.css')
        );
    }

    /**
     * @test
     */
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

    /**
     * @test
     * @dataProvider getAssetPathFromManifestErrorHandlingDataProvider
     */
    public function getAssetPathFromManifestErrorHandling(string $manifestFile, string $entry, int $exceptionCode): void
    {
        $this->expectException(ViteException::class);
        $this->expectExceptionCode($exceptionCode);

        $this->createViteService()->getAssetPathFromManifest($manifestFile, $entry);
    }

    private function createViteService(
        AssetCollector $assetCollector = null,
        string $defaultManifest = '_assets/vite/.vite/manifest.json',
        string $useDevServer = 'auto',
        string $devServerUri = 'auto'
    ) {
        $assetCollector ??= new AssetCollector();

        $fixtureDir = realpath(__DIR__ . '/../../Fixtures') . '/';
        $packageManager = $this->createStub(PackageManager::class);
        $packageManager
            ->method('resolvePackagePath')
            ->willReturnMap([
                [
                    'EXT:test_extension/Resources/Private/JavaScript/Main.js',
                    $fixtureDir . 'test_extension/Resources/Private/JavaScript/Main.js',
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

        $extensionConfiguration = $this->createStub(ExtensionConfiguration::class);
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
}
