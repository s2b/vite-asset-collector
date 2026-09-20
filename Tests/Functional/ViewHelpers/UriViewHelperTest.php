<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Context\ViteContext;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\View\TemplateView;

final class UriViewHelperTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/vite_asset_collector',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/vite_asset_collector/Tests/Fixtures' => 'fileadmin/Fixtures/',
    ];

    public static function renderDataProvider(): array
    {
        return [
            'basic' => [
                '<vite:uri manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" file="Main.css" />',
                '/fileadmin/Fixtures/ValidManifest/assets/Main-973bb662.css',
            ],
            'defaultManifest' => [
                '<vite:uri file="Default.css" />',
                '/fileadmin/Fixtures/DefaultManifest/assets/Default-973bb662.css',
            ],
        ];
    }

    #[Test]
    #[DataProvider('renderDataProvider')]
    public function render(
        string $template,
        string $assetUri
    ): void {
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'useDevServer' => '0',
            'devServerUri' => 'https://localhost:5173',
            'defaultManifest' => 'fileadmin/Fixtures/DefaultManifest/.vite/manifest.json',
        ]);

        $context = $this->createRenderingContext(false, new Uri('https://localhost:5173'));
        $context->getTemplatePaths()->setTemplateSource($template);

        self::assertEquals($assetUri, (new TemplateView($context))->render());
    }

    #[Test]
    public function renderWithDevServer(): void
    {
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'useDevServer' => '1',
            'devServerUri' => 'https://localhost:5173',
            'defaultManifest' => 'fileadmin/Fixtures/DefaultManifest/.vite/manifest.json',
        ]);

        $context = $this->createRenderingContext(true, new Uri('https://localhost:5173'));
        $context->getTemplatePaths()->setTemplateSource('<vite:uri file="path/to/file.jpg" />');

        self::assertEquals(
            'https://localhost:5173/path/to/file.jpg',
            (new TemplateView($context))->render(),
        );
    }

    #[Test]
    public function renderWithoutManifest()
    {
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'useDevServer' => '0',
            'devServerUri' => 'https://localhost:5173',
            'defaultManifest' => '',
        ]);

        $context = $this->createRenderingContext(false, new Uri('https://localhost:5173'));
        $context->getTemplatePaths()->setTemplateSource('<vite:uri file="Default.js" />');

        $this->expectException(ViteException::class);
        $this->expectExceptionCode(1684528724);
        (new TemplateView($context))->render();
    }

    protected function createRenderingContext(bool $useDevServer, UriInterface $devServerUri): RenderingContextInterface
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getViewHelperResolver()->addNamespace('vite', 'Praetorius\\ViteAssetCollector\\ViewHelpers');

        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('vite.context', new ViteContext(useDevServer: $useDevServer, devServer: $devServerUri));

        $context->setAttribute(ServerRequestInterface::class, $request);

        return $context;
    }
}
