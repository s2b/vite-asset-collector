<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\ViewHelpers\Asset;

use Praetorius\ViteAssetCollector\Asset\Embedding\ScriptEmbedding;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\ViewHelpers\AssetViewHelper;
use TYPO3Fluid\Fluid\Core\Parser\Exception as ParserException;
use TYPO3Fluid\Fluid\Core\Parser\ParsingState;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\InvalidArgumentException;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperNodeInitializedEventInterface;

/**
 * The `<vite:asset.script>` ViewHelper allows to customize the embedding of
 * script assets with the `<vite:asset>` ViewHelper. It can only be used as a
 * direct child element of `<vite:asset>`.
 *
 * See `<vite:asset> <https://docs.typo3.org/permalink/praetorius/vite-asset-collector:viewhelper-asset>`_
 * for more examples.
 *
 * Example: Disable preloading of imports
 * ======================================
 *
 * ..  code-block:: html
 *
 *     <vite:asset entry="EXT:sitepackage/Resources/Private/JavaScript/Main.entry.js">
 *         <vite:asset.script preloadImports="{false}" />
 *     </vite:asset>
 *
 * @see https://docs.typo3.org/permalink/praetorius/vite-asset-collector:viewhelper-asset
 * @see https://docs.typo3.org/permalink/praetorius/vite-asset-collector:viewhelper-asset-script
 */
final class ScriptViewHelper extends AbstractViewHelper implements ViewHelperNodeInitializedEventInterface
{
    public function initializeArguments(): void
    {
        $this->registerArgument('priority', 'bool', 'Set to "true" if the script should be embedded in the <head> of the document.', false, false);
        $this->registerArgument('preloadImports', 'bool', 'Set to "false" if imported scripts should not be preloaded automatically.', false, true);
        $this->registerArgument('ignore', 'bool', 'Set to "true" if the script should not be embedded at all.', false, false);
        $this->registerArgument('module', 'bool', 'Set to "false" if the script should be embedded as a classic script instead of a JavaScript module (e. g. for non-ESM build output).', false, true);
        $this->registerArgument('async', 'bool', 'Set to "true" if the script should be executed as soon as it is loaded. Can be used for JavaScript modules and classic scripts.', false, false);
        $this->registerArgument('defer', 'bool', 'Set to "true" if the classic script should be executed after the document has been parsed. JavaScript modules are always deferred, so this can only be used with module="{false}".', false, false);
        $this->registerArgument('nomodule', 'bool', 'Set to "true" if the classic script should be used as an alternative to a JavaScript module. Can only be used with module="{false}".', false, false);
        $this->registerArgument('additionalAttributes', 'array', 'Additional attributes to be added to the <script> tag.', false, []);
    }

    public function render(): void
    {
        try {
            $scriptEmbedding = new ScriptEmbedding(...$this->arguments);
        } catch (ViteException $e) {
            throw new InvalidArgumentException($e->getMessage(), 1793528981, $e);
        }
        $this->renderingContext?->getViewHelperVariableContainer()->add(
            AssetViewHelper::class,
            ScriptEmbedding::VariableName,
            $scriptEmbedding
        );
    }

    /**
     * @param array<string, NodeInterface> $arguments
     */
    public static function nodeInitializedEvent(ViewHelperNode $node, array $arguments, ParsingState $parsingState): void
    {
        $parentNode = $parsingState->getNodeFromStack();
        if (!$parentNode instanceof ViewHelperNode || $parentNode->getViewHelperClassName() !== AssetViewHelper::class) {
            throw new ParserException('<vite:asset.script> can only be used within <vite:asset> to configure script embedding.', 1793529909);
        }
    }
}
