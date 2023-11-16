<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Unit\Service;

use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Service\ViteService;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ViteServiceTest extends UnitTestCase
{
    private ?ViteService $viteService;
    private ?AssetCollector $assetCollector;

    public function setUp(): void
    {
        parent::setUp();
        $this->assetCollector = new AssetCollector();

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

        $this->viteService = new ViteService(
            new NullFrontend('manifest'),
            $this->assetCollector,
            $packageManager
        );
    }

    public function tearDown(): void
    {
        $this->assetCollector = $this->viteService = null;
        parent::tearDown();
    }

    /**
     * @test
     */
    public function determineDevServer(): void
    {
        $request = new ServerRequest(new Uri('https://localhost/path/to/file'));
        self::assertEquals(
            'https://localhost:5173',
            (string)$this->viteService->determineDevServer($request)
        );
    }

    /**
     * @test
     */
    public function determineEntrypointFromManifest(): void
    {
        self::assertEquals(
            'Main.js',
            $this->viteService->determineEntrypointFromManifest(
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
        $this->viteService->determineEntrypointFromManifest(
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
        $this->viteService->determineEntrypointFromManifest(
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
        $this->viteService->addAssetsFromDevServer(
            new Uri('https://localhost:5173'),
            $entry,
            $options,
            ['async' => true, 'otherAttribute' => 'otherValue']
        );

        self::assertEquals(
            $javaScripts,
            $this->assetCollector->getJavaScripts(false)
        );
        self::assertEquals(
            $priorityJavaScripts,
            $this->assetCollector->getJavaScripts(true)
        );
    }

    public static function addAssetsFromManifestDataProvider(): array
    {
        $fixtureDir = realpath(__DIR__ . '/../../Fixtures') . '/';
        $manifestDir = realpath(__DIR__ . '/../../Fixtures/ValidManifest') . '/';
        $manifestFile = $fixtureDir . 'ValidManifest/manifest.json';
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
        $this->viteService->addAssetsFromManifest(
            $manifestFile,
            $entry,
            $addCss,
            $options,
            ['async' => true, 'otherAttribute' => 'otherValue'],
            ['media' => 'print', 'disabled' => true]
        );

        self::assertEquals(
            $javaScripts,
            $this->assetCollector->getJavaScripts(false)
        );
        self::assertEquals(
            $priorityJavaScripts,
            $this->assetCollector->getJavaScripts(true)
        );
        self::assertEquals(
            $styleSheets,
            $this->assetCollector->getStyleSheets(false)
        );
        self::assertEquals(
            $priorityStyleSheets,
            $this->assetCollector->getStyleSheets(true)
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
        $this->viteService->addAssetsFromManifest($manifestFile, $entry);
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
            $this->viteService->getAssetPathFromManifest($fixtureDir . 'ValidManifest/manifest.json', 'Main.css')
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
            $this->viteService->getAssetPathFromManifest(
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

        $this->viteService->getAssetPathFromManifest($manifestFile, $entry);
    }
}
