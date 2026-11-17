:navigation-title: PHP API

..  include:: /Includes.rst.txt

..  _php-api:

=======
PHP API
=======

All features of the :ref:`ViewHelpers <viewhelpers>` are also available
in PHP. The PHP API consists of two layers:

*   :ref:`ViteHelper <php-api-vitehelper>` covers the common use cases and
    automatically switches between the Vite dev server and the production
    build, based on the current request.
*   :ref:`AssetRenderer and AssetUriGenerator <php-api-low-level>` provide
    fine-grained control, for example to always use a specific manifest
    file or dev server.

While the ViewHelpers remain the preferred and most convenient way to embed
Vite entrypoints into your site, the PHP API can cover use cases where this
isn't feasible, for example:

*   Automatically embedding the matching frontend assets for Fluid components
    from PHP
*   Serving URLs to assets in a JSON API
*   ...

..  _php-api-vitehelper:

ViteHelper
==========

Use cases where both the dev server and the Vite build should be supported can
easily be convered with the :php:`\Praetorius\ViteAssetCollector\Utility\ViteHelper`
class. It determines the current context automatically based on the provided
TYPO3 request object, which contains the `vite.context` request attribute. If
that attribute is not available, the default is to serve the built asset files,
not the dev server.

..  _php-api-vitehelper-render-asset:

Embedding assets
----------------

..  code-block:: php
    :caption: EXT:sitepackage/Classes/Controller/MyController.php

    use Praetorius\ViteAssetCollector\Asset\Asset;
    use Praetorius\ViteAssetCollector\Utility\ViteHelper;

    final class MyController
    {
        public function __construct(private readonly ViteHelper $viteHelper) {}

        public function myAction(): ResponseInterface
        {
            $this->viteHelper->renderAsset(
                Asset::create('EXT:sitepackage/Resources/Private/JavaScript/Main.entry.js'),
                $this->request,
            );
            // ...
        }
    }

If you need to use a different :file:`manifest.json` file, it can be supplied
as a third argument to
:php-short:`\Praetorius\ViteAssetCollector\Utility\ViteHelper::renderAsset()`.

..  _php-api-vitehelper-generate-uri:

Generating URIs
---------------

..  code-block:: php

    use Praetorius\ViteAssetCollector\Asset\AssetFile;

    $uri = $this->viteHelper->generateUri(
        AssetFile::create('EXT:sitepackage/Resources/Private/Images/Logo.svg'),
        $request,
    );

Depending on your use case, you might want to cast the resulting value to
string since `generateUri()` returns a `UriInterface` object.

..  _php-api-asset:

Asset configuration
===================

Both :php-short:`\Praetorius\ViteAssetCollector\Utility\ViteHelper::renderAsset()`
and :php-short:`\Praetorius\ViteAssetCollector\Asset\AssetRenderer` accept
:php-short:`\Praetorius\ViteAssetCollector\Asset\Asset` objects as input.
:php-short:`\Praetorius\ViteAssetCollector\Asset\Asset::create()` accepts the
same options as the :ref:`<vite:asset> <viewhelper-asset>` ViewHelper and
its child ViewHelpers:

..  code-block:: php

    use Praetorius\ViteAssetCollector\Asset\Asset;
    use Praetorius\ViteAssetCollector\Asset\Embedding\ContentSecurityMode;
    use Praetorius\ViteAssetCollector\Asset\Embedding\CssEmbedding;
    use Praetorius\ViteAssetCollector\Asset\Embedding\FontPreloading;
    use Praetorius\ViteAssetCollector\Asset\Embedding\ImagePreloading;
    use Praetorius\ViteAssetCollector\Asset\Embedding\ScriptEmbedding;

    $asset = Asset::create(
        entry: 'EXT:sitepackage/Resources/Private/JavaScript/Main.entry.js',
        cssEmbedding: new CssEmbedding(inline: true),
        scriptEmbedding: new ScriptEmbedding(async: true),
        fontPreloading: new FontPreloading(formats: ['woff2', 'woff']),
        imagePreloading: new ImagePreloading(preload: true, formats: ['svg']),
        csp: ContentSecurityMode::Enabled,
    );

..  _php-api-low-level:

AssetRenderer and AssetUriGenerator
===================================

Sometimes, you might prefer to only use the built assets and opt-out of
the Vite dev server for your use case. In such cases, the underlying APIs
:php:`\Praetorius\ViteAssetCollector\Asset\AssetRendererInterface` and
:php:`\Praetorius\ViteAssetCollector\Asset\AssetUriGeneratorInterface`
can be used instead.

The extension itself uses this approach for its YAML integration because
YAML parsing happens server-side and thus needs an actual file to check for.

The :php:`\Praetorius\ViteAssetCollector\Asset\Manifest\ManifestFactory` can
be used to obtain a
:php-short:`\Praetorius\ViteAssetCollector\Asset\Manifest\Manifest` object,
which interprets a Vite :file:`manifest.json` file.

..  code-block:: php

    use Praetorius\ViteAssetCollector\Asset\AssetRendererInterface;
    use Praetorius\ViteAssetCollector\Asset\Manifest\ManifestFactory;

    final class MyClass
    {
        public function __construct(
            private readonly AssetRendererInterface $assetRenderer,
            private readonly ManifestFactory $manifestFactory,
        ) {}

        public function doSomething(ServerRequestInterface $request): void
        {
            $manifest = $this->manifestFactory->createFromFilePath('EXT:sitepackage/Resources/Public/Vite/.vite/manifest.json');
            $this->assetRenderer->renderBuildAsset($asset, $manifest, $request);
        }
    }

..  _php-api-vite-context:

Vite context
============

The vite context object determines if the Vite dev server or the built
assets should be used in the current request. This is determined early
in the request cycle using a PSR-15 HTTP Middleware.

The current Vite context is available as request attribute:

..  code-block:: php

    /** @var \Praetorius\ViteAssetCollector\Context\ViteContext|null $viteContext */
    $viteContext = $request->getAttribute('vite.context');
    if ($viteContext?->useDevServer) {
        $devServerUri = $viteContext->devServer;
    }

See :ref:`extending-events-build-vite-context` to customize the context.
