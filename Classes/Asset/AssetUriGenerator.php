<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset;

use Praetorius\ViteAssetCollector\Asset\Manifest\Manifest;
use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\PathUtility;

final readonly class AssetUriGenerator
{
    public function __construct(private AssetPathResolver $assetPathResolver) {}

    public function generateDevUri(AssetFile $file, UriInterface $devServerBase): UriInterface
    {
        return $devServerBase->withPath($this->assetPathResolver->resolveSourcePath($file));
    }

    public function generateUri(AssetFile|OutputFile $file, Manifest $manifest, ?UriInterface $base = null): UriInterface
    {
        $base ??= new Uri();
        return $base->withPath(PathUtility::getAbsoluteWebPath($this->assetPathResolver->resolveOutputPath($file, $manifest)));
    }
}
