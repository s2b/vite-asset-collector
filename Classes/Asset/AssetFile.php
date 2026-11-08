<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset;

final readonly class AssetFile
{
    private function __construct(
        public string $locator,
        public string $extension,
        public AssetType $type,
    ) {}

    public static function create(string $locator): self
    {
        $extension = strtolower(pathinfo($locator, PATHINFO_EXTENSION));
        return new self(
            locator: $locator,
            extension: $extension,
            type: AssetType::fromFileExtension($extension),
        );
    }
}
