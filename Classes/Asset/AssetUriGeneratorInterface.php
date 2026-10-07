<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset;

use Praetorius\ViteAssetCollector\Asset\Manifest\Manifest;
use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

interface AssetUriGeneratorInterface
{
    public function generateDevUri(AssetFile $file, UriInterface $devServerBase, ?ServerRequestInterface $request): UriInterface;

    public function generateUri(AssetFile|OutputFile $file, Manifest $manifest, ?ServerRequestInterface $request): UriInterface;
}
