<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset\Embedding;

use Praetorius\ViteAssetCollector\Asset\Manifest\OutputFile;

final readonly class FontPreloading
{
    public const VariableName = 'fontPreloading';

    /**
     * @var string[] File extensions that should be preloaded; empty means all
     */
    public array $formats;

    /**
     * @param string[] $formats
     */
    public function __construct(
        public bool $preload = true,
        array $formats = ['woff2'],
    ) {
        // File extensions are always lowercase, so the configured formats need to be as well
        $this->formats = array_map(strtolower(...), $formats);
    }

    public function appliesTo(OutputFile $file): bool
    {
        return $this->preload && ($this->formats === [] || in_array($file->extension, $this->formats, true));
    }
}
