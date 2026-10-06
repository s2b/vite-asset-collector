<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset;

use Praetorius\ViteAssetCollector\Asset\Manifest\Manifest;
use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;

interface AssetPathResolverInterface
{
    public function resolveOutputPath(AssetFile|OutputFile $file, Manifest $manifest, bool $absolute = false): string;

    public function resolveSourcePath(AssetFile $file, bool $absolute = false): string;
}
