<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Middleware;

use Praetorius\ViteAssetCollector\Context\ViteContext;
use Praetorius\ViteAssetCollector\Event\BuildViteContextEvent;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class ViteAssetsMiddleware implements MiddlewareInterface
{
    public function __construct(private EventDispatcherInterface $eventDispatcher) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute('vite.context', $this->buildViteContext($request));
        return $handler->handle($request);
    }

    private function buildViteContext($request): ViteContext
    {
        $event = $this->eventDispatcher->dispatch(new BuildViteContextEvent($request));
        if ($event->getViteContext() === null) {
            throw new ViteException('Unable to determine vite context: No event listener created a valid context object.', 1789912229);
        }
        return $event->getViteContext();
    }
}
