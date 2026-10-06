<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset;

use Praetorius\ViteAssetCollector\Asset\Manifest\Manifest;
use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;
use Psr\Http\Message\UriInterface;

/**
 * @internal Still subject to change, see TODO
 */
interface AssetUriGeneratorInterface
{
    public function generateDevUri(AssetFile $file, UriInterface $devServerBase): UriInterface;

    // TODO change return type back to UriInterface
    public function generateUri(AssetFile|OutputFile $file, Manifest $manifest, ?UriInterface $base = null): string;
}
