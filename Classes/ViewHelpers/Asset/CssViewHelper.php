<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\ViewHelpers\Asset;

use Praetorius\ViteAssetCollector\Asset\Embedding\CssEmbedding;
use Praetorius\ViteAssetCollector\ViewHelpers\AssetViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class CssViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('priority', 'bool', '', false, true);
        $this->registerArgument('inline', 'bool', '', false, false);
        $this->registerArgument('preload', 'bool', '', false, false); // TODO change default?
        $this->registerArgument('preloadImages', 'bool', '', false, false); // TODO change default?
        $this->registerArgument('preloadFonts', 'bool', '', false, false); // TODO change default?
        $this->registerArgument('ignore', 'bool', '', false, false);
        $this->registerArgument('media', 'string', '');
        $this->registerArgument('additionalAttributes', 'array', '', false, []);
    }

    public function render(): void
    {
        $this->renderingContext->getViewHelperVariableContainer()->add(
            AssetViewHelper::class,
            CssEmbedding::VariableName,
            new CssEmbedding(...$this->arguments)
        );
    }

    // TODO limit VH to vite:asset
}
