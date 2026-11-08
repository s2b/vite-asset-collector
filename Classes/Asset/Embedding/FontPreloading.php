<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset\Embedding;

use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;

final readonly class FontPreloading
{
    public const VariableName = 'fontPreloading';

    /**
     * @param string[] $formats File extensions that should be preloaded; empty means all
     */
    public function __construct(
        public bool $preload = true,
        public array $formats = ['woff2'],
    ) {}

    public function appliesTo(OutputFile $file): bool
    {
        return $this->preload && ($this->formats === [] || in_array($file->extension, $this->formats, true));
    }
}
