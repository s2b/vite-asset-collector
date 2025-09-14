<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Event;

final class ModifyUseDevServerEvent
{
    public function __construct(
        private readonly string|bool $configuration,
        private bool $resolvedValue,
    ) {}

    public function getConfiguration(): string|bool
    {
        return $this->configuration;
    }

    public function setResolvedValue(bool $resolvedValue)
    {
        $this->resolvedValue = $resolvedValue;
    }

    public function getResolvedValue(): bool
    {
        return $this->resolvedValue;
    }
}
