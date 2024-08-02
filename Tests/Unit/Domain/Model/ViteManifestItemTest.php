<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Domain\Model\ViteManifestItem;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ViteManifestItemTest extends UnitTestCase
{
    public static function isCssDataProvider(): array
    {
        return [
            'Main.js' => [
                new ViteManifestItem('Main.js', 'Main.js', 'assets/Main-4483b920.js', true, false, [], [], [], []),
                false,
            ],
            'Main.css' => [
                new ViteManifestItem('Main.css', 'Main.css', 'assets/Main-4483b920.css', true, false, [], [], [], []),
                true,
            ],
            'Main.scss' => [
                new ViteManifestItem('Main.scss', 'Main.scss', 'assets/Main-4483b920.css', true, false, [], [], [], []),
                true,
            ],
        ];
    }

    #[Test]
    #[DataProvider('isCssDataProvider')]
    public function isCss(ViteManifestItem $item, bool $expected): void
    {
        self::assertEquals(
            $expected,
            $item->isCss()
        );
    }
}
