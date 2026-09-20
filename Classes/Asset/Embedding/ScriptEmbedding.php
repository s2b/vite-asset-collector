<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset\Embedding;

final readonly class ScriptEmbedding
{
    public const VariableName = 'scriptEmbedding';

    public function __construct(
        public bool $priority = false,
        public bool $inline = false,
        public bool $preload = false, // TODO change default?
        public bool $ignore = false,
        public ScriptLoading $loading = ScriptLoading::Module,
        public bool $nomodule = false,
        public array $additionalAttributes = [],
    ) {}
}
