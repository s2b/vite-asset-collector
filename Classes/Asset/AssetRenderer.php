<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset;

use Praetorius\ViteAssetCollector\Asset\Embedding\ContentSecurityMode;
use Praetorius\ViteAssetCollector\Asset\Embedding\CssEmbedding;
use Praetorius\ViteAssetCollector\Asset\Embedding\FontPreloading;
use Praetorius\ViteAssetCollector\Asset\Embedding\ImagePreloading;
use Praetorius\ViteAssetCollector\Asset\Manifest\Manifest;
use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\MimeTypeDetector;
use TYPO3\CMS\Core\Resource\RelativeCssPathFixer;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;

#[AsAlias(AssetRendererInterface::class)]
final readonly class AssetRenderer implements AssetRendererInterface
{
    public function __construct(
        private AssetCollector $assetCollector,
        private PageRenderer $pageRenderer,
        private RelativeCssPathFixer $relativeCssPathFixer,
        private AssetPathResolverInterface $assetPathResolver,
        private AssetUriGeneratorInterface $assetUriGenerator,
        private MimeTypeDetector $mimeTypeDetector,
    ) {}

    /**
     * Embeds an asset from a running dev server. Due to the way the vite dev
     * server operates, not all available embedding options will be applied to
     * the resulting HTML tags. Content Security Policy is handled by allowing
     * requests to the dev server in general.
     */
    public function renderDevAsset(Asset $asset, UriInterface $devServerUri, ServerRequestInterface $request): void
    {
        switch ($asset->entry->type) {
            case AssetType::Css:
                if ($asset->cssEmbedding->ignore) {
                    return;
                }
                $this->renderViteClient($devServerUri, $request);
                $this->assetCollector->addStyleSheet(
                    'vite:' . $this->assetPathResolver->resolveSourcePath($asset->entry),
                    (string)$this->assetUriGenerator->generateDevUri($asset->entry, $devServerUri, $request),
                    $asset->cssEmbedding->getTagAttributes(),
                    $this->prepareOptions(['priority' => $asset->cssEmbedding->priority]),
                );
                return;

            case AssetType::Script:
                // Vite JavaScript entrypoints might serve both JavaScript and CSS, so the
                // separate "ignore" options can only be applied if both CSS and JavaScript
                // should be ignored.
                if ($asset->scriptEmbedding->ignore && $asset->cssEmbedding->ignore) {
                    return;
                }
                // Legacy (nomodule) bundles are only generated during build, the dev server
                // doesn't provide them.
                if ($asset->scriptEmbedding->nomodule) {
                    return;
                }
                $this->renderViteClient($devServerUri, $request);
                $this->assetCollector->addJavaScript(
                    'vite:' . $this->assetPathResolver->resolveSourcePath($asset->entry),
                    (string)$this->assetUriGenerator->generateDevUri($asset->entry, $devServerUri, $request),
                    $asset->scriptEmbedding->getDevServerTagAttributes(),
                    $this->prepareOptions(['priority' => $asset->scriptEmbedding->priority])
                );
                return;

            default:
                throw new ViteException(sprintf(
                    'Vite entry point "%s" has unsupported type "%s". %s() only supports JavaScript and CSS entry points, use <vite:uri> to reference other files.',
                    $asset->entry->locator,
                    $asset->entry->type->name,
                    __METHOD__,
                ), 1794135659);
        }
    }

    /**
     * Embeds an asset from vite's build output, based on its manifest.json file.
     */
    public function renderBuildAsset(Asset $asset, Manifest $manifest, ServerRequestInterface $request): void
    {
        $identifier = $this->assetPathResolver->resolveSourcePath($asset->entry);
        $chunk = $manifest->getChunk($identifier);
        if (!$chunk?->isEntry) {
            throw new ViteException(sprintf(
                'Invalid vite entry point "%s" in manifest file "%s".',
                $asset->entry->locator,
                $manifest->resolveAssetPath($manifest->file),
            ), 1683200524);
        }
        if (!in_array($chunk->file->type, [AssetType::Script, AssetType::Css], true)) {
            throw new ViteException(sprintf(
                'Vite entry point "%s" has unsupported type "%s". %s() only supports JavaScript and CSS entry points, use <vite:uri> to reference other files.',
                $asset->entry->locator,
                $chunk->file->type->name,
                __METHOD__,
            ), 1794135660);
        }

        $preload = false;
        if (!$asset->scriptEmbedding->ignore && $chunk->file->type === AssetType::Script) {
            $preload = true;
            $assetUri = (string)$this->assetUriGenerator->generateUri($chunk->file, $manifest, $request);
            // Using the full asset URI as identifier avoids conflicts between different manifest.json
            // files while still catching duplicates from one manifest.json.
            $this->assetCollector->addJavaScript(
                'vite:' . $assetUri,
                $this->prepareAssetPath($assetUri),
                $asset->scriptEmbedding->getTagAttributes(),
                $this->prepareOptions(['priority' => $asset->scriptEmbedding->priority, 'csp' => $this->determineCspStatus($asset->csp, false)]),
            );
            if ($asset->scriptEmbedding->preloadImports) {
                $preloadTemplate = $asset->scriptEmbedding->module
                    ? '<link rel="modulepreload" href="%s" />'
                    : '<link rel="preload" href="%s" as="script" />';
                foreach ($manifest->getImportsForChunk($identifier, true) as $preloadModule) {
                    $this->pageRenderer->addHeaderData(sprintf(
                        $preloadTemplate,
                        (string)$this->assetUriGenerator->generateUri($preloadModule->file, $manifest, $request),
                    ));
                }
            }
        }
        if (!$asset->cssEmbedding->ignore) {
            $preload = true;
            if ($chunk->file->type === AssetType::Css) {
                $this->renderStyleSheet(
                    $chunk->file,
                    $manifest,
                    $asset->cssEmbedding,
                    $asset->csp,
                    $request,
                );
            }
            foreach ($manifest->getImportsForChunk($identifier, true) as $import) {
                foreach ($import->css as $file) {
                    $this->renderStyleSheet(
                        $file,
                        $manifest,
                        $asset->cssEmbedding,
                        $asset->csp,
                        $request,
                    );
                }
            }
            foreach ($chunk->css as $file) {
                $this->renderStyleSheet(
                    $file,
                    $manifest,
                    $asset->cssEmbedding,
                    $asset->csp,
                    $request,
                );
            }
        }

        if ($preload) {
            $this->preloadReferencedAssets($chunk->assets, $manifest, $asset->fontPreloading, $asset->imagePreloading, $request);
            foreach ($manifest->getImportsForChunk($identifier, true) as $import) {
                $this->preloadReferencedAssets($import->assets, $manifest, $asset->fontPreloading, $asset->imagePreloading, $request);
            }
        }
    }

    /**
     * @param OutputFile[] $assets
     */
    private function preloadReferencedAssets(
        array $assets,
        Manifest $manifest,
        FontPreloading $fontPreloading,
        ImagePreloading $imagePreloading,
        ServerRequestInterface $request,
    ): void {
        foreach ($assets as $asset) {
            $preloadTemplate = match (true) {
                ($asset->type === AssetType::Font && $fontPreloading->appliesTo($asset))
                    => '<link rel="preload" href="%s" as="font" type="%s" crossorigin />',
                (in_array($asset->type, [AssetType::Image, AssetType::Svg], true) && $imagePreloading->appliesTo($asset))
                    => '<link rel="preload" href="%s" as="image" type="%s" />',
                default => null,
            };
            if ($preloadTemplate) {
                $mimeType = $this->mimeTypeDetector->getMimeTypesForFileExtension($asset->extension);
                if ($mimeType[0]) {
                    $this->pageRenderer->addHeaderData(sprintf(
                        $preloadTemplate,
                        (string)$this->assetUriGenerator->generateUri($asset, $manifest, $request),
                        $mimeType[0],
                    ));
                }
            }
        }
    }

    private function renderStyleSheet(
        OutputFile $file,
        Manifest $manifest,
        CssEmbedding $cssEmbedding,
        ContentSecurityMode $csp,
        ServerRequestInterface $request,
    ): void {
        // The same file with the same attributes results in a single tag, regardless of the
        // entry point or chunk it originates from. Different attributes (e. g. media) result
        // in separate tags.
        // Note that AssetCollector merges attributes and options of entries with the same
        // identifier.
        $assetUri = (string)$this->assetUriGenerator->generateUri($file, $manifest, $request);
        $identifier = sprintf('vite:%s:%s', $assetUri, md5(serialize($cssEmbedding->getTagAttributes())));
        if (!$cssEmbedding->inline) {
            $this->assetCollector->addStyleSheet(
                $identifier,
                $this->prepareAssetPath($assetUri),
                $cssEmbedding->getTagAttributes(),
                $this->prepareOptions(['priority' => $cssEmbedding->priority, 'csp' => $this->determineCspStatus($csp, false)])
            );
            return;
        }
        $resolvedFile = $this->assetPathResolver->resolveOutputPath($file, $manifest, true);
        if ($resolvedFile === '' || !@is_file($resolvedFile)) {
            throw new ViteException(sprintf(
                'CSS asset file "%s" was resolved to "%s" and cannot be opened for inline rendering.',
                $file->locator,
                $resolvedFile
            ), 1745414701);
        }
        $cssSource = file_get_contents($resolvedFile);
        if ($cssSource === false) {
            throw new ViteException(sprintf(
                'Unable to open CSS file "%s".',
                $resolvedFile
            ), 1790541381);
        }
        $outputPath = dirname($this->prepareAssetPath($assetUri)) . '/';
        $cssSource = $this->relativeCssPathFixer->fixRelativeUrlPaths($cssSource, $outputPath, $request);
        $this->assetCollector->addInlineStyleSheet(
            $identifier,
            $cssSource,
            $cssEmbedding->getTagAttributes(),
            $this->prepareOptions(['priority' => $cssEmbedding->priority, 'csp' => $this->determineCspStatus($csp, true)])
        );
    }

    private function renderViteClient(UriInterface $devServerUri, ServerRequestInterface $request): void
    {
        if ($this->assetCollector->hasJavaScript('vite')) {
            return;
        }
        $nonceAttribute = $request->getAttribute('nonce');
        if ($nonceAttribute instanceof ConsumableNonce) {
            // Add metatag to <head> to allow vite to consume the current nonce
            // see: https://vite.dev/guide/features.html#content-security-policy-csp
            $nonce = $nonceAttribute->consume();
            $endingSlash = $this->pageRenderer->getDocType()->isXmlCompliant() ? ' /' : '';
            $this->pageRenderer->addHeaderData('<meta property="csp-nonce" nonce="' . $nonce . '"' . $endingSlash . '>');
        }
        $this->assetCollector->addJavaScript(
            'vite',
            (string)$this->assetUriGenerator->generateDevUri(AssetFile::create('@vite/client'), $devServerUri, $request),
            ['type' => 'module'],
            $this->prepareOptions(['priority' => true]),
        );
    }

    private function prepareAssetPath(string $assetUri): string
    {
        // TODO adjust this when support for TYPO3 v13 is dropped
        return (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() > 13
            ? 'URI:' . $assetUri
            : $assetUri;
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function prepareOptions(array $options): array
    {
        // The "external" flag has been introduced with TYPO3 v13. It allows bypassing
        // of the default path preparation by AssetRenderer, including the addition of
        // cache-busting parameters to all asset files. As this is not necessary for files
        // generated by vite, which already contain a hash in their file name, this behavior
        // is avoided with v13. This also improves the behavior of dynamic imports, which
        // could result in duplicate requests before.
        // TODO remove external flag once support for TYPO3 v13 is dropped
        $options = ['external' => true, ...$options];
        if (isset($options['priority']) && $options['priority'] !== true) {
            unset($options['priority']);
        }
        if (isset($options['csp']) && $options['csp'] !== true) {
            unset($options['csp']);
        }
        // TODO remove this once support for TYPO3 v13 is dropped
        if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() < 14 && isset($options['csp'])) {
            $options['useNonce'] = $options['csp'];
            unset($options['csp']);
        }
        return $options;
    }

    private function determineCspStatus(ContentSecurityMode $mode, bool $inline): bool
    {
        return match ($mode) {
            // Enable CSP by default in v14, but only for non-inline assets; for v13, it's disabled by default
            // TODO remove version switch when support for v13 is dropped
            ContentSecurityMode::Auto => (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() > 13 && !$inline,
            ContentSecurityMode::Enabled => true,
            ContentSecurityMode::Disabled => false,
        };
    }
}
