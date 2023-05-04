<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Service;

use Praetorius\ViteAssetCollector\Exception\ViteException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ViteService
{
    public const DEFAULT_PORT = 5173;

    public function __construct(
        protected AssetCollector $assetCollector
    ) {
    }

    public function determineDevServer(ServerRequestInterface $request): UriInterface
    {
        $vitePort = getenv('VITE_PRIMARY_PORT') ?: self::DEFAULT_PORT;
        return $request->getUri()->withPath('')->withPort((int)$vitePort);
    }

    public function addAssetsFromDevServer(
        UriInterface $devServerUri,
        string $entry,
        array $assetOptions = [],
        array $scriptTagAttributes = []
    ): void {
        $scriptTagAttributes = $this->prepareScriptAttributes($scriptTagAttributes);
        $this->assetCollector->addJavaScript(
            'vite',
            (string)$devServerUri->withPath('@vite/client'),
            ['type' => 'module', ...$scriptTagAttributes],
            $assetOptions
        );
        $this->assetCollector->addJavaScript(
            "vite:${entry}",
            (string)$devServerUri->withPath($entry),
            ['type' => 'module', ...$scriptTagAttributes],
            $assetOptions
        );
    }

    public function addAssetsFromManifest(
        string $manifestFile,
        string $entry,
        bool $addCss = true,
        array $assetOptions = [],
        array $scriptTagAttributes = [],
        array $cssTagAttributes = []
    ): void {
        $manifestFile = $this->resolveManifestFile($manifestFile);
        $manifestDir = dirname($manifestFile) . '/';
        $manifest = $this->parseManifestFile($manifestFile);

        if (!isset($manifest[$entry]) || !$manifest[$entry]['isEntry']) {
            throw new ViteException(sprintf(
                'Invalid vite entry point "%s" in manifest file "%s".',
                $entry,
                $manifestFile
            ), 1683200524);
        }

        $scriptTagAttributes = $this->prepareScriptAttributes($scriptTagAttributes);
        $this->assetCollector->addJavaScript(
            "vite:${entry}",
            $manifestDir . $manifest[$entry]['file'],
            ['type' => 'module', ...$scriptTagAttributes],
            $assetOptions
        );

        if ($addCss && !empty($manifest[$entry]['css'])) {
            $cssTagAttributes = $this->prepareCssAttributes($cssTagAttributes);
            foreach ($manifest[$entry]['css'] as $file) {
                $this->assetCollector->addStyleSheet(
                    "vite:${entry}:${file}",
                    $manifestDir . $file,
                    $cssTagAttributes,
                    $assetOptions
                );
            }
        }
    }

    protected function resolveManifestFile(string $manifestFile): string
    {
        $resolvedManifestFile = GeneralUtility::getFileAbsFileName($manifestFile);
        if ($resolvedManifestFile === '' || !file_exists($resolvedManifestFile)) {
            throw new ViteException(sprintf(
                'Vite manifest file "%s" was resolved to "%s" and cannot be opened.',
                $manifestFile,
                $resolvedManifestFile
            ), 1683200522);
        }
        return $resolvedManifestFile;
    }

    protected function parseManifestFile(string $manifestFile): array
    {
        $manifest = json_decode(file_get_contents($manifestFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ViteException(sprintf(
                'Invalid vite manifest file "%s": %s.',
                $manifestFile,
                json_last_error_msg()
            ), 1683200523);
        }
        return $manifest;
    }

    protected function prepareScriptAttributes(array $attributes): array
    {
        foreach (['async', 'defer', 'nomodule'] as $attr) {
            if ($attributes[$attr] ?? false) {
                $attributes[$attr] = $attr;
            }
        }
        return $attributes;
    }

    protected function prepareCssAttributes(array $attributes): array
    {
        if ($attributes['disabled'] ?? false) {
            $attributes['disabled'] = 'disabled';
        }
        return $attributes;
    }
}
