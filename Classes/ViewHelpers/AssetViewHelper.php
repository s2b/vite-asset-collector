<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\ViewHelpers;

use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Service\ViteService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * The `vite:asset` ViewHelper embeds all JavaScript and CSS belonging to the
 * specified vite `entry` using TYPO3's AssetCollector API.
 *
 * Example
 * =======
 *
 * ..  code-block:: html
 *
 *     <html
 *         data-namespace-typo3-fluid="true"
 *         xmlns:vite="http://typo3.org/ns/Praetorius/ViteAssetCollector/ViewHelpers"
 *     >
 *
 *     <vite:asset
 *         entry="EXT:sitepackage/Resources/Private/JavaScript/Main.entry.js"
 *     />
 *
 * Advanced Example
 * ================
 *
 * ..  code-block:: html
 *
 *     <html
 *         data-namespace-typo3-fluid="true"
 *         xmlns:vite="http://typo3.org/ns/Praetorius/ViteAssetCollector/ViewHelpers"
 *     >
 *
 *     <vite:asset
 *         manifest="EXT:sitepackage/Resources/Public/Vite/.vite/manifest.json"
 *         entry="EXT:sitepackage/Resources/Private/JavaScript/Main.entry.js"
 *         scriptTagAttributes="{
 *             type: 'text/javascript',
 *             async: 1
 *         }"
 *         cssTagAttributes="{
 *             media: 'print'
 *         }"
 *         priority="1"
 *     />
 */
final class AssetViewHelper extends AbstractViewHelper
{
    protected ViteService $viteService;

    public function initializeArguments(): void
    {
        $this->registerArgument(
            'manifest',
            'string',
            'Path to your manifest.json file. If omitted, default manifest from extension configuration will be used instead.'
        );
        $this->registerArgument(
            'entry',
            'string',
            'Identifier of the desired vite entrypoint; this is the value specified as "input" in the vite configuration file. Can be omitted if manifest file exists and only one entrypoint is present.',
        );
        $this->registerArgument('devTagAttributes', 'array', 'HTML attributes that should be added to script tags that point to the vite dev server', false, []);
        $this->registerArgument('scriptTagAttributes', 'array', 'HTML attributes that should be added to script tags for built JavaScript assets', false, []);
        $this->registerArgument('addCss', 'boolean', 'If set to "false", CSS files associated with the entry point won\'t be added to the asset collector', false, true);
        $this->registerArgument('cssTagAttributes', 'array', 'Additional attributes for css link tags.', false, []);
        $this->registerArgument(
            'priority',
            'boolean',
            'Include assets before other assets in HTML',
            false,
            false
        );
        $this->registerArgument('useNonce', 'bool', 'Whether to use the global nonce value', false, false);
    }

    public function render(): string
    {
        $assetOptions = [
            'priority' => $this->arguments['priority'],
            'useNonce' => $this->arguments['useNonce'],
        ];

        $manifest = $this->getManifest();

        $entry = $this->arguments['entry'];
        $entry ??= $this->viteService->determineEntrypointFromManifest($manifest);

        if ($this->viteService->useDevServer()) {
            $this->viteService->addAssetsFromDevServer(
                $this->viteService->determineDevServer($this->getRequest()),
                $entry,
                $assetOptions,
                $this->arguments['devTagAttributes'],
                $this->arguments['devTagAttributes']
            );
        } else {
            $this->viteService->addAssetsFromManifest(
                $manifest,
                $entry,
                $this->arguments['addCss'],
                $assetOptions,
                $this->arguments['scriptTagAttributes'],
                $this->arguments['cssTagAttributes']
            );
        }
        return '';
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
        // This is a fallback for TYPO3 < 13.3
        if (
            !method_exists($this->renderingContext, 'getAttribute') ||
            !method_exists($this->renderingContext, 'hasAttribute') ||
            !$this->renderingContext->hasAttribute(ServerRequestInterface::class)
        ) {
            /** @var RenderingContext */
            $renderingContext = $this->renderingContext;
            return $renderingContext->getRequest();
        }
        return $this->renderingContext->getAttribute(ServerRequestInterface::class);
    }

    public function injectViteService(ViteService $viteService): void
    {
        $this->viteService = $viteService;
    }
}
