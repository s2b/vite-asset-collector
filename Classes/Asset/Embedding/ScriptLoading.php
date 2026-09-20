<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset\Embedding;

enum ScriptLoading: string
{
    case Module = 'module';
    case Defer = 'defer';
    case Async = 'async';
    case Blocking = 'blocking';

    public function getTagAttributes(): array
    {
        return match ($this) {
            self::Module => ['type' => 'module'],
            self::Defer => ['defer' => true],
            self::Async => ['async' => true],
            self::Blocking => [],
        };
    }
}
