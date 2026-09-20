<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset\Embedding;

final readonly class CssEmbedding
{
    public const VariableName = 'cssEmbedding';

    public function __construct(
        public bool $priority = false,
        public bool $inline = false,
        public bool $preload = false, // TODO change default?
        public bool $preloadImages = false, // TODO change default?
        public bool $preloadFonts = false, // TODO change default?
        public bool $ignore = false,
        public ?string $media = null,
        public array $additionalAttributes = [],
    ) {}
}
