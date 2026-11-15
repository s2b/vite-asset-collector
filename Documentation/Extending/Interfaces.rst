:navigation-title: Interfaces

..  include:: /Includes.rst.txt

..  _extending-interfaces:

==================
Service Interfaces
==================

The extension's main services are defined by interfaces. All other parts
of the extension, including the ViewHelpers, depend on these interfaces
instead of the concrete implementations. This allows changing the core
functionality of the extension by providing a different implementation
of an interface.

..  _extending-interfaces-asset-renderer:

AssetRendererInterface
======================

Asset renderers are responsible for embedding Vite entrypoints, either
in dev server or in build context.

:php:`\Praetorius\ViteAssetCollector\Asset\AssetRendererInterface`

..  _extending-interfaces-asset-uri-generator:

AssetUriGeneratorInterface
==========================

Asset URI generators are responsible for generating URIs to asset files
covered by Vite, both in dev server and in build context.

:php:`\Praetorius\ViteAssetCollector\Asset\AssetUriGeneratorInterface`

..  _extending-interfaces-asset-path-resolver:

AssetPathResolverInterface
==========================

Asset path resolvers are responsible for resolving provided path strings
(e. g. with `EXT:` prefix) to actual file paths.

:php:`\Praetorius\ViteAssetCollector\Asset\AssetPathResolverInterface`

..  _extending-interfaces-replace:

Replacing or decorating a service
=================================

To replace an implementation, alias the interface to your own class:

..  code-block:: yaml
    :caption: EXT:sitepackage/Configuration/Services.yaml

    services:
        Praetorius\ViteAssetCollector\Asset\AssetRendererInterface:
            alias: MyVendor\Sitepackage\Vite\CustomAssetRenderer

To only adjust parts of the default behavior, decorate the service instead:

..  code-block:: php
    :caption: EXT:sitepackage/Classes/Vite/CustomAssetRenderer.php

    namespace MyVendor\Sitepackage\Vite;

    use Praetorius\ViteAssetCollector\Asset\Asset;
    use Praetorius\ViteAssetCollector\Asset\AssetRendererInterface;
    use Praetorius\ViteAssetCollector\Asset\Manifest\Manifest;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Message\UriInterface;
    use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
    use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

    #[AsDecorator(AssetRendererInterface::class)]
    final readonly class CustomAssetRenderer implements AssetRendererInterface
    {
        public function __construct(
            #[AutowireDecorated]
            private AssetRendererInterface $inner,
        ) {}

        public function renderDevAsset(Asset $asset, UriInterface $devServerUri, ServerRequestInterface $request): void
        {
            $this->inner->renderDevAsset($asset, $devServerUri, $request);
        }

        public function renderBuildAsset(Asset $asset, Manifest $manifest, ServerRequestInterface $request): void
        {
            // ...
            $this->inner->renderBuildAsset($asset, $manifest, $request);
        }
    }

Note that your extension must declare `vite_asset_collector` as
a dependency for this to work correctly.
