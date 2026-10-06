<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset\Embedding;

use Praetorius\ViteAssetCollector\Exception\ViteException;

final readonly class ScriptEmbedding
{
    public const VariableName = 'scriptEmbedding';
    public const BooleanAttributes = ['async', 'defer', 'nomodule'];

    public function __construct(
        public bool $priority = false,
        public bool $preload = true,
        public bool $ignore = false,
        public ScriptLoading $loading = ScriptLoading::Module,
        public bool $nomodule = false,
        public array $additionalAttributes = [],
    ) {
        if ($loading === ScriptLoading::Module && $nomodule) {
            throw new ViteException('Invalid configuration for script embedding: module script cannot be used as nomodule script.', 1790873151);
        }
    }

    public function getTagAttributes(): array
    {
        $attributes = [
            ...$this->loading->getTagAttributes(),
            ...($this->nomodule ? ['nomodule' => true] : []),
            ...$this->additionalAttributes,
        ];
        return $this->convertBooleanAttributes($attributes);
    }

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
