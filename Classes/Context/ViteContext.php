<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Context;

use Psr\Http\Message\UriInterface;

final readonly class ViteContext
{
    public function __construct(
        public bool $useDevServer,
        public UriInterface $devServer,
    ) {}
}
