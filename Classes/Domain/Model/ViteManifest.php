<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Domain\Model;

use Praetorius\ViteAssetCollector\Exception\ViteException;

final readonly class ViteManifest
{
    /**
     * @param array<string, ViteManifestItem> $items
     */
    public function __construct(
        private array $items,
        private string $originalPath = 'manifest.json'
    ) {}

    public function getOriginalPath(): string
    {
        return $this->originalPath;
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
    public function getImportsForEntrypoint(string $entrypoint, bool $recursive = false, array &$visited = []): array
    {
        if (!isset($this->items[$entrypoint])) {
            return [];
        }

        // Avoid infinite loop when asset references itself
        if (isset($visited[$entrypoint])) {
            return [];
        }
        $visited[$entrypoint] = true;

        $imports = [];
        foreach ($this->items[$entrypoint]->imports as $identifier) {
            $imports[$identifier] = $this->get($identifier);
            if ($recursive) {
                $imports = array_merge(
                    $imports,
                    $this->getImportsForEntrypoint($identifier, $recursive, $visited)
                );
            }
        }
        return $imports;
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
        $manifest = json_decode($manifestJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ViteException(sprintf(
                'Invalid vite manifest file "%s": %s.',
                $path,
                json_last_error_msg()
            ), 1683200523);
        }
        $items = [];
        foreach ($manifest as $identifier => $item) {
            $items[$identifier] = ViteManifestItem::fromArray($item, $identifier);
        }
        return new self($items, $path);
    }
}
