<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Http;

/**
 * Extension of the Core Uri class with applied patch from
 * TYPO3 v14 that allows rootless paths. Note that this implementation
 * is only used in v13 to get consistent paths between v13 and v14.
 *
 * @see https://review.typo3.org/c/Packages/TYPO3.CMS/+/91419
 * @internal
 * @todo Remove once support for v13 is dropped.
 */
final class Uri extends \TYPO3\CMS\Core\Http\Uri
{
    public function __toString(): string
    {
        $uri = '';

        if (!empty($this->scheme)) {
            $uri .= $this->scheme . ':';
        }

        $authority = $this->getAuthority();
        if (!empty($authority)) {
            $uri .= '//' . $authority;
        }

        $uri .= $this->normalizePathForStringification($authority, $this->getPath());

        if ($this->query) {
            $uri .= '?' . $this->query;
        }
        if ($this->fragment) {
            $uri .= '#' . $this->fragment;
        }
        return $uri;
    }

    private function normalizePathForStringification(string $authority, string $path): string
    {
        $isRootless = $path !== '' && !str_starts_with($path, '/');
        if ($isRootless) {
            if ($authority === '') {
                // See: https://datatracker.ietf.org/doc/html/rfc3986#page-26:~:text=A%20path%20segment%20that%20contains%20a%20colon%20character%20(e.g.%2C%20%22this%3Athat%22)
                $pathParts = explode('/', $path, 2);
                if (str_contains($pathParts[0], ':')) {
                    $path = './' . $path;
                }
            } else {
                $path = '/' . $path;
            }
        }
        return $path;
    }
}
