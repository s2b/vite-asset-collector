<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Unit\Asset\Embedding;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Asset\Embedding\ScriptEmbedding;
use Praetorius\ViteAssetCollector\Asset\Embedding\ScriptLoading;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ScriptEmbeddingTest extends UnitTestCase
{
    public static function getTagAttributesDataProvider(): array
    {
        return [
            [
                'scriptEmbedding' => new ScriptEmbedding(),
                'expected' => ['type' => 'module'],
            ],
            [
                'scriptEmbedding' => new ScriptEmbedding(loading: ScriptLoading::Module),
                'expected' => ['type' => 'module'],
            ],
            [
                'scriptEmbedding' => new ScriptEmbedding(loading: ScriptLoading::Async),
                'expected' => ['async' => 'async'],
            ],
            [
                'scriptEmbedding' => new ScriptEmbedding(loading: ScriptLoading::Defer),
                'expected' => ['defer' => 'defer'],
            ],
            [
                'scriptEmbedding' => new ScriptEmbedding(loading: ScriptLoading::Blocking),
                'expected' => [],
            ],
            [
                'scriptEmbedding' => new ScriptEmbedding(nomodule: true, loading: ScriptLoading::Defer),
                'expected' => ['defer' => 'defer', 'nomodule' => 'nomodule'],
            ],
            [
                'scriptEmbedding' => new ScriptEmbedding(nomodule: true, loading: ScriptLoading::Defer, additionalAttributes: ['foo' => 'bar']),
                'expected' => ['defer' => 'defer', 'nomodule' => 'nomodule', 'foo' => 'bar'],
            ],
        ];
    }

    #[Test]
    #[DataProvider('getTagAttributesDataProvider')]
    public function getTagAttributes(ScriptEmbedding $scriptEmbedding, array $expected): void
    {
        self::assertSame($expected, $scriptEmbedding->getTagAttributes());
    }

    #[Test]
    public function moduleAndNomoduleCombinationThrowsException(): void
    {
        self::expectException(ViteException::class);
        self::expectExceptionCode(1790873151);
        new ScriptEmbedding(nomodule: true);
    }
}
