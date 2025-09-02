<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Event;

use Psr\Http\Message\ServerRequestInterface;

final class ModifyDevServerUriEvent
{
    public function __construct(private string $uri, private readonly ServerRequestInterface $request) {}

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
