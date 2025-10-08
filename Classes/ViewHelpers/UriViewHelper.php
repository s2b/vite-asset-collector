<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\ViewHelpers;

use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Service\ViteService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * The `vite:uri` ViewHelper extracts the uri to one specific asset file from a vite
 * manifest file. If the dev server is used, the dev server uri to the resource is returned.
 *
 * Example
 * =======
 *
 * This can be used to preload certain assets in the HTML `<head>` tag.
 *
 * First, add a Fluid template to your TypoScript setup, for example:
 *
 * ..  code-block:: typoscript
 *
 *     page.headerData {
 *         10 = FLUIDTEMPLATE
 *         10 {
 *             file = EXT:sitepackage/Resources/Private/Templates/HeaderData.html
 *         }
 *     }
 *
 * Then create the HeaderData template:
 *
 * ..  code-block:: html
 *     :caption: EXT:sitepackage/Resources/Private/Templates/HeaderData.html
 *
 *     <html
 *         data-namespace-typo3-fluid="true"
 *         xmlns:vite="http://typo3.org/ns/Praetorius/ViteAssetCollector/ViewHelpers"
 *     >
 *
 *     <link
 *         rel="preload"
 *         href="{vite:uri(file: 'EXT:sitepackage/Resources/Private/Fonts/webfont.woff2')}"
 *         as="font"
 *         type="font/woff2"
 *         crossorigin
 *     />
 *
 *     </html>
 */
final class UriViewHelper extends AbstractViewHelper
{
    public function __construct(private readonly ViteService $viteService) {}

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
                $this->viteService->determineDevServer($this->getRequest()),
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

    private function getRequest(): ServerRequestInterface
    {
        return $this->renderingContext->getAttribute(ServerRequestInterface::class);
    }
}
