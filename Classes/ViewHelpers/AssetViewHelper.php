<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\ViewHelpers;

use Praetorius\ViteAssetCollector\Asset\Asset;
use Praetorius\ViteAssetCollector\Asset\Embedding\ContentSecurityMode;
use Praetorius\ViteAssetCollector\Asset\Embedding\CssEmbedding;
use Praetorius\ViteAssetCollector\Asset\Embedding\ScriptEmbedding;
use Praetorius\ViteAssetCollector\Asset\Manifest\ManifestFactory;
use Praetorius\ViteAssetCollector\Utility\ViteHelper;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3Fluid\Fluid\Core\Parser\ParsingState;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperNodeInitializedEventInterface;

/**
 * The `<vite:asset>` ViewHelper embeds all JavaScript and CSS belonging to the
 * specified vite `entry` using TYPO3's AssetCollector API. It automatically
 * switches between Vite's development server and production rendering
 * (manifest.json) based on the extension's configuration.
 *
 * Assets are embedded with the following defaults:
 *
 * * Scripts are embedded as JavaScript modules using `<script type="module">`
 *   at the end of the document
 * * CSS files use `<link rel="stylesheet">` in the `<head>`
 * * Imported JavaScript modules are preloaded automatically using
 *   `<link rel="modulepreload">`
 * * Referenced images and fonts are preloaded automatically using
 *   `<link rel="preload">`
 *
 * These defaults can be configured separately for CSS and script assets by nesting
 * `<vite:asset.script> <https://docs.typo3.org/permalink/praetorius/vite-asset-collector:viewhelper-asset-script>`_
 * and/or
 * `<vite:asset.css> <https://docs.typo3.org/permalink/praetorius/vite-asset-collector:viewhelper-asset-css>`_
 * inside `<vite:asset>` (see examples below).
 *
 * Example: Basic usage
 * ====================
 *
 * ..  code-block:: html
 *
 *     <vite:asset entry="EXT:sitepackage/Resources/Private/JavaScript/Main.entry.js" />
 *
 * Example: Inlining CSS
 * =====================
 *
 * ..  code-block:: html
 *
 *     <vite:asset entry="EXT:sitepackage/Resources/Private/JavaScript/Main.entry.js">
 *         <vite:asset.css inline="{true}" />
 *     </vite:asset>
 *
 * Example: Disable automatic preloading
 * =====================================
 *
 * ..  code-block:: html
 *
 *     <vite:asset entry="EXT:sitepackage/Resources/Private/JavaScript/Main.entry.js">
 *         <vite:asset.script preloadImports="{false}" />
 *         <vite:asset.css preloadFonts="{false}" preloadImages="{false}" />
 *     </vite:asset>
 *
 * Example: Media query for CSS
 * ============================
 *
 * ..  code-block:: html
 *
 *     <vite:asset entry="EXT:sitepackage/Resources/Private/Css/Print.entry.css">
 *         <vite:asset.css media="print" />
 *     </vite:asset>
 *
 * @see https://docs.typo3.org/permalink/praetorius/vite-asset-collector:viewhelper-asset
 */
final class AssetViewHelper extends AbstractViewHelper implements ViewHelperNodeInitializedEventInterface
{
    public function __construct(
        private readonly ViteHelper $viteHelper,
        private readonly ManifestFactory $manifestFactory,
    ) {}

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
        $this->registerArgument('csp', 'bool', 'Whether to collect a CSP hash value for this asset (default: true for external files, false for inline)', false, null);
        // TODO remove with v3
        $this->registerArgument('useNonce', 'bool', 'Deprecated, use csp instead. (Whether to use the global nonce value)', false, null);
        $this->registerArgument('scriptTagAttributes', 'array', 'Deprecated, use <vite:asset.script> instead. (Additional HTML attributes for script tags)', false, []);
        $this->registerArgument('addCss', 'boolean', 'Deprecated, use <vite:asset.css> instead. (If set to "false", CSS files associated with the entry point won\'t be added to the asset collector)', false, true);
        $this->registerArgument('inlineCss', 'boolean', 'Deprecated, use <vite:asset.css> instead. (If set to "true", CSS will be added as inline <style> tag.)', false, false);
        $this->registerArgument('cssTagAttributes', 'array', 'Deprecated, use <vite:asset.css> instead. (Additional HTML attributes for css link tags.)', false, []);
        $this->registerArgument(
            'priority',
            'boolean',
            'Deprecated, use <vite:asset.css> for CSS and <vite:asset.script> for scripts instead. (Include assets before other assets in HTML)',
            false,
            false
        );
    }

    public function render(): void
    {
        $entry = $this->arguments['entry'];
        if ($entry === null) {
            $manifest = $this->manifestFactory->createFromConfiguredPath($this->arguments['manifest']);
            $entry = $manifest->getOnlyEntrypoint()->identifier;
        }

        $cssEmbedding = $this->createFallbackCssEmbedding();
        $scriptEmbedding = $this->createFallbackScriptEmbedding();
        $viewHelperVariableContainer = $this->renderingContext?->getViewHelperVariableContainer();
        if ($viewHelperVariableContainer) {
            $viewHelperVariableContainer->add(self::class, CssEmbedding::VariableName, $cssEmbedding);
            $viewHelperVariableContainer->add(self::class, ScriptEmbedding::VariableName, $scriptEmbedding);
            $this->renderChildren();
            /** @var CssEmbedding */
            $cssEmbedding = $viewHelperVariableContainer->get(self::class, CssEmbedding::VariableName);
            /** @var ScriptEmbedding */
            $scriptEmbedding = $viewHelperVariableContainer->get(self::class, ScriptEmbedding::VariableName);
        }
        $asset = Asset::create(
            entry: $entry,
            cssEmbedding: $cssEmbedding,
            scriptEmbedding: $scriptEmbedding,
            csp: ContentSecurityMode::fromViewHelperArgument($this->arguments['useNonce'] ?? $this->arguments['csp']),
        );
        $this->viteHelper->renderAsset($asset, $this->getRequest(), $this->arguments['manifest']);
    }

    /**
     * @todo remove with v3
     */
    private function createFallbackCssEmbedding(): CssEmbedding
    {
        return new CssEmbedding(
            priority: $this->arguments['inlineCss'] ? $this->arguments['priority'] : true,
            ignore: !$this->arguments['addCss'],
            inline: $this->arguments['inlineCss'],
            additionalAttributes: $this->arguments['cssTagAttributes'],
        );
    }

    /**
     * @todo remove with v3
     */
    private function createFallbackScriptEmbedding(): ScriptEmbedding
    {
        return new ScriptEmbedding(
            priority: $this->arguments['priority'],
            additionalAttributes: $this->arguments['scriptTagAttributes'],
        );
    }

    private function getRequest(): ServerRequestInterface
    {
        $request = $this->renderingContext?->getAttribute(ServerRequestInterface::class);
        if ($request === null) {
            throw new \RuntimeException('Request is required for <vite:asset> ViewHelper.', 1793551383);
        }
        return $request;
    }

    /**
     * @param array<string, NodeInterface> $arguments Unevaluated ViewHelper arguments
     * @todo remove with v3
     */
    public static function nodeInitializedEvent(ViewHelperNode $node, array $arguments, ParsingState $parsingState): void
    {
        if (array_key_exists('useNonce', $arguments)) {
            trigger_error(
                'The "useNonce" argument of <vite:asset> is deprecated. Use "csp" instead.',
                E_USER_DEPRECATED
            );
        }
        if (array_key_exists('priority', $arguments)) {
            trigger_error(
                'The "priority" argument of <vite:asset> is deprecated. Use <vite:asset.script> and/or <vite:asset.css> instead to configure embedding.',
                E_USER_DEPRECATED
            );
        }
        if (array_key_exists('scriptTagAttributes', $arguments)) {
            trigger_error(
                'The "scriptTagAttributes" argument of <vite:asset> is deprecated. Use <vite:asset.script> instead to configure script embedding.',
                E_USER_DEPRECATED
            );
        }
        foreach (['addCss', 'inlineCss', 'cssTagAttributes'] as $argumentName) {
            if (array_key_exists($argumentName, $arguments)) {
                trigger_error(
                    'The "' . $argumentName . '" argument of <vite:asset> is deprecated. Use <vite:asset.css> instead to configure CSS embedding.',
                    E_USER_DEPRECATED
                );
            }
        }
    }
}
