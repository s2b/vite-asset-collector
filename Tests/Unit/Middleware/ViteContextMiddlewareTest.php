<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Unit\Middleware;

use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Context\ViteContext;
use Praetorius\ViteAssetCollector\Event\BuildViteContextEvent;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Middleware\ViteContextMiddleware;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ViteContextMiddlewareTest extends UnitTestCase
{
    #[Test]
    public function addsViteContextFromEventToRequest(): void
    {
        $viteContext = new ViteContext(true, new Uri('https://localhost:5173'));
        $originalRequest = new ServerRequest('https://localhost/');
        $dispatchedEvent = null;
        $eventDispatcher = $this->createEventDispatcher(
            function (BuildViteContextEvent $event) use ($viteContext, &$dispatchedEvent): void {
                $dispatchedEvent = $event;
                $event->setViteContext($viteContext);
            }
        );
        $handledRequest = null;

        $response = (new ViteContextMiddleware($eventDispatcher))->process(
            $originalRequest,
            $this->createRequestHandler($handledRequest),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertInstanceOf(BuildViteContextEvent::class, $dispatchedEvent);
        self::assertSame($originalRequest, $dispatchedEvent->getRequest());
        self::assertInstanceOf(ServerRequestInterface::class, $handledRequest);
        self::assertSame($viteContext, $handledRequest->getAttribute('vite.context'));
    }

    #[Test]
    public function lastListenerWins(): void
    {
        $firstContext = new ViteContext(false, new Uri('https://first:5173'));
        $secondContext = new ViteContext(true, new Uri('https://second:5173'));
        $eventDispatcher = $this->createEventDispatcher(
            fn(BuildViteContextEvent $event) => $event->setViteContext($firstContext),
            fn(BuildViteContextEvent $event) => $event->setViteContext($secondContext),
        );
        $handledRequest = null;

        (new ViteContextMiddleware($eventDispatcher))->process(new ServerRequest(), $this->createRequestHandler($handledRequest));

        self::assertInstanceOf(ServerRequestInterface::class, $handledRequest);
        self::assertSame($secondContext, $handledRequest->getAttribute('vite.context'));
    }

    #[Test]
    public function stoppedPropagationSkipsSubsequentListeners(): void
    {
        $firstContext = new ViteContext(false, new Uri('https://first:5173'));
        $secondContext = new ViteContext(true, new Uri('https://second:5173'));
        $eventDispatcher = $this->createEventDispatcher(
            function (BuildViteContextEvent $event) use ($firstContext): void {
                $event->setViteContext($firstContext);
                $event->stopPropagation();
            },
            fn(BuildViteContextEvent $event) => $event->setViteContext($secondContext),
        );
        $handledRequest = null;

        (new ViteContextMiddleware($eventDispatcher))->process(new ServerRequest(), $this->createRequestHandler($handledRequest));

        self::assertInstanceOf(ServerRequestInterface::class, $handledRequest);
        self::assertSame($firstContext, $handledRequest->getAttribute('vite.context'));
    }

    #[Test]
    public function throwsExceptionIfNoListenerProvidesContext(): void
    {
        $handledRequest = null;
        try {
            (new ViteContextMiddleware($this->createEventDispatcher()))->process(
                new ServerRequest(),
                $this->createRequestHandler($handledRequest),
            );
            self::fail('Expected ViteException was not thrown');
        } catch (ViteException $e) {
            self::assertSame(1789912229, $e->getCode());
        }
        self::assertNull($handledRequest, 'Request handler must not be called without vite context');
    }

    /**
     * Minimal PSR-14 dispatcher that respects stoppable events
     */
    private function createEventDispatcher(callable ...$listeners): EventDispatcherInterface
    {
        return new class ($listeners) implements EventDispatcherInterface {
            /**
             * @param callable[] $listeners
             */
            public function __construct(private readonly array $listeners) {}

            public function dispatch(object $event): object
            {
                foreach ($this->listeners as $listener) {
                    if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                        break;
                    }
                    $listener($event);
                }
                return $event;
            }
        };
    }

    private function createRequestHandler(?ServerRequestInterface &$handledRequest): RequestHandlerInterface
    {
        $capture = function (ServerRequestInterface $request) use (&$handledRequest): void {
            $handledRequest = $request;
        };
        return new class ($capture) implements RequestHandlerInterface {
            /**
             * @param \Closure(ServerRequestInterface): void $capture
             */
            public function __construct(private readonly \Closure $capture) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                ($this->capture)($request);
                return new Response();
            }
        };
    }
}
