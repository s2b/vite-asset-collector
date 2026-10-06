<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset;

use Praetorius\ViteAssetCollector\Asset\Manifest\Manifest;
use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;
use Psr\Http\Message\UriInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Core\Utility\PathUtility;

#[AsAlias(AssetUriGeneratorInterface::class)]
final readonly class AssetUriGenerator implements AssetUriGeneratorInterface
{
    public function __construct(private AssetPathResolverInterface $assetPathResolver) {}

    public function generateDevUri(AssetFile $file, UriInterface $devServerBase): UriInterface
    {
        return $devServerBase->withPath($devServerBase->getPath() . $this->assetPathResolver->resolveSourcePath($file));
    }

    // TODO change return type back to UriInterface and re-introduce absolute paths
    public function generateUri(AssetFile|OutputFile $file, Manifest $manifest, ?UriInterface $base = null): string
    {
        return PathUtility::getAbsoluteWebPath($this->assetPathResolver->resolveOutputPath($file, $manifest, true));
    }
}
