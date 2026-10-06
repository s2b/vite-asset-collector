<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Asset\Embedding;

enum ContentSecurityMode
{
    case Enabled;
    case Disabled;
    case Auto;

    public static function fromViewHelperArgument(?bool $csp): self
    {
        if ($csp === null) {
            return self::Auto;
        }
        return $csp ? self::Enabled : self::Disabled;
    }
}
