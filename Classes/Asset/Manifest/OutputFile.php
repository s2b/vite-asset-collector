<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset\Manifest;

use Praetorius\ViteAssetCollector\Asset\AssetType;

final readonly class OutputFile
{
    private function __construct(
        public string $locator,
        public AssetType $type,
    ) {}

    public static function create(string $locator, ?AssetType $type = null): self
    {
        return new self(
            locator: $locator,
            type: $type ?? AssetType::fromFilePath($locator),
        );
    }
}
