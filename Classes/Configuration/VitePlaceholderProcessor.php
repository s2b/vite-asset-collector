<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Configuration;

use Praetorius\ViteAssetCollector\Asset\AssetFile;
use Praetorius\ViteAssetCollector\Asset\AssetUriGenerator;
use Praetorius\ViteAssetCollector\Asset\Manifest\ManifestFactory;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Configuration\Processor\Placeholder\PlaceholderProcessorInterface;

#[Autoconfigure(public: true)]
final readonly class VitePlaceholderProcessor implements PlaceholderProcessorInterface
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
        private AssetUriGenerator $assetUriGenerator,
        private ManifestFactory $manifestFactory,
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

        $assetFile = AssetFile::create($matches[1]);
        $manifest = $this->manifestFactory->createFromConfiguredPath($matches[2] ?? null);
        return (string)$this->assetUriGenerator->generateUri($assetFile, $manifest);
    }
}
