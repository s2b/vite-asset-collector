<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\ViewHelpers\Resource;

use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Service\ViteService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * This ViewHelper extracts asset uris from a vite manifest file
 */
final class ViteViewHelper extends AbstractViewHelper
{
    protected ViteService $viteService;

    public function initializeArguments(): void
    {
        $this->registerArgument(
            'manifest',
            'string',
            'Path to vite manifest file; if omitted, default manifest from extension configuration will be used instead.'
        );
        $this->registerArgument(
            'file',
            'string',
            'Asset file for which uri should be extracted',
            true
        );
    }

    public function render(): string
    {
        return $this->viteService->getAssetPathFromManifest(
            $this->getManifest(),
            $this->arguments['file']
        );
    }

    private function getManifest(): string
    {
        $manifest = $this->arguments['manifest'] ?? $this->viteService->getDefaultManifestFile();

        if (!is_string($manifest) || $manifest === '') {
            throw new ViteException(
                sprintf(
                    'Unable to determine vite manifest from specified argument and default manifest: %s',
                    $manifest
                ),
                1684528724
            );
        }

        return $manifest;
    }

    public function injectViteService(ViteService $viteService): void
    {
        $this->viteService = $viteService;
    }
}
