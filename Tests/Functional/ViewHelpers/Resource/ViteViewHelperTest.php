<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\ViewHelpers\Resource;

use Praetorius\ViteAssetCollector\Exception\ViteException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
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

    public function setUp(): void
    {
        parent::setUp();

        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'defaultManifest' => 'fileadmin/Fixtures/DefaultManifest/manifest.json',
        ]);

        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->getViewHelperResolver()->addNamespace(
            'vac',
            'Praetorius\\ViteAssetCollector\\ViewHelpers'
        );
    }

    public function tearDown(): void
    {
        $this->view = null;
        parent::tearDown();
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

    /**
     * @test
     * @dataProvider renderDataProvider
     */
    public function render(
        string $template,
        string $assetUri
    ): void {
        $this->view->setTemplateSource($template);
        self::assertEquals($assetUri, $this->view->render());
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
            '<vac:resource.vite file="Default.js" />'
        );

        $this->expectException(ViteException::class);
        $this->expectExceptionCode(1684528724);
        $this->view->render();
    }
}
