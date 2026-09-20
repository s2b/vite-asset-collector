<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\ViewHelpers\Asset;

use Praetorius\ViteAssetCollector\Asset\Embedding\ScriptEmbedding;
use Praetorius\ViteAssetCollector\Asset\Embedding\ScriptLoading;
use Praetorius\ViteAssetCollector\ViewHelpers\AssetViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class ScriptViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('priority', 'bool', '', false, false);
        $this->registerArgument('inline', 'bool', '', false, false);
        $this->registerArgument('preload', 'bool', '', false, false); // TODO change default?
        $this->registerArgument('ignore', 'bool', '', false, false);
        $this->registerArgument('loading', 'string', '', false, ScriptLoading::Module->value);
        $this->registerArgument('nomodule', 'bool', '', false, false);
        $this->registerArgument('additionalAttributes', 'array', '', false, []);
    }

    public function render(): void
    {
        $this->arguments['loading'] = ScriptLoading::from($this->arguments['loading']);
        $this->renderingContext->getViewHelperVariableContainer()->add(
            AssetViewHelper::class,
            ScriptEmbedding::VariableName,
            new ScriptEmbedding(...$this->arguments)
        );
    }

    // TODO limit VH to vite:asset
}
