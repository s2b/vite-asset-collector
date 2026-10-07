<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset;

use Praetorius\ViteAssetCollector\Asset\Manifest\Manifest;
use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Core\SystemResource\Exception\SystemResourceException;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\Publishing\UriGenerationOptions;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\Utility\PathUtility;

#[AsAlias(AssetUriGeneratorInterface::class)]
final readonly class AssetUriGenerator implements AssetUriGeneratorInterface
{
    public function __construct(
        private AssetPathResolverInterface $assetPathResolver,
        // TODO remove nullable once support for v13 is droppped
        private ?SystemResourceFactory $systemResourceFactory,
        private ?SystemResourcePublisherInterface $systemResourcePublisher,
    ) {}

    public function generateDevUri(AssetFile $file, UriInterface $devServerBase, ?ServerRequestInterface $request): UriInterface
    {
        return $devServerBase->withPath($devServerBase->getPath() . $this->assetPathResolver->resolveSourcePath($file));
    }

    public function generateUri(AssetFile|OutputFile $file, Manifest $manifest, ?ServerRequestInterface $request): UriInterface
    {
        // TODO remove once support for v13 is droppped
        if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() < 14) {
            return new \Praetorius\ViteAssetCollector\Http\Uri(PathUtility::getAbsoluteWebPath($this->assetPathResolver->resolveOutputPath($file, $manifest, true)));
        }
        $resolvedFile = $this->assetPathResolver->resolveOutputPath($file, $manifest);
        try {
            $systemResource = $this->systemResourceFactory->createPublicResource($resolvedFile);
        } catch (SystemResourceException) {
            $systemResource = $this->systemResourceFactory->createPublicResource('URI:' . $resolvedFile);
        }
        return $this->systemResourcePublisher->generateUri($systemResource, $request, new UriGenerationOptions(cacheBusting: false));
    }
}
