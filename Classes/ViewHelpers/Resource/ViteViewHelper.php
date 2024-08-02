<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\ViewHelpers\Resource;

use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Service\ViteService;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * The `resource.vite` ViewHelper extracts the uri to one specific asset file from a vite
 * manifest file. If the dev server is used, the dev server uri to the resource is returned.
 *
 * Example
 * =======
 *
 * This can be used to preload certain assets in the HTML `<head>` tag:
 *
 * ..  code-block:: html
 *
 *     <html
 *         data-namespace-typo3-fluid="true"
 *         xmlns:vac="http://typo3.org/ns/Praetorius/ViteAssetCollector/ViewHelpers"
 *     >
 *
 *     <f:section name="HeaderAssets">
 *         <link
 *             rel="preload"
 *             href="{vac:resource.vite(file: 'EXT:sitepackage/Resources/Private/Fonts/webfont.woff2')}"
 *             as="font"
 *             type="font/woff2"
 *             crossorigin
 *         />
 *     </f:section>
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
            'Path to vite manifest file; if omitted, default manifest from extension configuration will be used instead'
        );
        $this->registerArgument(
            'file',
            'string',
            'Identifier of the desired asset file for which a uri should be generated',
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
