<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\ViewHelpers\Asset;

use Praetorius\ViteAssetCollector\Exception\ViteException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

final class ViteViewHelperTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/vite_asset_collector',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/vite_asset_collector/Tests/Fixtures' => 'fileadmin/Fixtures/',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'useDevServer' => '0',
            'devServerUri' => 'https://localhost:5173',
            'defaultManifest' => 'fileadmin/Fixtures/DefaultManifest/manifest.json',
        ]);
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
        $assetCollector = $this->get(AssetCollector::class);

        $context = $this->createRenderingContext();
        $context->getTemplatePaths()->setTemplateSource($template);
        (new TemplateView($context))->render();

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
    public function renderWithDevServer(): void
    {
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'useDevServer' => '1',
            'devServerUri' => 'https://localhost:5173',
        ]);

        $assetCollector = $this->get(AssetCollector::class);

        $context = $this->createRenderingContext();
        $context->getTemplatePaths()->setTemplateSource('<vac:asset.vite manifest="fileadmin/Fixtures/ValidManifest/manifest.json" entry="Main.js" />');
        (new TemplateView($context))->render();

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
            $assetCollector->getJavaScripts(false)
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

        $context = $this->createRenderingContext();
        $context->getTemplatePaths()->setTemplateSource('<vac:asset.vite entry="Default.js" />');

        $this->expectException(ViteException::class);
        $this->expectExceptionCode(1684528724);
        (new TemplateView($context))->render();
    }

    protected function createRenderingContext(): RenderingContextInterface
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getViewHelperResolver()->addNamespace('vac', 'Praetorius\\ViteAssetCollector\\ViewHelpers');
        $context->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
        );
        return $context;
    }
}
