<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\ViewHelpers\Asset;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Tests\Functional\AbstractViteFunctionalTestCase;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3Fluid\Fluid\Core\Parser\Exception as ParserException;

final class FontViewHelperTest extends AbstractViteFunctionalTestCase
{
    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'vite_asset_collector' => [
                'useDevServer' => '0',
                'devServerUri' => 'https://localhost:5173',
                'defaultManifest' => 'fileadmin/Fixtures/ReferencedAssets/.vite/manifest.json',
            ],
        ],
    ];

    /**
     * @return array<string, array{arguments: string, expected: string[]}>
     */
    public static function preloadingDataProvider(): array
    {
        return [
            'default' => [
                'arguments' => '',
                'expected' => [
                    '<link rel="preload" href="fileadmin/Fixtures/ReferencedAssets/assets/Font-57f5650e.woff2" as="font" type="font/woff2" crossorigin="anonymous">',
                ],
            ],
            'disabled' => [
                'arguments' => 'preload="{false}"',
                'expected' => [
                ],
            ],
            'multiple formats' => [
                'arguments' => 'formats="woff2, woff"',
                'expected' => [
                    '<link rel="preload" href="fileadmin/Fixtures/ReferencedAssets/assets/Font-57f5650e.woff2" as="font" type="font/woff2" crossorigin="anonymous">',
                    '<link rel="preload" href="fileadmin/Fixtures/ReferencedAssets/assets/Font-57f5650e.woff" as="font" type="font/woff" crossorigin="anonymous">',
                ],
            ],
            'all formats' => [
                'arguments' => 'formats=""',
                'expected' => [
                    '<link rel="preload" href="fileadmin/Fixtures/ReferencedAssets/assets/Font-57f5650e.woff2" as="font" type="font/woff2" crossorigin="anonymous">',
                    '<link rel="preload" href="fileadmin/Fixtures/ReferencedAssets/assets/Font-57f5650e.woff" as="font" type="font/woff" crossorigin="anonymous">',
                    '<link rel="preload" href="fileadmin/Fixtures/ReferencedAssets/assets/Font-57f5650e.ttf" as="font" type="font/ttf" crossorigin="anonymous">',
                ],
            ],
        ];
    }

    /**
     * @param string[] $expected
     */
    #[Test]
    #[DataProvider('preloadingDataProvider')]
    public function preloading(string $arguments, array $expected): void
    {
        $this->renderTemplate('
            <vite:asset entry="Main.js">
                <vite:asset.font ' . $arguments . ' />
            </vite:asset>
        ');
        $headerDataAccessor = (new \ReflectionClass(PageRenderer::class))->getProperty('headerData');
        self::assertSame($expected, $headerDataAccessor->getValue($this->get(PageRenderer::class)));
    }

    #[Test]
    public function multipleUsageThrowsException(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionCode(1794142769);
        $this->renderTemplate('
            <vite:asset entry="Main.js">
                <vite:asset.font />
                <vite:asset.font />
            </vite:asset>
        ');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidParentDataProvider(): array
    {
        return [
            'without parent' => ['<vite:asset.font />'],
            'nested in other ViewHelper' => ['
                <vite:asset entry="Main.js">
                    <f:if condition="{true}"><vite:asset.font /></f:if>
                </vite:asset>
            '],
        ];
    }

    #[Test]
    #[DataProvider('invalidParentDataProvider')]
    public function invalidParentThrowsException(string $template): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionCode(1794142768);
        $this->renderTemplate($template);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function deprecatedArgumentOfParentThrowsException(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionCode(1794142770);
        $this->renderTemplate('
            <vite:asset entry="Main.js" priority="{true}">
                <vite:asset.font />
            </vite:asset>
        ');
    }
}
