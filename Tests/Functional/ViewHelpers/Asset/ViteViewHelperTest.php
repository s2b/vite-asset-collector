<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\ViewHelpers\Asset;

use Praetorius\ViteAssetCollector\Exception\ViteException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ViteViewHelperTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/vite_asset_collector',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/vite_asset_collector/Tests/Fixtures' => 'fileadmin/Fixtures/',
    ];

    private ?StandaloneView $view;
    private ?AssetCollector $assetCollector;

    public function setUp(): void
    {
        parent::setUp();

        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'useDevServer' => '0',
            'devServerUri' => 'https://localhost:5173',
            'defaultManifest' => 'fileadmin/Fixtures/DefaultManifest/manifest.json',
        ]);

        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->getViewHelperResolver()->addNamespace(
            'vac',
            'Praetorius\\ViteAssetCollector\\ViewHelpers'
        );
        $this->assetCollector = $this->get(AssetCollector::class);
    }

    public function tearDown(): void
    {
        $this->view = $this->assetCollector = null;
        parent::tearDown();
    }

    public static function renderDataProvider(): array
    {
        $manifestDir = self::getInstancePath() . '/fileadmin/Fixtures/';
        return [
            'basic' => [
                '<vac:asset.vite manifest="fileadmin/Fixtures/ValidManifest/manifest.json" entry="Main.js" />',
                [
                    'vite:Main.js' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['priority' => false, 'useNonce' => false],
                    ],
                ],
                [],
                [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['priority' => false, 'useNonce' => false],
                    ],
                ],
                [],
            ],
            'withoutCss' => [
                '<vac:asset.vite manifest="fileadmin/Fixtures/ValidManifest/manifest.json" entry="Main.js" addCss="0" />',
                [
                    'vite:Main.js' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['priority' => false, 'useNonce' => false],
                    ],
                ],
                [],
                [],
                [],
            ],
            'defaultManifest' => [
                '<vac:asset.vite entry="Default.js" />',
                [
                    'vite:Default.js' => [
                        'source' => $manifestDir . 'DefaultManifest/assets/Default-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['priority' => false, 'useNonce' => false],
                    ],
                ],
                [],
                [
                    'vite:Default.js:assets/Default-973bb662.css' => [
                        'source' => $manifestDir . 'DefaultManifest/assets/Default-973bb662.css',
                        'attributes' => [],
                        'options' => ['priority' => false, 'useNonce' => false],
                    ],
                ],
                [],
            ],
            'autoEntry' => [
                '<vac:asset.vite manifest="fileadmin/Fixtures/ValidManifest/manifest.json" />',
                [
                    'vite:Main.js' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['priority' => false, 'useNonce' => false],
                    ],
                ],
                [],
                [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['priority' => false, 'useNonce' => false],
                    ],
                ],
                [],
            ],
            'withAttributes' => [
                '<vac:asset.vite
                    manifest="fileadmin/Fixtures/ValidManifest/manifest.json"
                    entry="Main.js"
                    scriptTagAttributes="{async: 1}"
                    cssTagAttributes="{media: \'print\'}"
                />',
                [
                    'vite:Main.js' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async'],
                        'options' => ['priority' => false, 'useNonce' => false],
                    ],
                ],
                [],
                [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => ['media' => 'print'],
                        'options' => ['priority' => false, 'useNonce' => false],
                    ],
                ],
                [],
            ],
            'withPriority' => [
                '<vac:asset.vite manifest="fileadmin/Fixtures/ValidManifest/manifest.json" entry="Main.js" priority="1" />',
                [],
                [
                    'vite:Main.js' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['priority' => true, 'useNonce' => false],
                    ],
                ],
                [],
                [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['priority' => true, 'useNonce' => false],
                    ],
                ],
            ],
            'withNonce' => [
                '<vac:asset.vite manifest="fileadmin/Fixtures/ValidManifest/manifest.json" entry="Main.js" useNonce="1" />',
                [
                    'vite:Main.js' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['priority' => false, 'useNonce' => true],
                    ],
                ],
                [],
                [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['priority' => false, 'useNonce' => true],
                    ],
                ],
                [],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderDataProvider
     */
    public function render(
        string $template,
        array $javaScripts,
        array $priorityJavaScripts,
        array $styleSheets,
        array $priorityStyleSheets
    ): void {
        $this->view->setTemplateSource($template);
        $this->view->render();

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

    /**
     * @test
     */
    public function renderWithDevServer(): void
    {
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'useDevServer' => '1',
            'devServerUri' => 'https://localhost:5173',
        ]);

        $this->view->setTemplateSource(
            '<vac:asset.vite manifest="fileadmin/Fixtures/ValidManifest/manifest.json" entry="Main.js" />'
        );
        $this->view->render();

        self::assertEquals(
            [
                'vite' => [
                    'source' => 'https://localhost:5173/@vite/client',
                    'attributes' => ['type' => 'module'],
                    'options' => ['priority' => false, 'useNonce' => false],
                ],
                'vite:Main.js' => [
                    'source' => 'https://localhost:5173/Main.js',
                    'attributes' => ['type' => 'module'],
                    'options' => ['priority' => false, 'useNonce' => false],
                ],
            ],
            $this->assetCollector->getJavaScripts(false)
        );
    }

    /**
     * @test
     */
    public function renderWithoutManifest()
    {
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'defaultManifest' => '',
        ]);

        $this->view->setTemplateSource(
            '<vac:asset.vite entry="Default.js" />'
        );

        $this->expectException(ViteException::class);
        $this->expectExceptionCode(1684528724);
        $this->view->render();
    }
}
