<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\ViewHelpers\Resource;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\View\TemplateView;

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
            'defaultManifest' => 'fileadmin/Fixtures/DefaultManifest/manifest.json',
        ]);
    }

    public static function renderDataProvider(): array
    {
        return [
            'basic' => [
                '<vac:resource.vite manifest="fileadmin/Fixtures/ValidManifest/manifest.json" file="Main.css" />',
                'fileadmin/Fixtures/ValidManifest/assets/Main-973bb662.css',
            ],
            'defaultManifest' => [
                '<vac:resource.vite file="Default.css" />',
                'fileadmin/Fixtures/DefaultManifest/assets/Default-973bb662.css',
            ],
        ];
    }

    #[Test]
    #[DataProvider('renderDataProvider')]
    public function render(
        string $template,
        string $assetUri
    ): void {
        $context = $this->createRenderingContext();
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertEquals($assetUri, (new TemplateView($context))->render());
    }

    #[Test]
    public function renderWithDevServer(): void
    {
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'useDevServer' => '1',
            'devServerUri' => 'https://localhost:5173',
        ]);

        $context = $this->createRenderingContext();
        $context->getTemplatePaths()->setTemplateSource('<vac:resource.vite file="path/to/file.jpg" />');

        self::assertEquals(
            'https://localhost:5173/path/to/file.jpg',
            (new TemplateView($context))->render(),
        );
    }

    #[Test]
    public function renderWithoutManifest()
    {
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'defaultManifest' => '',
        ]);

        $context = $this->createRenderingContext();
        $context->getTemplatePaths()->setTemplateSource('<vac:resource.vite file="Default.js" />');

        $this->expectException(ViteException::class);
        $this->expectExceptionCode(1684528724);
        (new TemplateView($context))->render();
    }

    protected function createRenderingContext(): RenderingContextInterface
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getViewHelperResolver()->addNamespace('vac', 'Praetorius\\ViteAssetCollector\\ViewHelpers');

        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('extbase', new ExtbaseRequestParameters());

        // TODO remove this when support for TYPO3 v12 is dropped
        if (method_exists($context, 'setRequest')) {
            @$context->setRequest($request);
        }

        $context->setAttribute(ServerRequestInterface::class, $request);

        return $context;
    }
}
