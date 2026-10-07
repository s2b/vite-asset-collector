<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset;

use Praetorius\ViteAssetCollector\Asset\Embedding\ContentSecurityMode;
use Praetorius\ViteAssetCollector\Asset\Embedding\CssEmbedding;
use Praetorius\ViteAssetCollector\Asset\Manifest\Manifest;
use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\RelativeCssPathFixer;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Utility\PathUtility;

#[AsAlias(AssetRendererInterface::class)]
final readonly class AssetRenderer implements AssetRendererInterface
{
    public function __construct(
        private AssetCollector $assetCollector,
        private PageRenderer $pageRenderer,
        private RelativeCssPathFixer $relativeCssPathFixer,
        private AssetPathResolverInterface $assetPathResolver,
        private AssetUriGeneratorInterface $assetUriGenerator,
    ) {}

    public function renderDevAsset(Asset $asset, UriInterface $devServerUri, ServerRequestInterface $request): void
    {
        $this->renderViteClient($devServerUri, $request);
        $path = $this->assetPathResolver->resolveSourcePath($asset->entry);
        $uri = (string)$this->assetUriGenerator->generateDevUri($asset->entry, $devServerUri, $request);
        if ($asset->entry->type === AssetType::Css) {
            $this->assetCollector->addStyleSheet(
                "vite:{$path}",
                $uri,
                $asset->cssEmbedding->getTagAttributes(),
                $this->prepareOptions(['priority' => $asset->cssEmbedding->priority]),
            );
        } else {
            $this->assetCollector->addJavaScript(
                "vite:{$path}",
                $uri,
                $asset->scriptEmbedding->getTagAttributes(),
                $this->prepareOptions(['priority' => $asset->scriptEmbedding->priority])
            );
        }
    }

    public function renderAsset(Asset $asset, Manifest $manifest, ServerRequestInterface $request): void
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

        if (!$asset->scriptEmbedding->ignore && $chunk->file->type === AssetType::Script) {
            $this->assetCollector->addJavaScript(
                "vite:{$chunk->identifier}",
                $this->prepareAssetPath($chunk->file, $manifest, $request),
                $asset->scriptEmbedding->getTagAttributes(),
                $this->prepareOptions(['priority' => $asset->scriptEmbedding->priority, 'csp' => $this->determineCspStatus($asset->csp, false)]),
            );
        }

        if (!$asset->cssEmbedding->ignore) {
            if ($chunk->file->type === AssetType::Css) {
                $this->renderStyleSheet(
                    "vite:{$chunk->identifier}",
                    $chunk->file,
                    $manifest,
                    $asset->cssEmbedding,
                    $asset->csp,
                    $request,
                );
            }

            foreach ($manifest->getImportsForChunk($identifier, true) as $import) {
                $identifier = md5($import->identifier . '|' . serialize($asset->cssEmbedding->getTagAttributes()));
                foreach ($import->css as $file) {
                    $this->renderStyleSheet(
                        "vite:{$identifier}:{$file->locator}",
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
                    "vite:{$chunk->identifier}:{$file->locator}",
                    $file,
                    $manifest,
                    $asset->cssEmbedding,
                    $asset->csp,
                    $request,
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

    private function renderStyleSheet(
        string $identifier,
        OutputFile $file,
        Manifest $manifest,
        CssEmbedding $cssEmbedding,
        ContentSecurityMode $csp,
        ServerRequestInterface $request,
    ): void {
        if ($cssEmbedding->inline) {
            $resolvedFile = $this->assetPathResolver->resolveOutputPath($file, $manifest, true);
            if ($resolvedFile === '' || !@is_file($resolvedFile) || !@file_exists($resolvedFile)) {
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
            $outputPath = $this->prepareAssetPath(OutputFile::create(PathUtility::dirname($file->locator)), $manifest, $request) . '/';
            $cssSource = $this->relativeCssPathFixer->fixRelativeUrlPaths($cssSource, $outputPath, $request);
            $this->assetCollector->addInlineStyleSheet(
                $identifier,
                $cssSource,
                $cssEmbedding->getTagAttributes(),
                $this->prepareOptions(['priority' => $cssEmbedding->priority, 'csp' => $this->determineCspStatus($csp, true)])
            );
            return;
        }

        $this->assetCollector->addStyleSheet(
            $identifier,
            $this->prepareAssetPath($file, $manifest, $request),
            $cssEmbedding->getTagAttributes(),
            $this->prepareOptions(['priority' => $cssEmbedding->priority, 'csp' => $this->determineCspStatus($csp, false)])
        );
    }

    private function prepareAssetPath(OutputFile $file, Manifest $manifest, ServerRequestInterface $request): string
    {
        $assetPath = (string)$this->assetUriGenerator->generateUri($file, $manifest, $request);
        // TODO adjust this when support for TYPO3 v13 is dropped
        return (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() > 13
            ? 'URI:' . $assetPath
            : $assetPath;
    }

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
