<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\ViewHelpers;

use Praetorius\ViteAssetCollector\Asset\Asset;
use Praetorius\ViteAssetCollector\Asset\AssetRendererInterface;
use Praetorius\ViteAssetCollector\Asset\Embedding\ContentSecurityMode;
use Praetorius\ViteAssetCollector\Asset\Embedding\CssEmbedding;
use Praetorius\ViteAssetCollector\Asset\Embedding\ScriptEmbedding;
use Praetorius\ViteAssetCollector\Asset\Manifest\ManifestFactory;
use Praetorius\ViteAssetCollector\Context\ViteContext;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3Fluid\Fluid\Core\Parser\ParsingState;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperNodeInitializedEventInterface;

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
final class AssetViewHelper extends AbstractViewHelper implements ViewHelperNodeInitializedEventInterface
{
    public function __construct(
        private readonly ManifestFactory $manifestFactory,
        private readonly AssetRendererInterface $assetRenderer,
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
        $this->registerArgument('useNonce', 'bool', 'Whether to use the global nonce value (deprecated, use csp instead)', false, null);
        $this->registerArgument('scriptTagAttributes', 'array', 'Additional HTML attributes for script tags', false, []);
        $this->registerArgument('addCss', 'boolean', 'If set to "false", CSS files associated with the entry point won\'t be added to the asset collector', false, true);
        $this->registerArgument('inlineCss', 'boolean', 'If set to "true", CSS will be added as inline <style> tag.', false, false);
        $this->registerArgument('cssTagAttributes', 'array', 'Additional HTML attributes for css link tags.', false, []);
        $this->registerArgument(
            'priority',
            'boolean',
            'Include assets before other assets in HTML',
            false,
            false
        );
    }

    public function render(): void
    {
        $viteContext = $this->getViteContext();
        $manifest = null;
        $entry = $this->arguments['entry'];
        if (!$viteContext?->useDevServer() || $entry === null) {
            $manifest = $this->manifestFactory->createFromConfiguredPath($this->arguments['manifest']);
            $entry ??= $manifest->getOnlyEntrypoint()->identifier;
        }

        $asset = Asset::create(
            entry: $entry,
            cssEmbedding: $this->createFallbackCssEmbedding(),
            scriptEmbedding: $this->createFallbackScriptEmbedding(),
            csp: ContentSecurityMode::fromViewHelperArgument($this->arguments['useNonce'] ?? $this->arguments['csp']),
        );

        if ($viteContext?->useDevServer()) {
            $this->assetRenderer->renderDevAsset($asset, $viteContext->getDevServer(), $this->getRequest());
        } else {
            $this->assetRenderer->renderAsset($asset, $manifest, $this->getRequest());
        }
    }

    private function createFallbackCssEmbedding(): CssEmbedding
    {
        return new CssEmbedding(
            priority: $this->arguments['priority'],
            ignore: !$this->arguments['addCss'],
            inline: $this->arguments['inlineCss'],
            additionalAttributes: $this->arguments['cssTagAttributes'],
        );
    }

    private function createFallbackScriptEmbedding(): ScriptEmbedding
    {
        return new ScriptEmbedding(
            priority: $this->arguments['priority'],
            additionalAttributes: $this->arguments['scriptTagAttributes'],
        );
    }

    private function getViteContext(): ?ViteContext
    {
        return $this->getRequest()->getAttribute('vite.context');
    }

    private function getRequest(): ServerRequestInterface
    {
        return $this->renderingContext->getAttribute(ServerRequestInterface::class);
    }

    /**
     * @param array<string, NodeInterface> $arguments Unevaluated ViewHelper arguments
     */
    public static function nodeInitializedEvent(ViewHelperNode $node, array $arguments, ParsingState $parsingState): void
    {
        if ($node->getName() === 'asset.vite') {
            trigger_error(
                'ViewHelper <vac:asset.vite> has been renamed to <vite:asset>. The old name is deprecated and will be removed with v2 of EXT:vite_asset_collector.',
                E_USER_DEPRECATED,
            );
        }
        if (array_key_exists('useNonce', $arguments)) {
            trigger_error(
                'The "useNonce" argument of <vite:asset> is deprecated. Use "csp" instead.',
                E_USER_DEPRECATED
            );
        }
    }
}
