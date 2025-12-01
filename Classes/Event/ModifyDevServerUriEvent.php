<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Event;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

final class ModifyDevServerUriEvent
{
    public function __construct(
        private readonly string $configuration,
        private UriInterface $resolvedValue,
        private readonly ServerRequestInterface $request
    ) {}

    public function getConfiguration(): string
    {
        return $this->configuration;
    }

    public function setResolvedValue(UriInterface $resolvedValue): void
    {
        $this->resolvedValue = $resolvedValue;
    }

    public function getResolvedValue(): UriInterface
    {
        return $this->resolvedValue;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
