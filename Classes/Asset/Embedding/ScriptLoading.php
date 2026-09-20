<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset\Embedding;

enum ScriptLoading: string
{
    case Module = 'module';
    case Defer = 'defer';
    case Async = 'async';
    case Blocking = 'blocking';
}
