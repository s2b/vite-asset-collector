<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset;

final readonly class AssetFile
{
    private function __construct(
        public string $locator,
        public AssetType $type,
    ) {}

    public static function create(string $locator): self
    {
        return new self(
            locator: $locator,
            type: AssetType::fromFilePath($locator),
        );
    }
}
