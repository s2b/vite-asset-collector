<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\ViewHelpers\Resource;

use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Service\ViteService;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * This ViewHelper creates an uri to a specific asset file
 */
final class ViteViewHelper extends AbstractViewHelper
{
    protected ViteService $viteService;

    /**
     * @var RenderingContext
     */
    protected $renderingContext;

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
            'Asset file for which uri should be created',
            true
        );
    }

    public function render(): string
    {
        if ($this->viteService->useDevServer()) {
            return $this->viteService->getAssetPathFromDevServer(
                $this->viteService->determineDevServer($this->renderingContext->getRequest()),
                $this->arguments['file']
            );
        }

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
