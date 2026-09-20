<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset;

use Praetorius\ViteAssetCollector\Asset\Manifest\Manifest;
use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\PathUtility;

final readonly class AssetPathResolver
{
    public function __construct(private PackageManager $packageManager) {}

    public function resolveOutputPath(AssetFile|OutputFile $file, Manifest $manifest): string
    {
        if ($file instanceof OutputFile) {
            return $manifest->resolveAssetPath($file);
        }
        $identifier = $this->resolveSourcePath($file);
        $chunk = $manifest->getChunk($identifier);
        if ($chunk === null) {
            throw new ViteException(sprintf(
                'Invalid asset file "%s" in vite manifest file "%s%s".',
                $identifier,
                $manifest->outputDir,
                $manifest->file->locator,
            ), 1690735353);
        }
        return $manifest->resolveAssetPath($chunk->file);
    }

    public function resolveSourcePath(AssetFile $file): string
    {
        if (!PathUtility::isExtensionPath($file->locator)) {
            return $file->locator;
        }
        $absolutePath = $this->packageManager->resolvePackagePath($file->locator);
        $file = PathUtility::basename($absolutePath);
        $dir = PathUtility::dirname($absolutePath);
        $relativeDirToProjectRoot = $this->stripProjectPath($dir);
        return $relativeDirToProjectRoot . $file;
    }

    private function stripProjectPath(string $path): string
    {
        $projectPath = Environment::getProjectPath() . '/';
        if (str_starts_with($path, $projectPath)) {
            $path = substr($path, strlen($projectPath));
        }
        return rtrim($path, '/') . '/';
    }
}
