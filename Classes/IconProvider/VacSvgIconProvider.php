<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\IconProvider;

use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Service\ViteService;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;

class VacSvgIconProvider extends AbstractSvgIconProvider
{
    protected ExtensionConfiguration $extensionConfiguration;
    protected ViteService $viteService;

    /**
     * @throws \InvalidArgumentException
     */
    protected function generateMarkup(Icon $icon, array $options): string
    {
        if (empty($options['source'])) {
            throw new \InvalidArgumentException('[' . $icon->getIdentifier() . '] The option "source" is required and must not be empty', 1460976566);
        }

        $source = $this->viteService->getAssetWebPathFromManifest(
            $this->getManifest($options['manifest'] ?? ''),
            $options['source']
        );

        return '<img src="' . htmlspecialchars($this->getPublicPath($source)) . '" width="' . $icon->getDimension()->getWidth() . '" height="' . $icon->getDimension()->getHeight() . '" alt="" />';
    }

    private function getManifest(string $manifest): string
    {
        if ($manifest === '') {
            $manifest = $this->extensionConfiguration->get('vite_asset_collector', 'defaultManifest');
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
     * @throws \InvalidArgumentException
     */
    protected function generateInlineMarkup(array $options): string
    {
        if (empty($options['source'])) {
            throw new \InvalidArgumentException('The option "source" is required and must not be empty', 1690831431);
        }

        $source = $this->viteService->getAssetPathFromManifest(
            $this->getManifest($options['manifest'] ?? ''),
            $options['source']
        );

        return $this->getInlineSvg($source);
    }

    public function injectViteService(ViteService $viteService): void
    {
        $this->viteService = $viteService;
    }

    public function injectExtensionConfiguration(ExtensionConfiguration $extensionConfiguration): void
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }
}
