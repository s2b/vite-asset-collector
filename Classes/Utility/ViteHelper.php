<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Utility;

use Praetorius\ViteAssetCollector\Asset\Asset;
use Praetorius\ViteAssetCollector\Asset\AssetFile;
use Praetorius\ViteAssetCollector\Asset\AssetRendererInterface;
use Praetorius\ViteAssetCollector\Asset\AssetUriGeneratorInterface;
use Praetorius\ViteAssetCollector\Asset\Manifest\ManifestFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

final readonly class ViteHelper
{
    public function __construct(
        private ManifestFactory $manifestFactory,
        private AssetRendererInterface $assetRenderer,
        private AssetUriGeneratorInterface $assetUriGenerator,
    ) {}

    /**
     * Renders the specified asset in the right vite context based on the
     * provided request object. This covers the main use case of the PHP
     * API. For fine-grained control, the AssetRenderer can be used directly.
     */
    public function renderAsset(Asset $asset, ServerRequestInterface $request, ?string $manifestFile = null): void
    {
        $viteContext = $request->getAttribute('vite.context');
        if ($viteContext?->useDevServer()) {
            $this->assetRenderer->renderDevAsset($asset, $viteContext->getDevServer(), $request);
        } else {
            $manifest = $this->manifestFactory->createFromConfiguredPath($manifestFile);
            $this->assetRenderer->renderBuildAsset($asset, $manifest, $request);
        }
    }

    /**
     * Generates an URI to the specified asset file in the right vite context
     * based on the provided request object. This covers the main use case
     * of the PHP API. For fine-grained control, the AssetUriGenerator can be
     * used directly.
     */
    public function generateUri(AssetFile $file, ServerRequestInterface $request, ?string $manifestFile = null): UriInterface
    {
        $viteContext = $request->getAttribute('vite.context');
        if ($viteContext?->useDevServer()) {
            return $this->assetUriGenerator->generateDevUri($file, $viteContext->getDevServer(), $request);
        }
        $manifest = $this->manifestFactory->createFromConfiguredPath($manifestFile);
        return $this->assetUriGenerator->generateUri($file, $manifest, $request);
    }
}
