<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset;

use Praetorius\ViteAssetCollector\Asset\Manifest\Manifest;
use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

final readonly class AssetPathResolver
{
    public function __construct(private PackageManager $packageManager) {}

    public function resolveOutputPath(AssetFile|OutputFile $file, Manifest $manifest, $absolute = false): string
    {
        if ($file instanceof OutputFile) {
            $outputFile = $file;
        } else {
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
            $outputFile = $chunk->file;
        }
        $path = $manifest->resolveAssetPath($outputFile);
        return $absolute ? $path : $this->stripProjectPath($path);
    }

    public function resolveSourcePath(AssetFile $file, $absolute = false): string
    {
        $path = $file->locator;
        if (PathUtility::isExtensionPath($path)) {
            $path = $this->packageManager->resolvePackagePath($path);
        } elseif ($absolute && !PathUtility::isAbsolutePath($path)) {
            $path = GeneralUtility::getFileAbsFileName($path);
        }
        return $absolute ? $path : $this->stripProjectPath($path);
    }

    private function stripProjectPath(string $path): string
    {
        $projectPath = Environment::getProjectPath() . '/';
        if (str_starts_with($path, $projectPath)) {
            return substr($path, strlen($projectPath));
        }
        return $path;
    }
}
