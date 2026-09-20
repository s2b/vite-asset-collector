<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset\Manifest;

use Praetorius\ViteAssetCollector\Exception\ViteException;

final readonly class Manifest
{
    /**
     * @param array<string, ManifestChunk> $chunks
     */
    public function __construct(
        private array $chunks,
        public OutputFile $file,
        public string $outputDir,
    ) {}

    public function resolveAssetPath(OutputFile $file): string
    {
        return $this->outputDir . $file->locator;
    }

    /**
     * Determines if the manifest file has exactly one valid entrypoint
     * and returns it. If there are multiple entrypoints or none, an
     * exception will be thrown.
     */
    public function getOnlyEntrypoint(): ManifestChunk
    {
        $entrypoints = $this->getValidEntrypoints();
        if (count($entrypoints) !== 1) {
            throw new ViteException(sprintf(
                'Appropriate vite entrypoint could not be determined automatically. Expected 1 entrypoint in "%s%s", found %d.',
                $this->outputDir,
                $this->file->locator,
                count($entrypoints)
            ), 1683552723);
        }
        return array_last($entrypoints);
    }

    /**
     * @return array<string, ManifestChunk>
     */
    public function getValidEntrypoints(): array
    {
        return array_filter($this->chunks, fn(ManifestChunk $entry): bool => $entry->isEntry);
    }

    public function getChunk(string $identifier): ?ManifestChunk
    {
        return $this->chunks[$identifier] ?? null;
    }

    /**
     * @return array<string, ManifestChunk>
     */
    public function getImportsForChunk(string $identifier, bool $recursive = false, array &$visited = []): array
    {
        if (!isset($this->chunks[$identifier])) {
            return [];
        }

        // Avoid infinite loop when asset references itself
        if (isset($visited[$identifier])) {
            return [];
        }
        $visited[$identifier] = true;

        $imports = [];
        foreach ($this->chunks[$identifier]->imports as $importIdentifier) {
            $imports[$importIdentifier] = $this->getChunk($importIdentifier);
            if ($recursive) {
                $imports = array_merge(
                    $imports,
                    $this->getImportsForChunk($importIdentifier, $recursive, $visited)
                );
            }
        }
        return $imports;
    }
}
