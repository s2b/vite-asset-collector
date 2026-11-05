<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset\Embedding;

use Praetorius\ViteAssetCollector\Exception\ViteException;

final readonly class ScriptEmbedding
{
    public const VariableName = 'scriptEmbedding';
    public const BooleanAttributes = ['async', 'defer', 'nomodule'];

    /**
     * @param array<string, mixed> $additionalAttributes
     */
    public function __construct(
        public bool $priority = false,
        public bool $preloadImports = true,
        public bool $ignore = false,
        public bool $module = true,
        public bool $async = false,
        public bool $defer = false,
        public bool $nomodule = false,
        public array $additionalAttributes = [],
    ) {
        if ($module && $nomodule) {
            throw new ViteException('Invalid configuration for script embedding: module script cannot be used as nomodule script.', 1790873151);
        }
        if ($module && $defer) {
            throw new ViteException('Invalid configuration for script embedding: module scripts are always deferred, "defer" can only be used for classic scripts.', 1793918609);
        }
        if ($async && $defer) {
            throw new ViteException('Invalid configuration for script embedding: script cannot be "async" and "defer" at the same time.', 1793918610);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getTagAttributes(): array
    {
        $attributes = [
            ...($this->module ? ['type' => 'module'] : []),
            ...($this->async ? ['async' => true] : []),
            ...($this->defer ? ['defer' => true] : []),
            ...($this->nomodule ? ['nomodule' => true] : []),
            ...$this->additionalAttributes,
        ];
        return $this->convertBooleanAttributes($attributes);
    }

    /**
     * Vite's dev server only serves JavaScript modules, so scripts are always
     * embedded as modules during development. "async" is kept because it can
     * be used for module scripts as well.
     *
     * @return array<string, mixed>
     */
    public function getDevServerTagAttributes(): array
    {
        $attributes = [
            'type' => 'module',
            ...($this->async ? ['async' => true] : []),
            ...$this->additionalAttributes,
        ];
        // Additional attributes must not turn the script into a classic script
        $attributes['type'] = 'module';
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
