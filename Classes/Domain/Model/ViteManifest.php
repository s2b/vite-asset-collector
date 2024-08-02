<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Domain\Model;

use Praetorius\ViteAssetCollector\Exception\ViteException;

final class ViteManifest
{
    /** @var array<string, ViteManifestItem> */
    private array $items;

    public function __construct(string $jsonString, string $fileName = 'manifest.json')
    {
        $manifest = $this->validateAndSanitize($jsonString, $fileName);
        foreach ($manifest as $identifier => $item) {
            $this->items[$identifier] = ViteManifestItem::fromArray($item, $identifier);
        }
    }

    public function get(string $entrypoint): ?ViteManifestItem
    {
        return $this->items[$entrypoint] ?? null;
    }

    /**
     * @return array<string, ViteManifestItem>
     */
    public function getValidEntrypoints(): array
    {
        return array_filter($this->items, fn(ViteManifestItem $entry): bool => $entry->isEntry);
    }

    /**
     * @return array<string, ViteManifestItem>
     */
    public function getImportsForEntrypoint(string $entrypoint): array
    {
        if (!isset($this->items[$entrypoint])) {
            return [];
        }

        $imports = [];
        foreach ($this->items[$entrypoint]->imports as $identifier) {
            $imports[$identifier] = $this->get($identifier);
        }
        return $imports;
    }

    private function validateAndSanitize($jsonString, $fileName): array
    {
        $manifest = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ViteException(sprintf(
                'Invalid vite manifest file "%s": %s.',
                $fileName,
                json_last_error_msg()
            ), 1683200523);
        }
        return $manifest;
    }

    public static function fromFile(string $path): self
    {
        $manifestJson = file_get_contents($path);
        if ($manifestJson === false) {
            throw new ViteException(sprintf(
                'Unable to open manifest file "%s".',
                $path
            ), 1684256597);
        }
        return new self($manifestJson, $path);
    }
}
