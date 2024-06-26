<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Configuration;

use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Service\ViteService;
use TYPO3\CMS\Core\Configuration\Processor\Placeholder\PlaceholderProcessorInterface;

final class VitePlaceholderProcessor implements PlaceholderProcessorInterface
{
    /**
     * Regular expression to support the following syntax variants:
     *
     * - %vite(path/to/file.css)
     * - %vite('path/to/file.css')%
     * - %vite("path/to/file.css")%
     * - %vite(path/to/file.css, path/to/manifest.json)%
     * - %vite('path/to/file.css', 'path/to/manifest.json')%
     * - %vite("path/to/file.css", "path/to/manifest.json")%
     */
    public const PLACEHOLDER_PATTERN = '^[\'"]?([^(]*?)[\'"]?(?:\s*,\s*[\'"]?([^(]*?)[\'"]?)?$';

    public function __construct(
        private readonly ViteService $viteService
    ) {}

    public function canProcess(string $placeholder, array $referenceArray): bool
    {
        return str_starts_with($placeholder, '%vite(');
    }

    public function process(string $value, array $referenceArray): string
    {
        preg_match('/' . self::PLACEHOLDER_PATTERN . '/', $value, $matches);
        if (empty($matches)) {
            return '';
        }

        $assetFile = $matches[1];
        $manifest = $matches[2] ?? $this->viteService->getDefaultManifestFile();

        if (!is_string($manifest) || $manifest === '') {
            throw new ViteException(
                sprintf(
                    'Unable to determine vite manifest from specified argument and default manifest: %s',
                    $manifest
                ),
                1694537554
            );
        }

        return $this->viteService->getAssetPathFromManifest($manifest, $assetFile);
    }
}
