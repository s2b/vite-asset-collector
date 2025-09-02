<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Event;

final class ModifyUseDevServerEvent
{
    public function __construct(private string|bool $useDevServer) {}

    public function getUseDevServer(): string|bool
    {
        return $this->useDevServer;
    }

    public function setUseDevServer(string|bool $getUseDevServer): void
    {
        $this->useDevServer = $getUseDevServer;
    }
}
