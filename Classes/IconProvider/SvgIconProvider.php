<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\IconProvider;

use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Service\ViteService;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;

class SvgIconProvider extends AbstractSvgIconProvider
{
    public function __construct(
        private readonly ViteService $viteService
    ) {}

    /**
     * @throws \InvalidArgumentException|ViteException
     */
    protected function generateMarkup(Icon $icon, array $options): string
    {
        if (empty($options['source'])) {
            throw new \InvalidArgumentException('[' . $icon->getIdentifier() . '] The option "source" is required and must not be empty', 1460976566);
        }

        $source = $this->viteService->getAssetPathFromManifest(
            $this->getManifest($options['manifest'] ?? ''),
            $options['source']
        );

        return '<img src="' . htmlspecialchars($this->getPublicPath($source)) . '" width="' . $icon->getDimension()->getWidth() . '" height="' . $icon->getDimension()->getHeight() . '" alt="" />';
    }

    private function getManifest(string $manifest): string
    {
        if ($manifest === '') {
            $manifest = $this->viteService->getDefaultManifestFile();
        }

        if (!is_string($manifest) || $manifest === '') {
            throw new ViteException(
                sprintf(
                    'Unable to determine vite manifest from specified argument and default manifest: %s',
                    $manifest
                ),
                1684528724
            );
        }

        return $manifest;
    }

    /**
     * @throws \InvalidArgumentException|ViteException|ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException
     */
    protected function generateInlineMarkup(array $options): string
    {
        if (empty($options['source'])) {
            throw new \InvalidArgumentException('The option "source" is required and must not be empty', 1690831431);
        }

        $source = $this->viteService->getAssetPathFromManifest(
            $this->getManifest($options['manifest'] ?? ''),
            $options['source'],
            false
        );

        return $this->getInlineSvg($source);
    }
}
