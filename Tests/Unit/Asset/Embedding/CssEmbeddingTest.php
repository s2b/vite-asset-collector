<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Unit\Asset\Embedding;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Asset\Embedding\CssEmbedding;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CssEmbeddingTest extends UnitTestCase
{
    public static function getTagAttributesDataProvider(): array
    {
        return [
            [
                'cssEmbedding' => new CssEmbedding(),
                'expected' => [],
            ],
            [
                'cssEmbedding' => new CssEmbedding(media: 'print'),
                'expected' => ['media' => 'print'],
            ],
            [
                'cssEmbedding' => new CssEmbedding(disabled: true),
                'expected' => ['disabled' => 'disabled'],
            ],
            [
                'cssEmbedding' => new CssEmbedding(media: 'print', additionalAttributes: ['media' => 'screen', 'foo' => 'bar']),
                'expected' => ['media' => 'screen', 'foo' => 'bar'],
            ],
        ];
    }

    #[Test]
    #[DataProvider('getTagAttributesDataProvider')]
    public function getTagAttributes(CssEmbedding $cssEmbedding, array $expected): void
    {
        self::assertSame($expected, $cssEmbedding->getTagAttributes());
    }

    #[Test]
    public function moduleAndNomoduleCombinationThrowsException(): void
    {
        self::expectException(ViteException::class);
        self::expectExceptionCode(1790873152);
        new CssEmbedding(inline: true, disabled: true);
    }
}
