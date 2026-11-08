<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset\Embedding;

use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;

final readonly class ImagePreloading
{
    public const VariableName = 'imagePreloading';

    /**
     * @var string[] File extensions that should be preloaded; empty means all
     */
    public array $formats;

    /**
     * @param string[] $formats
     */
    public function __construct(
        public bool $preload = false,
        array $formats = [],
    ) {
        $this->formats = array_map(strtolower(...), $formats);
    }

    public function appliesTo(OutputFile $file): bool
    {
        return $this->preload && ($this->formats === [] || in_array($file->extension, $this->formats, true));
    }
}
