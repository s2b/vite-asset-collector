<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\ViewHelpers\Asset;

use Praetorius\ViteAssetCollector\Asset\Embedding\CssEmbedding;
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
 * The `<vite:asset.css>` ViewHelper allows to customize the embedding of
 * CSS assets with the `<vite:asset>` ViewHelper. It can only be used as a
 * direct child element of `<vite:asset>`.
 *
 * See `<vite:asset> <https://docs.typo3.org/permalink/praetorius/vite-asset-collector:viewhelper-asset>`_
 * for more examples.
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
 * @see https://docs.typo3.org/permalink/praetorius/vite-asset-collector:viewhelper-asset
 * @see https://docs.typo3.org/permalink/praetorius/vite-asset-collector:viewhelper-asset-css
 */
final class CssViewHelper extends AbstractViewHelper implements ViewHelperNodeInitializedEventInterface
{
    public function initializeArguments(): void
    {
        $this->registerArgument('priority', 'bool', 'Set to "false" if the CSS should be loaded with lower priority.', false, true);
        $this->registerArgument('inline', 'bool', 'Set to "true" if the CSS should be embedded inline with a <style> tag.', false, false);
        $this->registerArgument('preloadFonts', 'bool', 'Set to "false" if referenced font files shouldn not be preloaded automatically.', false, true);
        $this->registerArgument('preloadImages', 'bool', 'Set to "false" if referenced image files should not be preloaded automatically.', false, true);
        $this->registerArgument('ignore', 'bool', 'Set to "true" if the CSS should not be embedded at all.', false, false);
        $this->registerArgument('disabled', 'bool', 'Set to "true" if the <link> tag should be marked as disabled.', false, false);
        $this->registerArgument('media', 'string', 'Associated media query');
        $this->registerArgument('additionalAttributes', 'array', 'Additional attributes to be added to the <link>/<style> tag.', false, []);
    }

    public function render(): void
    {
        try {
            $cssEmbedding = new CssEmbedding(...$this->arguments);
        } catch (ViteException $e) {
            throw new InvalidArgumentException($e->getMessage(), 1793528982, $e);
        }
        $this->renderingContext?->getViewHelperVariableContainer()->add(
            AssetViewHelper::class,
            CssEmbedding::VariableName,
            $cssEmbedding,
        );
    }

    /**
     * @param array<string, NodeInterface> $arguments
     */
    public static function nodeInitializedEvent(ViewHelperNode $node, array $arguments, ParsingState $parsingState): void
    {
        $parentNode = $parsingState->getNodeFromStack();
        if (!$parentNode instanceof ViewHelperNode || $parentNode->getViewHelperClassName() !== AssetViewHelper::class) {
            throw new ParserException('<vite:asset.css> can only be used within <vite:asset> to configure CSS embedding.', 1793529908);
        }
        foreach ($parentNode->getChildNodes() as $siblingNode) {
            if ($siblingNode instanceof ViewHelperNode && $siblingNode->getViewHelperClassName() === self::class) {
                throw new ParserException('<vite:asset.css> can only be used once within <vite:asset>.', 1794141766);
            }
        }
        // @todo remove with v3
        if (array_intersect(array_keys($parentNode->getArguments()), AssetViewHelper::DeprecatedEmbeddingArguments) !== []) {
            throw new ParserException(
                'Deprecated arguments of <vite:asset> cannot be combined with <vite:asset.css>. Use <vite:asset.css> and <vite:asset.script> instead of the following arguments: ' . implode(', ', AssetViewHelper::DeprecatedEmbeddingArguments),
                1794140579,
            );
        }
    }
}
