<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset\Manifest;

use Praetorius\ViteAssetCollector\Asset\AssetFile;

final readonly class ManifestChunk
{
    /**
     * @param OutputFile[] $assets
     * @param OutputFile[] $css
     */
    public function __construct(
        public string $identifier,
        public ?string $name,
        public ?AssetFile $src,
        public OutputFile $file,
        public bool $isEntry,
        public bool $isDynamicEntry,
        public array $assets,
        public array $css,
        public array $imports,
        public array $dynamicImports,
    ) {}
}
