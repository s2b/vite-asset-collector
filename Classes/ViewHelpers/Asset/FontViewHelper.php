<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\ViewHelpers\Asset;

use Praetorius\ViteAssetCollector\Asset\Embedding\FontPreloading;
use Praetorius\ViteAssetCollector\ViewHelpers\AssetViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Parser\Exception as ParserException;
use TYPO3Fluid\Fluid\Core\Parser\ParsingState;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperNodeInitializedEventInterface;

/**
 * The `<vite:asset.font>` ViewHelper allows to customize the preloading of
 * fonts referenced by the entry point of the `<vite:asset>` ViewHelper, for
 * example fonts used in CSS files. It can only be used as a direct child
 * element of `<vite:asset>`.
 *
 * By default, only fonts in the woff2 format are preloaded, as all current
 * browsers support it. Other formats are usually only fallbacks for legacy
 * browsers.
 *
 * See `<vite:asset> <https://docs.typo3.org/permalink/praetorius/vite-asset-collector:viewhelper-asset>`_
 * for more examples.
 *
 * Example: Disable preloading of fonts
 * ====================================
 *
 * ..  code-block:: html
 *
 *     <vite:asset entry="EXT:sitepackage/Resources/Private/JavaScript/Main.entry.js">
 *         <vite:asset.font preload="{false}" />
 *     </vite:asset>
 *
 * @see https://docs.typo3.org/permalink/praetorius/vite-asset-collector:viewhelper-asset
 * @see https://docs.typo3.org/permalink/praetorius/vite-asset-collector:viewhelper-asset-font
 */
final class FontViewHelper extends AbstractViewHelper implements ViewHelperNodeInitializedEventInterface
{
    public function initializeArguments(): void
    {
        $this->registerArgument('preload', 'bool', 'Set to "false" if referenced fonts should not be preloaded.', false, true);
        $this->registerArgument('formats', 'string', 'Comma-separated list of file extensions that should be preloaded (default: woff2). If empty, all fonts are preloaded.', false, 'woff2');
    }

    public function render(): void
    {
        $this->renderingContext?->getViewHelperVariableContainer()->add(
            AssetViewHelper::class,
            FontPreloading::VariableName,
            new FontPreloading(
                preload: $this->arguments['preload'],
                formats: GeneralUtility::trimExplode(',', $this->arguments['formats'], true),
            ),
        );
    }

    /**
     * @param array<string, NodeInterface> $arguments
     */
    public static function nodeInitializedEvent(ViewHelperNode $node, array $arguments, ParsingState $parsingState): void
    {
        $parentNode = $parsingState->getNodeFromStack();
        if (!$parentNode instanceof ViewHelperNode || $parentNode->getViewHelperClassName() !== AssetViewHelper::class) {
            throw new ParserException('<vite:asset.font> can only be used within <vite:asset> to configure font preloading.', 1794142768);
        }
        foreach ($parentNode->getChildNodes() as $siblingNode) {
            if ($siblingNode instanceof ViewHelperNode && $siblingNode->getViewHelperClassName() === self::class) {
                throw new ParserException('<vite:asset.font> can only be used once within <vite:asset>.', 1794142769);
            }
        }
        // @todo remove with v3
        if (array_intersect(array_keys($parentNode->getArguments()), AssetViewHelper::DeprecatedEmbeddingArguments) !== []) {
            throw new ParserException(
                'Deprecated arguments of <vite:asset> cannot be combined with <vite:asset.font>. Use <vite:asset.css> and <vite:asset.script> instead of the following arguments: ' . implode(', ', AssetViewHelper::DeprecatedEmbeddingArguments),
                1794142770,
            );
        }
    }
}
