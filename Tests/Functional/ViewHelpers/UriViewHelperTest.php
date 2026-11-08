<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Tests\Functional\AbstractViteFunctionalTestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

final class UriViewHelperTest extends AbstractViteFunctionalTestCase
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function renderDataProvider(): array
    {
        return [
            'basic' => [
                'template' => '<vite:uri manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" file="Main.css" />',
                'assetUri' => 'fileadmin/Fixtures/ValidManifest/assets/Main-973bb662.css',
            ],
            'defaultManifest' => [
                'template' => '<vite:uri file="Default.css" />',
                'assetUri' => 'fileadmin/Fixtures/DefaultManifest/assets/Default-973bb662.css',
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

        self::assertSame($assetUri, $this->renderTemplate($template));
    }

    #[Test]
    public function renderWithDevServer(): void
    {
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'useDevServer' => '1',
            'devServerUri' => 'https://localhost:5173',
            'defaultManifest' => 'fileadmin/Fixtures/DefaultManifest/.vite/manifest.json',
        ]);

        self::assertSame(
            'https://localhost:5173/path/to/file.jpg',
            $this->renderTemplate('<vite:uri file="path/to/file.jpg" />', true),
        );
    }

    #[Test]
    public function renderWithoutManifest(): void
    {
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'useDevServer' => '0',
            'devServerUri' => 'https://localhost:5173',
            'defaultManifest' => '',
        ]);

        $this->expectException(ViteException::class);
        $this->expectExceptionCode(1684528724);
        $this->renderTemplate('<vite:uri file="Default.js" />');
    }
}
