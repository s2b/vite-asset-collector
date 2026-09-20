<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset;

use Praetorius\ViteAssetCollector\Asset\Embedding\CssEmbedding;
use Praetorius\ViteAssetCollector\Asset\Embedding\ScriptEmbedding;
use Praetorius\ViteAssetCollector\Asset\Manifest\Manifest;

final readonly class Asset
{
    private function __construct(
        public AssetFile $entry,
        public CssEmbedding $cssEmbedding,
        public ScriptEmbedding $scriptEmbedding,
        public ?Manifest $manifest,
        public bool $csp,
    ) {}

    public static function create(
        AssetFile|string $entry,
        ?CssEmbedding $cssEmbedding = null,
        ?ScriptEmbedding $scriptEmbedding = null,
        ?Manifest $manifest = null,
        bool $csp = false,
    ): self {
        return new self(
            entry: $entry instanceof AssetFile ? $entry : AssetFile::create($entry),
            cssEmbedding: $cssEmbedding ?? new CssEmbedding(),
            scriptEmbedding: $scriptEmbedding ?? new ScriptEmbedding(),
            manifest: $manifest,
            csp: $csp,
        );
    }
}
