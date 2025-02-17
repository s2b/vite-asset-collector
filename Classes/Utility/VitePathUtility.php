<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Utility;

final readonly class VitePathUtility
{
    public static function isCssFile(string $fileName): bool
    {
        return preg_match('/\.(css|less|sass|scss|styl|stylus|pcss|postcss)$/', $fileName) === 1;
    }
}
