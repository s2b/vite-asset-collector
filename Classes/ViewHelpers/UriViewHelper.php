<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\ViewHelpers;

use Praetorius\ViteAssetCollector\Asset\AssetFile;
use Praetorius\ViteAssetCollector\Asset\AssetUriGeneratorInterface;
use Praetorius\ViteAssetCollector\Asset\Manifest\ManifestFactory;
use Praetorius\ViteAssetCollector\Context\ViteContext;
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
 * Solution for TYPO3 v14+
 * -----------------------
 *
 * ..  code-block:: html
 *     <f:page.headerData>
 *         <link
 *             rel="preload"
 *             href="{vite:uri(file: 'EXT:sitepackage/Resources/Private/Fonts/webfont.woff2')}"
 *             as="font"
 *             type="font/woff2"
 *             crossorigin
 *         />
 *     </f:page.headerData>
 *
 * Solution for TYPO3 v13
 * ----------------------
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
    public function __construct(
        private AssetUriGeneratorInterface $assetUriGenerator,
        private ManifestFactory $manifestFactory,
    ) {}

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
        $viteContext = $this->getViteContext();
        $file = AssetFile::create($this->arguments['file']);
        if ($viteContext?->useDevServer()) {
            return (string)$this->assetUriGenerator->generateDevUri($file, $viteContext->getDevServer(), $this->getRequest());
        }
        $manifest = $this->manifestFactory->createFromConfiguredPath($this->arguments['manifest']);
        return (string)$this->assetUriGenerator->generateUri($file, $manifest, $this->getRequest());
    }

    private function getViteContext(): ?ViteContext
    {
        return $this->getRequest()->getAttribute('vite.context');
    }

    private function getRequest(): ServerRequestInterface
    {
        return $this->renderingContext->getAttribute(ServerRequestInterface::class);
    }
}
