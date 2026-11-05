<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Fixtures\ViteContextEventListener\EventListener;

use Praetorius\ViteAssetCollector\Event\BuildViteContextEvent;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Records which variables are available to BuildViteContextEvent listeners
 * at the moment the event is dispatched. State is static because frontend
 * sub requests in functional tests use a separate container instance.
 */
#[AsEventListener(
    identifier: 'praetorius/vite-asset-collector-test/variable-availability',
    after: 'praetorius/vite-asset-collector/build-vite-context',
)]
final class VariableAvailabilityListener
{
    /**
     * Request passed to BuildViteContextEvent
     */
    public static ?ServerRequestInterface $request = null;

    /**
     * $GLOBALS['BE_USER'] when BuildViteContextEvent was dispatched
     */
    public static ?BackendUserAuthentication $backendUser = null;

    public static function reset(): void
    {
        self::$request = null;
        self::$backendUser = null;
    }

    public function __invoke(BuildViteContextEvent $event): void
    {
        self::$request = $event->getRequest();
        $backendUser = $GLOBALS['BE_USER'] ?? null;
        self::$backendUser = $backendUser instanceof BackendUserAuthentication ? $backendUser : null;
    }
}
