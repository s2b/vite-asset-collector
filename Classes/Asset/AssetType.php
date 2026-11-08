<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset;

enum AssetType
{
    case Css;
    case Script;
    case Font;
    case Svg;
    case Image;
    case Other;

    public static function fromFileExtension(string $extension): self
    {
        return match (strtolower($extension)) {
            'css', 'less', 'sass', 'scss', 'styl', 'stylus', 'pcss', 'postcss' => self::Css,
            'js', 'mjs', 'cjs', 'ts', 'mts', 'cts', 'jsx', 'tsx', 'svelte', 'vue' => self::Script,
            'woff2', 'woff', 'ttf', 'otf' => self::Font,
            'svg' => self::Svg,
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'apng', 'avif', 'jxl' => self::Image,
            default => self::Other,
        };
    }
}
