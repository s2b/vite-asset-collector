<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset;

use Praetorius\ViteAssetCollector\Asset\Manifest\Manifest;
use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class AssetRenderer
{
    public function __construct(
        private AssetCollector $assetCollector,
        private PageRenderer $pageRenderer,
        private AssetPathResolver $assetPathResolver,
        private AssetUriGenerator $assetUriGenerator,
    ) {}

    public function renderDevAsset(Asset $asset, UriInterface $devServerUri, ServerRequestInterface $request): void
    {
        $this->renderViteClient($devServerUri, $request);
        $path = $this->assetPathResolver->resolveSourcePath($asset->entry);
        $uri = (string)$this->assetUriGenerator->generateDevUri($asset->entry, $devServerUri);
        if ($asset->entry->type === AssetType::Css) {
            $this->assetCollector->addStyleSheet(
                "vite:{$path}",
                $uri,
                $this->prepareCssAttributes($asset->cssEmbedding->additionalAttributes),
                $this->prepareOptions(['priority' => $asset->cssEmbedding->priority, 'useNonce' => $asset->csp]),
            );
        } else {
            $this->assetCollector->addJavaScript(
                "vite:{$path}",
                $uri,
                ['type' => 'module', ...$this->prepareScriptAttributes($asset->scriptEmbedding->additionalAttributes)],
                $this->prepareOptions(['priority' => $asset->scriptEmbedding->priority, 'useNonce' => $asset->csp])
            );
        }
    }

    public function renderAsset(Asset $asset, ServerRequestInterface $request): void
    {
        if ($asset->manifest === null) {
            throw new ViteException(sprintf(
                'Missing vite manifest data for asset "%s".',
                $asset->entry->locator,
            ), 1790004265);
        }
        $identifier = $this->assetPathResolver->resolveSourcePath($asset->entry);
        $chunk = $asset->manifest->getChunk($identifier);
        if (!$chunk?->isEntry) {
            throw new ViteException(sprintf(
                'Invalid vite entry point "%s" in manifest file "%s".',
                $asset->entry->locator,
                $asset->manifest->resolveAssetPath($asset->manifest->file),
            ), 1683200524);
        }

        if ($chunk->file->type === AssetType::Script) {
            $scriptTagAttributes = $this->prepareScriptAttributes($asset->scriptEmbedding->additionalAttributes);

            $this->assetCollector->addJavaScript(
                "vite:{$chunk->identifier}",
                $this->prepareAssetPath($chunk->file, $asset->manifest),
                ['type' => 'module', ...$scriptTagAttributes],
                $this->prepareOptions(['priority' => $asset->scriptEmbedding->priority, 'useNonce' => $asset->csp]),
            );
        }

        if (!$asset->cssEmbedding->ignore) {
            $cssTagAttributes = $this->prepareCssAttributes($asset->cssEmbedding->additionalAttributes);

            if ($chunk->file->type === AssetType::Css) {
                $this->renderCssAsset(
                    "vite:{$chunk->identifier}",
                    $chunk->file,
                    $cssTagAttributes,
                    $this->prepareOptions(['priority' => $asset->cssEmbedding->priority, 'useNonce' => $asset->csp]),
                    $asset->cssEmbedding->inline,
                    $asset->manifest
                );
            }

            foreach ($asset->manifest->getImportsForChunk($identifier, true) as $import) {
                $identifier = md5($import->identifier . '|' . serialize($cssTagAttributes));
                foreach ($import->css as $file) {
                    $this->renderCssAsset(
                        "vite:{$identifier}:{$file->locator}",
                        $file,
                        $cssTagAttributes,
                        $this->prepareOptions(['priority' => $asset->cssEmbedding->priority, 'useNonce' => $asset->csp]),
                        $asset->cssEmbedding->inline,
                        $asset->manifest
                    );
                }
            }

            foreach ($chunk->css as $file) {
                $this->renderCssAsset(
                    "vite:{$chunk->identifier}:{$file->locator}",
                    $file,
                    $cssTagAttributes,
                    $this->prepareOptions(['priority' => $asset->cssEmbedding->priority, 'useNonce' => $asset->csp]),
                    $asset->cssEmbedding->inline,
                    $asset->manifest
                );
            }
        }
    }

    private function renderViteClient(UriInterface $devServerUri, ServerRequestInterface $request): void
    {
        if ($this->assetCollector->hasJavaScript('vite')) {
            return;
        }
        $nonceAttribute = $request->getAttribute('nonce');
        if ($nonceAttribute instanceof ConsumableNonce) {
            // Add metatag to <head> to allow vite to consume the current nonce
            // see: https://de.vitejs.dev/guide/features.html#content-security-policy-csp
            $nonce = $nonceAttribute->consume();
            $this->pageRenderer->addHeaderData('<meta property="csp-nonce" nonce="' . $nonce . '">');
        }
        $this->assetCollector->addJavaScript(
            'vite',
            (string)$this->assetUriGenerator->generateDevUri(AssetFile::create('@vite/client'), $devServerUri),
            ['type' => 'module'],
            // TODO remove external flag once support for TYPO3 v13 is dropped
            $this->prepareOptions(['priority' => true]),
        );
    }

    private function renderCssAsset(
        string $identifier,
        OutputFile $file,
        array $attributes,
        array $assetOptions,
        bool $inlineCss,
        Manifest $manifest,
    ): void {
        if ($inlineCss) {
            $assetPath = $manifest->resolveAssetPath($file);
            $absoluteAssetPath = GeneralUtility::getFileAbsFileName($assetPath);
            if ($absoluteAssetPath === '' || !is_file($absoluteAssetPath)) {
                throw new ViteException(sprintf(
                    'CSS asset file "%s" was resolved to "%s" and cannot be opened for inline rendering.',
                    $assetPath,
                    $absoluteAssetPath
                ), 1745414701);
            }

            $cssSource = (string)file_get_contents($absoluteAssetPath);
            $this->assetCollector->addInlineStyleSheet(
                $identifier,
                $cssSource,
                $attributes,
                $assetOptions
            );
            return;
        }

        $this->assetCollector->addStyleSheet(
            $identifier,
            $this->prepareAssetPath($file, $manifest),
            $attributes,
            $assetOptions
        );
    }

    private function prepareAssetPath(OutputFile $file, Manifest $manifest): string
    {
        $assetPath = (string)$this->assetUriGenerator->generateUri($file, $manifest);
        // TODO adjust this when support for TYPO3 v13 is dropped
        return (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() > 13
            ? 'URI:' . $assetPath
            : $assetPath;
    }

    private function prepareScriptAttributes(array $attributes): array
    {
        foreach (['async', 'defer', 'nomodule'] as $attr) {
            if ($attributes[$attr] ?? false) {
                $attributes[$attr] = $attr;
            }
        }
        return $attributes;
    }

    private function prepareCssAttributes(array $attributes): array
    {
        if ($attributes['disabled'] ?? false) {
            $attributes['disabled'] = 'disabled';
        }
        return $attributes;
    }

    public function prepareOptions(array $options): array
    {
        // The "external" flag has been introduced with TYPO3 v13. It allows bypassing
        // of the default path preparation by AssetRenderer, including the addition of
        // cache-busting parameters to all asset files. As this is not necessary for files
        // generated by vite, which already contain a hash in their file name, this behavior
        // is avoided with v13. This also improves the behavior of dynamic imports, which
        // could result in duplicate requests before.
        // TODO remove external flag once support for TYPO3 v13 is dropped
        $options['external'] = true;
        if (isset($options['priority']) && $options['priority'] !== true) {
            unset($options['priority']);
        }
        if (isset($options['useNonce']) && $options['useNonce'] !== true) {
            unset($options['useNonce']);
        }
        return $options;
    }
}
