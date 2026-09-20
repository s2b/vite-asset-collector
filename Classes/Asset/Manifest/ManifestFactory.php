<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset\Manifest;

use Praetorius\ViteAssetCollector\Asset\AssetFile;
use Praetorius\ViteAssetCollector\Asset\AssetType;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

final readonly class ManifestFactory
{
    public function __construct(
        #[Autowire(service: 'cache.viteassetcollector_manifest')]
        private FrontendInterface $cache,
        private ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function createFromConfiguredPath(?string $manifestFile): Manifest
    {
        return $manifestFile !== null ? $this->createFromFilePath($manifestFile) : $this->getDefault();
    }

    public function getDefault(): Manifest
    {
        $defaultManifest = $this->extensionConfiguration->get('vite_asset_collector', 'defaultManifest');
        try {
            return $this->createFromFilePath($defaultManifest);
        } catch (ViteException $e) {
            throw new ViteException(sprintf(
                'Invalid default vite manifest file "%s": %s (%d)',
                $defaultManifest,
                $e->getMessage(),
                $e->getCode()
            ), 1684528724);
        }
    }

    public function createFromFilePath(string $manifestFile): Manifest
    {
        $resolvedFile = GeneralUtility::getFileAbsFileName($manifestFile);
        if ($resolvedFile === '' || !file_exists($resolvedFile)) {
            throw new ViteException(sprintf(
                'Vite manifest file "%s" was resolved to "%s" and cannot be opened.',
                $manifestFile,
                $resolvedFile
            ), 1683200522);
        }
        $cacheIdentifier = md5($resolvedFile);
        $manifest = $this->cache->get($cacheIdentifier);
        if ($manifest === false) {
            $manifest = new Manifest(
                chunks: $this->readManifestFile($resolvedFile),
                file: OutputFile::create($resolvedFile),
                outputDir: $this->determineOutputDir($resolvedFile),
            );
            $this->cache->set($cacheIdentifier, $manifest);
        }
        return $manifest;
    }

    private function readManifestFile(string $manifestFile): array
    {
        $manifestJson = file_get_contents($manifestFile);
        if ($manifestJson === false) {
            throw new ViteException(sprintf(
                'Unable to open manifest file "%s".',
                $manifestFile
            ), 1684256597);
        }
        $manifest = json_decode($manifestJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ViteException(sprintf(
                'Invalid vite manifest file "%s": %s.',
                $manifestFile,
                json_last_error_msg()
            ), 1683200523);
        }
        $chunks = [];
        foreach ($manifest as $identifier => $data) {
            $chunks[$identifier] = $this->createManifestChunk($identifier, $data);
        }
        return $chunks;
    }

    private function createManifestChunk(string $identifier, array $data): ManifestChunk
    {
        $defaults = [
            'name' => null,
            'src' => null,
            'isEntry' => false,
            'isDynamicEntry' => false,
            'file' => '',
            'assets' => [],
            'css' => [],
            'imports' => [],
            'dynamicImports' => [],
        ];
        $data = array_merge($defaults, $data);
        return new ManifestChunk(
            identifier: $identifier,
            name: (string)$data['name'],
            src: $data['src'] !== null ? AssetFile::create((string)$data['src']) : null,
            file: OutputFile::create((string)$data['file']),
            isEntry: (bool)$data['isEntry'],
            isDynamicEntry: (bool)$data['isDynamicEntry'],
            assets: array_map(
                OutputFile::create(...),
                (array)$data['assets'],
            ),
            css: array_map(
                fn(string $css) => OutputFile::create($css, AssetType::Css),
                (array)$data['css'],
            ),
            imports: (array)$data['imports'],
            dynamicImports: (array)$data['dynamicImports'],
        );
    }

    private function determineOutputDir(string $manifestFile): string
    {
        // from _assets/vite/.vite/manifest.json to _assets/vite/
        return PathUtility::dirname(PathUtility::dirname($manifestFile)) . '/';
    }
}
