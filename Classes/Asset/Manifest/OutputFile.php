<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset\Manifest;

use Praetorius\ViteAssetCollector\Asset\AssetType;

final readonly class OutputFile
{
    private function __construct(
        public string $locator,
        public string $extension,
        public AssetType $type,
    ) {}

    public static function create(string $locator, ?AssetType $type = null): self
    {
        $extension = pathinfo($locator, PATHINFO_EXTENSION);
        return new self(
            locator: $locator,
            extension: $extension,
            type: $type ?? AssetType::fromFileExtension($extension),
        );
    }
}
