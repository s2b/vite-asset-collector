<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset\Embedding;

use Praetorius\ViteAssetCollector\Exception\ViteException;

final readonly class CssEmbedding
{
    public const VariableName = 'cssEmbedding';

    public function __construct(
        public bool $priority = false,
        public bool $inline = false,
        public bool $preload = true,
        public bool $preloadFonts = true,
        public bool $preloadImages = false,
        public bool $ignore = false,
        public bool $disabled = false,
        public ?string $media = null,
        public array $additionalAttributes = [],
    ) {
        if ($inline && $disabled) {
            throw new ViteException('Invalid configuration for css embedding: inline stylesheets cannot be disabled.', 1790873152);
        }
    }

    public function getTagAttributes(): array
    {
        $attributes = [
            ...(is_string($this->media) ? ['media' => $this->media] : []),
            ...($this->disabled ? ['disabled' => true] : []),
            ...$this->additionalAttributes,
        ];
        return $this->convertBooleanAttributes($attributes);
    }

    private function convertBooleanAttributes(array $attributes): array
    {
        if ($attributes['disabled'] ?? false) {
            $attributes['disabled'] = 'disabled';
        } else {
            unset($attributes['disabled']);
        }
        return $attributes;
    }
}
