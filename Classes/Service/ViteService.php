<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Service;

use Praetorius\ViteAssetCollector\Domain\Model\ViteManifest;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class ViteService
{
    public const DEFAULT_PORT = 5173;

    public function __construct(
        private readonly FrontendInterface $cache,
        protected readonly AssetCollector $assetCollector,
        protected readonly PackageManager $packageManager,
        protected readonly ExtensionConfiguration $extensionConfiguration
    ) {}

    public function getDefaultManifestFile(): string
    {
        return $this->extensionConfiguration->get('vite_asset_collector', 'defaultManifest');
    }

    public function useDevServer(): bool
    {
        $useDevServer = $this->extensionConfiguration->get('vite_asset_collector', 'useDevServer');
        if ($useDevServer === 'auto') {
            return Environment::getContext()->isDevelopment();
        }
        return (bool)$useDevServer;
    }

    public function determineDevServer(ServerRequestInterface $request): UriInterface
    {
        $devServerUri = $this->extensionConfiguration->get('vite_asset_collector', 'devServerUri');
        if ($devServerUri === 'auto') {
            // This constant is used by ddev-vite-sidecar and contains the full DDEV server uri
            $serverUri = getenv('VITE_SERVER_URI');
            if ($serverUri) {
                return new Uri($serverUri);
            }

            // This constant is used by ddev-viteserve and contains only the port that can be
            // combined with any ddev domain of the current project
            $vitePort = getenv('VITE_PRIMARY_PORT') ?: self::DEFAULT_PORT;
            return $request->getUri()->withPath('')->withPort((int)$vitePort);
        }
        return new Uri($devServerUri);
    }

    public function addAssetsFromDevServer(
        UriInterface $devServerUri,
        string $entry,
        array $assetOptions = [],
        array $scriptTagAttributes = []
    ): void {
        $entry = $this->determineAssetIdentifierFromExtensionPath($entry);

        $scriptTagAttributes = $this->prepareScriptAttributes($scriptTagAttributes);
        $this->assetCollector->addJavaScript(
            'vite',
            (string)$devServerUri->withPath('@vite/client'),
            ['type' => 'module', ...$scriptTagAttributes],
            $assetOptions
        );
        $this->assetCollector->addJavaScript(
            "vite:{$entry}",
            (string)$devServerUri->withPath($entry),
            ['type' => 'module', ...$scriptTagAttributes],
            $assetOptions
        );
    }

    public function getAssetPathFromDevServer(
        UriInterface $devServerUri,
        string $assetFile,
    ): string {
        $assetFile = $this->determineAssetIdentifierFromExtensionPath($assetFile);
        return (string)$devServerUri->withPath($assetFile);
    }

    public function determineEntrypointFromManifest(string $manifestFile): string
    {
        $manifestFile = $this->resolveManifestFile($manifestFile);
        $manifest = $this->parseManifestFile($manifestFile);

        $entrypoints = $manifest->getValidEntrypoints();
        if (count($entrypoints) !== 1) {
            throw new ViteException(sprintf(
                'Appropriate vite entrypoint could not be determined automatically. Expected 1 entrypoint in "%s", found %d.',
                $manifestFile,
                count($entrypoints)
            ), 1683552723);
        }
        $onlyEntrypoint = array_pop($entrypoints);
        return $onlyEntrypoint->identifier;
    }

    public function addAssetsFromManifest(
        string $manifestFile,
        string $entry,
        bool $addCss = true,
        array $assetOptions = [],
        array $scriptTagAttributes = [],
        array $cssTagAttributes = []
    ): void {
        $entry = $this->determineAssetIdentifierFromExtensionPath($entry);

        $manifestFile = $this->resolveManifestFile($manifestFile);
        $outputDir = $this->determineOutputDirFromManifestFile($manifestFile);
        $manifest = $this->parseManifestFile($manifestFile);

        if (!$manifest->get($entry)?->isEntry) {
            throw new ViteException(sprintf(
                'Invalid vite entry point "%s" in manifest file "%s".',
                $entry,
                $manifestFile
            ), 1683200524);
        }

        $entryPoint = $manifest->get($entry);

        if (!$entryPoint->isCss()) {
            $scriptTagAttributes = $this->prepareScriptAttributes($scriptTagAttributes);

            $this->assetCollector->addJavaScript(
                "vite:{$entry}",
                $outputDir . $entryPoint->file,
                ['type' => 'module', ...$scriptTagAttributes],
                $assetOptions
            );
        }

        if ($addCss) {
            if ($entryPoint->isCss()) {
                $this->assetCollector->addStyleSheet("vite:{$entry}", $outputDir . $entryPoint->file, $cssTagAttributes, $assetOptions);
            }

            $cssTagAttributes = $this->prepareCssAttributes($cssTagAttributes);

            foreach ($manifest->getImportsForEntrypoint($entry) as $import) {
                $identifier = md5($import->identifier . '|' . serialize($cssTagAttributes) . '|' . serialize($assetOptions));
                foreach ($import->css as $file) {
                    $this->assetCollector->addStyleSheet("vite:{$identifier}:{$file}", $outputDir . $file, $cssTagAttributes, $assetOptions);
                }
            }

            foreach ($manifest->get($entry)->css as $file) {
                $this->assetCollector->addStyleSheet("vite:{$entry}:{$file}", $outputDir . $file, $cssTagAttributes, $assetOptions);
            }
        }
    }

    public function getAssetPathFromManifest(
        string $manifestFile,
        string $assetFile,
        bool $returnWebPath = true
    ): string {
        $assetFile = $this->determineAssetIdentifierFromExtensionPath($assetFile);

        $manifestFile = $this->resolveManifestFile($manifestFile);
        $manifest = $this->parseManifestFile($manifestFile);
        if (!$manifest->get($assetFile)) {
            throw new ViteException(sprintf(
                'Invalid asset file "%s" in vite manifest file "%s".',
                $assetFile,
                $manifestFile
            ), 1690735353);
        }

        $assetPath = $this->determineOutputDirFromManifestFile($manifestFile) . $manifest->get($assetFile)->file;
        return ($returnWebPath) ? PathUtility::getAbsoluteWebPath($assetPath) : $assetPath;
    }

    protected function resolveManifestFile(string $manifestFile): string
    {
        $resolvedManifestFile = GeneralUtility::getFileAbsFileName($manifestFile);
        if ($resolvedManifestFile === '' || !file_exists($resolvedManifestFile)) {
            // Fallback to directory structure from vite < 5
            $legacyManifestFile = $this->determineOutputDirFromManifestFile($manifestFile) . PathUtility::basename($manifestFile);
            $resolvedLegacyManifestFile = GeneralUtility::getFileAbsFileName($legacyManifestFile);
            if ($resolvedLegacyManifestFile !== '' && file_exists($resolvedLegacyManifestFile)) {
                return $resolvedLegacyManifestFile;
            }

            throw new ViteException(sprintf(
                'Vite manifest file "%s" was resolved to "%s" and cannot be opened.',
                $manifestFile,
                $resolvedManifestFile
            ), 1683200522);
        }
        return $resolvedManifestFile;
    }

    protected function parseManifestFile(string $manifestFile): ViteManifest
    {
        $cacheIdentifier = md5($manifestFile);
        $manifest = $this->cache->get($cacheIdentifier);
        if ($manifest === false) {
            $manifest = ViteManifest::fromFile($manifestFile);
            $this->cache->set($cacheIdentifier, $manifest);
        }
        return $manifest;
    }

    protected function determineAssetIdentifierFromExtensionPath(string $identifier): string
    {
        if (!PathUtility::isExtensionPath($identifier)) {
            return $identifier;
        }

        $absolutePath = $this->packageManager->resolvePackagePath($identifier);
        $file = PathUtility::basename($absolutePath);
        $dir = realpath(PathUtility::dirname($absolutePath));
        if ($dir === false) {
            throw new ViteException(sprintf(
                'The specified extension path "%s" does not exist.',
                $identifier
            ), 1696238083);
        }
        $relativeDirToProjectRoot = PathUtility::getRelativePath(Environment::getProjectPath(), $dir);

        return $relativeDirToProjectRoot . $file;
    }

    protected function determineOutputDirFromManifestFile(string $manifestFile): string
    {
        $outputDir = PathUtility::dirname($manifestFile);
        if (PathUtility::basename($outputDir) === '.vite') {
            $outputDir = PathUtility::dirname($outputDir);
        }
        return $outputDir . '/';
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
