<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset;

use Praetorius\ViteAssetCollector\Asset\Manifest\Manifest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

interface AssetRendererInterface
{
    public function renderDevAsset(Asset $asset, UriInterface $devServerUri, ServerRequestInterface $request): void;

    public function renderAsset(Asset $asset, Manifest $manifest, ServerRequestInterface $request): void;
}
