<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Event;

final class ModifyDevServerEnabledEvent
{
    public function __construct(private string|bool $enabled) {}

    public function isEnabled(): string|bool
    {
        return $this->enabled;
    }

    public function setEnabled(string|bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}
