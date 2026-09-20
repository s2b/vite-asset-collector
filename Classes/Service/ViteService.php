<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Service;

use Praetorius\ViteAssetCollector\Asset\Asset;
use Praetorius\ViteAssetCollector\Asset\AssetFile;
use Praetorius\ViteAssetCollector\Asset\AssetPathResolver;
use Praetorius\ViteAssetCollector\Asset\AssetRenderer;
use Praetorius\ViteAssetCollector\Asset\AssetUriGenerator;
use Praetorius\ViteAssetCollector\Asset\Embedding\CssEmbedding;
use Praetorius\ViteAssetCollector\Asset\Embedding\ScriptEmbedding;
use Praetorius\ViteAssetCollector\Asset\Manifest\ManifestFactory;
use Praetorius\ViteAssetCollector\Event\BuildViteContextEvent;
use Praetorius\ViteAssetCollector\EventListener\BuildViteContext;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\ServerRequest;

readonly class ViteService
{
    public const DEFAULT_PORT = 5173;

    public function __construct(
        protected ExtensionConfiguration $extensionConfiguration,
        protected AssetPathResolver $assetPathResolver,
        protected AssetUriGenerator $assetUriGenerator,
        protected AssetRenderer $assetRenderer,
        protected ManifestFactory $manifestFactory,
    ) {}

    public function getDefaultManifestFile(): string
    {
        return $this->extensionConfiguration->get('vite_asset_collector', 'defaultManifest');
    }

    public function useDevServer(ServerRequestInterface $request): bool
    {
        $event = new BuildViteContextEvent($request);
        (new BuildViteContext($this->extensionConfiguration))($event);
        $viteContext = $event->getViteContext();
        return $viteContext->useDevServer();
    }

    public function determineDevServer(ServerRequestInterface $request): UriInterface
    {
        $event = new BuildViteContextEvent($request);
        (new BuildViteContext($this->extensionConfiguration))($event);
        $viteContext = $event->getViteContext();
        return $viteContext?->getDevServer();
    }

    public function addAssetsFromDevServer(
        UriInterface $devServerUri,
        string $entry,
        array $assetOptions = [],
        array $scriptTagAttributes = [],
        array $cssTagAttributes = [],
    ): void {
        $this->assetRenderer->renderDevAsset(
            Asset::create(
                entry: $entry,
                cssEmbedding: new CssEmbedding(
                    priority: $assetOptions['priority'] ?? false,
                    additionalAttributes: $cssTagAttributes,
                ),
                scriptEmbedding: new ScriptEmbedding(
                    priority: $assetOptions['priority'] ?? false,
                    additionalAttributes: $scriptTagAttributes,
                ),
                csp: $assetOptions['useNonce'] ?? false,
            ),
            $devServerUri,
            new ServerRequest(),
        );
    }

    public function getAssetPathFromDevServer(
        UriInterface $devServerUri,
        string $assetFile,
    ): string {
        return (string)$this->assetUriGenerator->generateDevUri(AssetFile::create($assetFile), $devServerUri);
    }

    public function determineEntrypointFromManifest(string $manifestFile): string
    {
        $manifest = $this->manifestFactory->createFromFilePath($manifestFile);
        return $manifest->getOnlyEntrypoint()->identifier;
    }

    public function addAssetsFromManifest(
        string $manifestFile,
        string $entry,
        bool $addCss = true,
        array $assetOptions = [],
        array $scriptTagAttributes = [],
        array $cssTagAttributes = [],
        bool $inlineCss = false,
    ): void {
        $this->assetRenderer->renderAsset(
            Asset::create(
                entry: $entry,
                cssEmbedding: new CssEmbedding(
                    ignore: !$addCss,
                    priority: $assetOptions['priority'] ?? false,
                    additionalAttributes: $cssTagAttributes,
                    inline: $inlineCss,
                ),
                scriptEmbedding: new ScriptEmbedding(
                    priority: $assetOptions['priority'] ?? false,
                    additionalAttributes: $scriptTagAttributes,
                ),
                manifest: $this->manifestFactory->createFromFilePath($manifestFile),
                csp: $assetOptions['useNonce'] ?? false,
            ),
            new ServerRequest(),
        );
    }

    public function getAssetPathFromManifest(
        string $manifestFile,
        string $assetFile,
        bool $returnWebPath = true
    ): string {
        $manifest = $this->manifestFactory->createFromFilePath($manifestFile);
        if ($returnWebPath) {
            return (string)$this->assetUriGenerator->generateUri(AssetFile::create($assetFile), $manifest);
        }
        return $this->assetPathResolver->resolveOutputPath(AssetFile::create($assetFile), $manifest);
    }
}
