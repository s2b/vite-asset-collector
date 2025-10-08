<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\View\TemplateView;

final class AssetViewHelperTest extends FunctionalTestCase
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
            'defaultManifest' => 'fileadmin/Fixtures/DefaultManifest/.vite/manifest.json',
        ]);
    }

    public static function renderDataProvider(): array
    {
        $manifestDir = 'fileadmin/Fixtures/';
        return [
            'basic' => [
                'template' => '<vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js" />',
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['priority' => false, 'useNonce' => false, 'external' => true],
                    ],
                ],
                'styleSheets' => [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['priority' => false, 'useNonce' => false, 'external' => true],
                    ],
                ],
            ],
            'withoutCss' => [
                'template' => '<vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js" addCss="0" />',
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['priority' => false, 'useNonce' => false, 'external' => true],
                    ],
                ],
            ],
            'defaultManifest' => [
                'template' => '<vite:asset entry="Default.js" />',
                'javaScripts' => [
                    'vite:Default.js' => [
                        'source' => $manifestDir . 'DefaultManifest/assets/Default-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['priority' => false, 'useNonce' => false, 'external' => true],
                    ],
                ],
                'styleSheets' => [
                    'vite:Default.js:assets/Default-973bb662.css' => [
                        'source' => $manifestDir . 'DefaultManifest/assets/Default-973bb662.css',
                        'attributes' => [],
                        'options' => ['priority' => false, 'useNonce' => false, 'external' => true],
                    ],
                ],
            ],
            'autoEntry' => [
                'template' => '<vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" />',
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['priority' => false, 'useNonce' => false, 'external' => true],
                    ],
                ],
                'styleSheets' => [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['priority' => false, 'useNonce' => false, 'external' => true],
                    ],
                ],
            ],
            'withAttributes' => [
                'template' => '<vite:asset
                    manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json"
                    entry="Main.js"
                    scriptTagAttributes="{async: 1}"
                    cssTagAttributes="{media: \'print\'}"
                />',
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module', 'async' => 'async'],
                        'options' => ['priority' => false, 'useNonce' => false, 'external' => true],
                    ],
                ],
                'styleSheets' => [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => ['media' => 'print'],
                        'options' => ['priority' => false, 'useNonce' => false, 'external' => true],
                    ],
                ],
            ],
            'withPriority' => [
                'template' => '<vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js" priority="1" />',
                'priorityJavaScripts' => [
                    'vite:Main.js' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['priority' => true, 'useNonce' => false, 'external' => true],
                    ],
                ],
                'priorityStyleSheets' => [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['priority' => true, 'useNonce' => false, 'external' => true],
                    ],
                ],
            ],
            'withNonce' => [
                'template' => '<vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js" useNonce="1" />',
                'javaScripts' => [
                    'vite:Main.js' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-4483b920.js',
                        'attributes' => ['type' => 'module'],
                        'options' => ['priority' => false, 'useNonce' => true, 'external' => true],
                    ],
                ],
                'styleSheets' => [
                    'vite:Main.js:assets/Main-973bb662.css' => [
                        'source' => $manifestDir . 'ValidManifest/assets/Main-973bb662.css',
                        'attributes' => [],
                        'options' => ['priority' => false, 'useNonce' => true, 'external' => true],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('renderDataProvider')]
    public function render(
        string $template,
        array $javaScripts = [],
        array $priorityJavaScripts = [],
        array $styleSheets = [],
        array $priorityStyleSheets = []
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

    #[Test]
    public function renderWithDevServer(): void
    {
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'useDevServer' => '1',
            'devServerUri' => 'https://localhost:5173',
        ]);

        $assetCollector = $this->get(AssetCollector::class);

        $context = $this->createRenderingContext();
        $context->getTemplatePaths()->setTemplateSource('<vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js" />');
        (new TemplateView($context))->render();

        self::assertEquals(
            [
                'vite' => [
                    'source' => 'https://localhost:5173/@vite/client',
                    'attributes' => ['type' => 'module'],
                    'options' => ['priority' => false, 'useNonce' => false, 'external' => true],
                ],
                'vite:Main.js' => [
                    'source' => 'https://localhost:5173/Main.js',
                    'attributes' => ['type' => 'module'],
                    'options' => ['priority' => false, 'useNonce' => false, 'external' => true],
                ],
            ],
            $assetCollector->getJavaScripts(false)
        );
    }

    #[Test]
    public function renderWithoutManifest()
    {
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'defaultManifest' => '',
        ]);

        $context = $this->createRenderingContext();
        $context->getTemplatePaths()->setTemplateSource('<vite:asset entry="Default.js" />');

        $this->expectException(ViteException::class);
        $this->expectExceptionCode(1684528724);
        (new TemplateView($context))->render();
    }

    protected function createRenderingContext(): RenderingContextInterface
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getViewHelperResolver()->addNamespace('vite', 'Praetorius\\ViteAssetCollector\\ViewHelpers');

        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('extbase', new ExtbaseRequestParameters());

        $context->setAttribute(ServerRequestInterface::class, $request);

        return $context;
    }
}
