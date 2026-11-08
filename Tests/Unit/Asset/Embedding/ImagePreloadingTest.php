<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Unit\Asset\Embedding;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Asset\Embedding\ImagePreloading;
use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ImagePreloadingTest extends UnitTestCase
{
    /**
     * @return array<string, array{preloading: ImagePreloading, file: string, expected: bool}>
     */
    public static function appliesToDataProvider(): array
    {
        return [
            'default' => [
                'preloading' => new ImagePreloading(),
                'file' => 'assets/Image-57f5650e.svg',
                'expected' => false,
            ],
            'all formats' => [
                'preloading' => new ImagePreloading(preload: true),
                'file' => 'assets/Image-57f5650e.png',
                'expected' => true,
            ],
            'matching format' => [
                'preloading' => new ImagePreloading(preload: true, formats: ['svg']),
                'file' => 'assets/Image-57f5650e.svg',
                'expected' => true,
            ],
            'other format' => [
                'preloading' => new ImagePreloading(preload: true, formats: ['svg']),
                'file' => 'assets/Image-57f5650e.png',
                'expected' => false,
            ],
            'disabled with matching format' => [
                'preloading' => new ImagePreloading(formats: ['svg']),
                'file' => 'assets/Image-57f5650e.svg',
                'expected' => false,
            ],
        ];
    }

    #[Test]
    #[DataProvider('appliesToDataProvider')]
    public function appliesTo(ImagePreloading $preloading, string $file, bool $expected): void
    {
        self::assertSame($expected, $preloading->appliesTo(OutputFile::create($file)));
    }
}
