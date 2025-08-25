<?php

namespace Praetorius\ViteAssetCollector\Event;

final class UseDevServerEvent
{
    public function __construct(public string|bool $useDevServer) {}
}
