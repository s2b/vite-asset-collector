<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Unit\Asset\Embedding;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Asset\Embedding\ScriptEmbedding;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ScriptEmbeddingTest extends UnitTestCase
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getTagAttributesDataProvider(): array
    {
        return [
            'module' => [
                'scriptEmbedding' => new ScriptEmbedding(),
                'expected' => ['type' => 'module'],
            ],
            'async module' => [
                'scriptEmbedding' => new ScriptEmbedding(async: true),
                'expected' => ['type' => 'module', 'async' => 'async'],
            ],
            'async classic script' => [
                'scriptEmbedding' => new ScriptEmbedding(module: false, async: true),
                'expected' => ['async' => 'async'],
            ],
            'deferred classic script' => [
                'scriptEmbedding' => new ScriptEmbedding(module: false, defer: true),
                'expected' => ['defer' => 'defer'],
            ],
            'blocking classic script' => [
                'scriptEmbedding' => new ScriptEmbedding(module: false),
                'expected' => [],
            ],
            'nomodule script' => [
                'scriptEmbedding' => new ScriptEmbedding(module: false, defer: true, nomodule: true),
                'expected' => ['defer' => 'defer', 'nomodule' => 'nomodule'],
            ],
            'additional attributes' => [
                'scriptEmbedding' => new ScriptEmbedding(module: false, defer: true, nomodule: true, additionalAttributes: ['foo' => 'bar']),
                'expected' => ['defer' => 'defer', 'nomodule' => 'nomodule', 'foo' => 'bar'],
            ],
        ];
    }

    /**
     * @param array<string, string> $expected
     */
    #[Test]
    #[DataProvider('getTagAttributesDataProvider')]
    public function getTagAttributes(ScriptEmbedding $scriptEmbedding, array $expected): void
    {
        self::assertSame($expected, $scriptEmbedding->getTagAttributes());
    }

    /**
     * @return array<string, array{scriptEmbedding: ScriptEmbedding, expected: array<string, string>}>
     */
    public static function getDevServerTagAttributesDataProvider(): array
    {
        return [
            'module' => [
                'scriptEmbedding' => new ScriptEmbedding(),
                'expected' => ['type' => 'module'],
            ],
            'async module' => [
                'scriptEmbedding' => new ScriptEmbedding(async: true),
                'expected' => ['type' => 'module', 'async' => 'async'],
            ],
            'async classic script' => [
                'scriptEmbedding' => new ScriptEmbedding(module: false, async: true),
                'expected' => ['type' => 'module', 'async' => 'async'],
            ],
            'deferred classic script' => [
                'scriptEmbedding' => new ScriptEmbedding(module: false, defer: true),
                'expected' => ['type' => 'module'],
            ],
            'blocking classic script' => [
                'scriptEmbedding' => new ScriptEmbedding(module: false),
                'expected' => ['type' => 'module'],
            ],
            'additional attributes' => [
                'scriptEmbedding' => new ScriptEmbedding(module: false, defer: true, additionalAttributes: ['foo' => 'bar']),
                'expected' => ['type' => 'module', 'foo' => 'bar'],
            ],
            'type cannot be overwritten by additional attributes' => [
                'scriptEmbedding' => new ScriptEmbedding(additionalAttributes: ['type' => 'text/javascript']),
                'expected' => ['type' => 'module'],
            ],
        ];
    }

    /**
     * @param array<string, string> $expected
     */
    #[Test]
    #[DataProvider('getDevServerTagAttributesDataProvider')]
    public function getDevServerTagAttributes(ScriptEmbedding $scriptEmbedding, array $expected): void
    {
        self::assertSame($expected, $scriptEmbedding->getDevServerTagAttributes());
    }

    /**
     * @return array<string, array{arguments: array<string, bool>, expectedCode: int}>
     */
    public static function invalidCombinationThrowsExceptionDataProvider(): array
    {
        return [
            'module and nomodule' => [
                'arguments' => ['nomodule' => true],
                'expectedCode' => 1790873151,
            ],
            'module and defer' => [
                'arguments' => ['defer' => true],
                'expectedCode' => 1793918609,
            ],
            'async and defer' => [
                'arguments' => ['module' => false, 'async' => true, 'defer' => true],
                'expectedCode' => 1793918610,
            ],
        ];
    }

    /**
     * @param array<string, bool> $arguments
     */
    #[Test]
    #[DataProvider('invalidCombinationThrowsExceptionDataProvider')]
    public function invalidCombinationThrowsException(array $arguments, int $expectedCode): void
    {
        self::expectException(ViteException::class);
        self::expectExceptionCode($expectedCode);
        new ScriptEmbedding(...$arguments);
    }
}
