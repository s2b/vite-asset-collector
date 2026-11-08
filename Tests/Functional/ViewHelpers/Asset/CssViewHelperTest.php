<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\ViewHelpers\Asset;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Tests\Functional\AbstractViteFunctionalTestCase;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3Fluid\Fluid\Core\Parser\Exception as ParserException;
use TYPO3Fluid\Fluid\Core\ViewHelper\InvalidArgumentException;

final class CssViewHelperTest extends AbstractViteFunctionalTestCase
{
    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'vite_asset_collector' => [
                'useDevServer' => '0',
                'devServerUri' => 'https://localhost:5173',
                'defaultManifest' => 'fileadmin/Fixtures/DefaultManifest/.vite/manifest.json',
            ],
        ],
    ];

    #[Test]
    public function disabledAttribute(): void
    {
        $this->renderTemplate('
            <vite:asset entry="Default.js">
                <vite:asset.css disabled="{true}" priority="{false}" />
            </vite:asset>
        ');
        $styleSheets = $this->get(AssetCollector::class)->getStyleSheets(false);
        self::assertCount(1, $styleSheets);
        self::assertSame(['disabled' => 'disabled'], array_values($styleSheets)[0]['attributes']);
        self::assertSame([], $this->get(AssetCollector::class)->getStyleSheets(true));
    }

    #[Test]
    public function multipleUsageThrowsException(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionCode(1794141766);
        $this->renderTemplate('
            <vite:asset entry="Default.js">
                <vite:asset.css media="screen" />
                <vite:asset.css media="print" />
            </vite:asset>
        ');
    }

    #[Test]
    public function configurationDoesNotLeakIntoSubsequentAssets(): void
    {
        $this->renderTemplate('
            <vite:asset entry="Default.js">
                <vite:asset.css ignore="{true}" />
            </vite:asset>
            <vite:asset manifest="fileadmin/Fixtures/ValidManifest/.vite/manifest.json" entry="Main.js" />
        ');
        self::assertSame(
            ['vite:fileadmin/Fixtures/ValidManifest/assets/Main-973bb662.css:' . md5(serialize([]))],
            array_keys($this->get(AssetCollector::class)->getStyleSheets()),
        );
    }

    #[Test]
    public function inlineAndDisabledThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1793528982);
        $this->renderTemplate('
            <vite:asset entry="Default.js">
                <vite:asset.css inline="{true}" disabled="{true}" />
            </vite:asset>
        ');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidParentDataProvider(): array
    {
        return [
            'without parent' => ['<vite:asset.css inline="{true}" />'],
            'nested in other ViewHelper' => ['
                <vite:asset entry="Default.js">
                    <f:if condition="{true}"><vite:asset.css inline="{true}" /></f:if>
                </vite:asset>
            '],
        ];
    }

    #[Test]
    #[DataProvider('invalidParentDataProvider')]
    public function invalidParentThrowsException(string $template): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionCode(1793529908);
        $this->renderTemplate($template);
    }

    /**
     * @return array<string, array{argument: string}>
     */
    public static function deprecatedArgumentOfParentThrowsExceptionDataProvider(): array
    {
        return [
            'inlineCss' => ['argument' => 'inlineCss="{true}"'],
            'priority' => ['argument' => 'priority="{true}"'],
            'addCss' => ['argument' => 'addCss="{false}"'],
            'cssTagAttributes' => ['argument' => 'cssTagAttributes="{foo: \'bar\'}"'],
            'scriptTagAttributes' => ['argument' => 'scriptTagAttributes="{foo: \'bar\'}"'],
        ];
    }

    #[Test]
    #[IgnoreDeprecations]
    #[DataProvider('deprecatedArgumentOfParentThrowsExceptionDataProvider')]
    public function deprecatedArgumentOfParentThrowsException(string $argument): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionCode(1794140579);
        $this->renderTemplate('
            <vite:asset entry="Default.js" ' . $argument . '>
                <vite:asset.css />
            </vite:asset>
        ');
    }

    #[Test]
    #[IgnoreDeprecations]
    public function deprecatedUseNonceArgumentOfParentIsAllowed(): void
    {
        $this->renderTemplate('
            <vite:asset entry="Default.js" useNonce="{true}">
                <vite:asset.css />
            </vite:asset>
        ');
        self::assertNotSame([], $this->get(AssetCollector::class)->getJavaScripts());
    }
}
