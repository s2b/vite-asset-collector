<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\ViewHelpers\Asset;

use Praetorius\ViteAssetCollector\Asset\Embedding\ImagePreloading;
use Praetorius\ViteAssetCollector\ViewHelpers\AssetViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Parser\Exception as ParserException;
use TYPO3Fluid\Fluid\Core\Parser\ParsingState;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperNodeInitializedEventInterface;

/**
 * The `<vite:asset.image>` ViewHelper allows to customize the preloading of
 * images referenced by the entry point of the `<vite:asset>` ViewHelper, for
 * example images used in CSS files. It can only be used as a direct child
 * element of `<vite:asset>`.
 *
 * By default, no images are preloaded, as images referenced in CSS files
 * are rarely critical for the initial rendering of the page.
 *
 * See `<vite:asset> <https://docs.typo3.org/permalink/praetorius/vite-asset-collector:viewhelper-asset>`_
 * for more examples.
 *
 * Example: Preload SVG images
 * ===========================
 *
 * ..  code-block:: html
 *
 *     <vite:asset entry="EXT:sitepackage/Resources/Private/JavaScript/Main.entry.js">
 *         <vite:asset.image preload="{true}" formats="svg" />
 *     </vite:asset>
 *
 * @see https://docs.typo3.org/permalink/praetorius/vite-asset-collector:viewhelper-asset
 * @see https://docs.typo3.org/permalink/praetorius/vite-asset-collector:viewhelper-asset-image
 */
final class ImageViewHelper extends AbstractViewHelper implements ViewHelperNodeInitializedEventInterface
{
    public function initializeArguments(): void
    {
        $this->registerArgument('preload', 'bool', 'Set to "true" if referenced images should be preloaded.', false, false);
        $this->registerArgument('formats', 'string', 'Comma-separated list of file extensions that should be preloaded. If empty, all images are preloaded.', false, '');
    }

    public function render(): void
    {
        $this->renderingContext?->getViewHelperVariableContainer()->add(
            AssetViewHelper::class,
            ImagePreloading::VariableName,
            new ImagePreloading(
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
            throw new ParserException('<vite:asset.image> can only be used within <vite:asset> to configure image preloading.', 1794142771);
        }
        foreach ($parentNode->getChildNodes() as $siblingNode) {
            if ($siblingNode instanceof ViewHelperNode && $siblingNode->getViewHelperClassName() === self::class) {
                throw new ParserException('<vite:asset.image> can only be used once within <vite:asset>.', 1794142772);
            }
        }
        // @todo remove with v3
        if (array_intersect(array_keys($parentNode->getArguments()), AssetViewHelper::DeprecatedEmbeddingArguments) !== []) {
            throw new ParserException(
                'Deprecated arguments of <vite:asset> cannot be combined with <vite:asset.image>. Use <vite:asset.css> and <vite:asset.script> instead of the following arguments: ' . implode(', ', AssetViewHelper::DeprecatedEmbeddingArguments),
                1794142773,
            );
        }
    }
}
