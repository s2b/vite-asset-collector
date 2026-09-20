<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Context;

use Psr\Http\Message\UriInterface;

final readonly class ViteContext
{
    public function __construct(
        private bool $useDevServer,
        private UriInterface $devServer,
    ) {}

    public function useDevServer(): bool
    {
        return $this->useDevServer;
    }

    public function getDevServer(): UriInterface
    {
        return $this->devServer;
    }
}
