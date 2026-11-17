:navigation-title: Events

..  include:: /Includes.rst.txt

..  _extending-events:

=============
PSR-14 Events
=============

Events can be used to customize the behavior of the extension. To
attach your custom event listener to an event, check out
:ref:`t3coreapi:EventDispatcher`.

..  _extending-events-build-vite-context:

BuildViteContextEvent
=====================

:php:`\Praetorius\ViteAssetCollector\Event\BuildViteContextEvent` is
dispatched once per request by a middleware, both in frontend and in
backend context. Its listeners determine whether the Vite dev server
should be used and which dev server URI applies. The resulting
:php-short:`\Praetorius\ViteAssetCollector\Context\ViteContext` object is
available as :ref:`request attribute <php-api-vite-context>` afterwards.

The middleware runs after the site has been resolved (frontend) and after
the backend user has been authenticated (backend), so both are available
in the request object.

The extension's default listener
`praetorius/vite-asset-collector/build-vite-context` evaluates the
:ref:`extension configuration <configuration-devserver>`. To adjust its
result, register your listener after the default listener:

..  code-block:: php
    :caption: EXT:sitepackage/Classes/EventListener/ViteContextForBackendUsers.php

    namespace MyVendor\Sitepackage\EventListener;

    use Praetorius\ViteAssetCollector\Context\ViteContext;
    use Praetorius\ViteAssetCollector\Event\BuildViteContextEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    #[AsEventListener(after: 'praetorius/vite-asset-collector/build-vite-context')]
    final readonly class ViteContextForBackendUsers
    {
        public function __invoke(BuildViteContextEvent $event): void
        {
            $currentContext = $event->getViteContext();
            // ...
            $event->setViteContext(new ViteContext(
                useDevServer: false,
                devServer: $currentContext->devServer,
            ));
        }
    }

Alternatively, you can register your listener before the default listener
and then stop the event's propagation with :php:`$event->stopPropagation()`.
Note that this will prevent all following listeners from running.
