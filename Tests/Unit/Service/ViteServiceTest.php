<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Unit\Service;

use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Service\ViteService;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
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
        $this->viteService = new ViteService($this->assetCollector);
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

    public static function addAssetsFromDevServerDataProvider(): array
    {
        return [
            'withoutPriority' => [
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
        ];
    }

    /**
     * @test
     * @dataProvider addAssetsFromDevServerDataProvider
     */
    public function addAssetsFromDevServer(array $options, array $javaScripts, array $priorityJavaScripts): void
    {
        $this->viteService->addAssetsFromDevServer(
            new Uri('https://localhost:5173'),
            'path/to/Main.js',
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
        $manifestDir = realpath(__DIR__ . '/../../Fixtures/ValidManifest') . '/';
        $manifestFile = $manifestDir . 'manifest.json';
        return [
            'withoutCss' => [
                $manifestFile,
                [],
                false,
                [
                    'vite:Main.js' => [
                        'source' => $manifestDir . 'assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => [],
                    ],
                ],
                [],
                [],
                [],
            ],
            'withCss' => [
                $manifestFile,
                [],
                true,
                [
                    'vite:Main.js' => [
                        'source' => $manifestDir . 'assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => [],
                    ],
                ],
                [],
                [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => $manifestDir . 'assets/Main-973bb662.css',
                        'attributes' => ['media' => 'print'],
                        'options' => [],
                    ],
                ],
                [],
            ],
            'withCssAndPriority' => [
                $manifestFile,
                ['priority' => true],
                true,
                [],
                [
                    'vite:Main.js' => [
                        'source' => $manifestDir . 'assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async', 'otherAttribute' => 'otherValue'],
                        'options' => ['priority' => true],
                    ],
                ],
                [],
                [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => $manifestDir . 'assets/Main-973bb662.css',
                        'attributes' => ['media' => 'print'],
                        'options' => ['priority' => true],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider addAssetsFromManifestDataProvider
     */
    public function addAssetsFromManifest(
        string $manifestFile,
        array $options,
        bool $addCss,
        array $javaScripts,
        array $priorityJavaScripts,
        array $styleSheets,
        array $priorityStyleSheets
    ): void {
        $this->viteService->addAssetsFromManifest(
            $manifestFile,
            'Main.js',
            $addCss,
            $options,
            ['async' => true, 'otherAttribute' => 'otherValue'],
            ['media' => 'print']
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
}
