<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Event;

use Praetorius\ViteAssetCollector\Context\ViteContext;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Http\Message\ServerRequestInterface;

final class BuildViteContextEvent implements StoppableEventInterface
{
    private ?ViteContext $context = null;
    private bool $stopPropagation = false;

    public function __construct(private ServerRequestInterface $request) {}

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function setViteContext(ViteContext $context): void
    {
        $this->context = $context;
    }

    public function getViteContext(): ?ViteContext
    {
        return $this->context;
    }

    public function stopPropagation(): void
    {
        $this->stopPropagation = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->stopPropagation;
    }
}
