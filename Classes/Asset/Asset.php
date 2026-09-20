<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset;

use Praetorius\ViteAssetCollector\Asset\Embedding\CssEmbedding;
use Praetorius\ViteAssetCollector\Asset\Embedding\ScriptEmbedding;

final readonly class Asset
{
    private function __construct(
        public AssetFile $entry,
        public CssEmbedding $cssEmbedding,
        public ScriptEmbedding $scriptEmbedding,
        public bool $csp,
    ) {}

    public static function create(
        AssetFile|string $entry,
        ?CssEmbedding $cssEmbedding = null,
        ?ScriptEmbedding $scriptEmbedding = null,
        bool $csp = false,
    ): self {
        return new self(
            entry: $entry instanceof AssetFile ? $entry : AssetFile::create($entry),
            cssEmbedding: $cssEmbedding ?? new CssEmbedding(),
            scriptEmbedding: $scriptEmbedding ?? new ScriptEmbedding(),
            csp: $csp,
        );
    }
}
