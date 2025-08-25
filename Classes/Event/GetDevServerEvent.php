<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Event;

final class GetDevServerEvent
{
    public function __construct(public string $uri) {}
}
