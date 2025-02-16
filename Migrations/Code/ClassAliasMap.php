<?php

declare(strict_types=1);

return [
    // Register class aliases for deprecated ViewHelper names
    'Praetorius\\ViteAssetCollector\\ViewHelpers\\Asset\\ViteViewHelper' => \Praetorius\ViteAssetCollector\ViewHelpers\AssetViewHelper::class,
    'Praetorius\\ViteAssetCollector\\ViewHelpers\\Resource\\ViteViewHelper' => \Praetorius\ViteAssetCollector\ViewHelpers\UriViewHelper::class,
];
