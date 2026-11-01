<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset\Embedding;

use Praetorius\ViteAssetCollector\Exception\ViteException;

final readonly class CssEmbedding
{
    public const VariableName = 'cssEmbedding';
    public const BooleanAttributes = ['disabled'];

    /**
     * @param array<string, mixed> $additionalAttributes
     */
    public function __construct(
        public bool $priority = true,
        public bool $inline = false,
        public bool $preloadFonts = true,
        public bool $preloadImages = true,
        public bool $ignore = false,
        public bool $disabled = false,
        public ?string $media = null,
        public array $additionalAttributes = [],
    ) {
        if ($inline && $disabled) {
            throw new ViteException('Invalid configuration for css embedding: inline stylesheets cannot be disabled.', 1790873152);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getTagAttributes(): array
    {
        $attributes = [
            ...(is_string($this->media) ? ['media' => $this->media] : []),
            ...($this->disabled ? ['disabled' => true] : []),
            ...$this->additionalAttributes,
        ];
        return $this->convertBooleanAttributes($attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function convertBooleanAttributes(array $attributes): array
    {
        foreach (self::BooleanAttributes as $attr) {
            if ($attributes[$attr] ?? false) {
                $attributes[$attr] = $attr;
            } else {
                unset($attributes[$attr]);
            }
        }
        return $attributes;
    }
}
