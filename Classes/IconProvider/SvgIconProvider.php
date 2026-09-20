<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\IconProvider;

use Praetorius\ViteAssetCollector\Asset\AssetFile;
use Praetorius\ViteAssetCollector\Asset\AssetPathResolver;
use Praetorius\ViteAssetCollector\Asset\AssetUriGenerator;
use Praetorius\ViteAssetCollector\Asset\Manifest\ManifestFactory;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;

#[Autoconfigure(public: true)]
class SvgIconProvider extends AbstractSvgIconProvider
{
    public function __construct(
        private AssetPathResolver $assetPathResolver,
        private AssetUriGenerator $assetUriGenerator,
        private ManifestFactory $manifestFactory,
    ) {}

    /**
     * @throws \InvalidArgumentException|ViteException
     */
    protected function generateMarkup(Icon $icon, array $options): string
    {
        if (empty($options['source'])) {
            throw new \InvalidArgumentException('[' . $icon->getIdentifier() . '] The option "source" is required and must not be empty', 1460976566);
        }

        $source = (string)$this->assetUriGenerator->generateUri(
            AssetFile::create($options['source']),
            $this->manifestFactory->createFromConfiguredPath($options['manifest'] ?? null),
        );

        return '<img src="' . htmlspecialchars($source) . '" width="' . $icon->getDimension()->getWidth() . '" height="' . $icon->getDimension()->getHeight() . '" alt="" />';
    }

    /**
     * @throws \InvalidArgumentException|ViteException|ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException
     */
    protected function generateInlineMarkup(array $options): string
    {
        if (empty($options['source'])) {
            throw new \InvalidArgumentException('The option "source" is required and must not be empty', 1690831431);
        }

        $source = $this->assetPathResolver->resolveOutputPath(
            AssetFile::create($options['source']),
            $this->manifestFactory->createFromConfiguredPath($options['manifest'] ?? null),
        );

        return $this->getInlineSvg($source);
    }
}
