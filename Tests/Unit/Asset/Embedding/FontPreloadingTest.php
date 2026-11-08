<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Unit\Asset\Embedding;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Asset\Embedding\FontPreloading;
use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FontPreloadingTest extends UnitTestCase
{
    /**
     * @return array<string, array{preloading: FontPreloading, file: string, expected: bool}>
     */
    public static function appliesToDataProvider(): array
    {
        return [
            'default with woff2' => [
                'preloading' => new FontPreloading(),
                'file' => 'assets/Font-57f5650e.woff2',
                'expected' => true,
            ],
            'default with woff' => [
                'preloading' => new FontPreloading(),
                'file' => 'assets/Font-57f5650e.woff',
                'expected' => false,
            ],
            'matching format' => [
                'preloading' => new FontPreloading(formats: ['woff2', 'woff']),
                'file' => 'assets/Font-57f5650e.woff',
                'expected' => true,
            ],
            'all formats' => [
                'preloading' => new FontPreloading(formats: []),
                'file' => 'assets/Font-57f5650e.ttf',
                'expected' => true,
            ],
            'disabled' => [
                'preloading' => new FontPreloading(preload: false),
                'file' => 'assets/Font-57f5650e.woff2',
                'expected' => false,
            ],
        ];
    }

    #[Test]
    #[DataProvider('appliesToDataProvider')]
    public function appliesTo(FontPreloading $preloading, string $file, bool $expected): void
    {
        self::assertSame($expected, $preloading->appliesTo(OutputFile::create($file)));
    }
}
