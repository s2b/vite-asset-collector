<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Domain\Model;

use Praetorius\ViteAssetCollector\Utility\VitePathUtility;

final readonly class ViteManifestItem
{
    public function __construct(
        public string $identifier,
        public ?string $src,
        public string $file,
        public bool $isEntry,
        public bool $isDynamicEntry,
        public array $assets,
        public array $css,
        public array $imports,
        public array $dynamicImports,
    ) {}

    public static function fromArray(array $item, string $identifier): self
    {
        return new self(
            $identifier,
            $item['src'] ?? null,
            $item['file'],
            (bool)($item['isEntry'] ?? false),
            (bool)($item['isDynamicEntry'] ?? false),
            (array)($item['assets'] ?? []),
            (array)($item['css'] ?? []),
            (array)($item['imports'] ?? []),
            (array)($item['dynamicImports'] ?? []),
        );
    }

    public function isCss(): bool
    {
        return VitePathUtility::isCssFile($this->file);
    }
}
