..  include:: /Includes.rst.txt

..  _migration:

=========================
Migration from 1.x to 2.0
=========================

Version 2 is a major overhaul of the internal code structure as well as
the PHP API (i. e. the :php-short:`\Praetorius\ViteAssetCollector\Service\ViteService`
class). The API of the ViewHelpers stays mostly the same, however some of
the ViewHelper arguments are now deprecated and should be migrated to their
new equivalent. Also, the old ViewHelper names :html:`<vac:asset.vite>` and
:html:`<vac:resource.vite>`, which have been deprecated already, no longer
work with version 2.

The extension remains compatible with TYPO3 v13 and v14, but now requires
Vite 5 or higher.

..  contents:: Table of contents
    :local:
    :depth: 1

..  _migration-requirements:

Requirements
============

*   PHP (8.2+) and TYPO3 (v13+v14) requirements remain the same.
*   Vite 5 or higher is required. The legacy location of the manifest
    file without the :file:`.vite/` folder is no longer supported.
*   Symlinked entry points are no longer resolved to their original path.
    Use version 3 of `vite-plugin-typo3 <https://github.com/s2b/vite-plugin-typo3/>`__
    or set `resolve.preserveSymlinks` to `true` in your :file:`vite.config.js`.

..  _migration-templates:

Fluid templates
===============

..  _migration-templates-aliases:

Removed ViewHelper aliases
--------------------------

The old ViewHelper names have been removed:

..  code-block:: text

    <vac:asset.vite>     => <vite:asset>
    <vac:resource.vite>  => <vite:uri>

The `vite` namespace is now registered globally, so explicit namespace
declarations for the extension is no longer necessary.

..  _migration-templates-arguments:

Deprecated arguments of <vite:asset>
------------------------------------

Most embedding options of :ref:`<vite:asset> <viewhelper-asset>` have been
moved to the new child ViewHelpers. The old arguments still work in 2.x,
but trigger deprecation notices and will be removed with 3.0.

..  list-table::
    :header-rows: 1

    *   -   Deprecated argument
        -   Replacement

    *   -   `addCss="0"`
        -   :ref:`<vite:asset.css ignore="{true}" /> <viewhelper-asset-css>`

    *   -   `inlineCss="1"`
        -   :ref:`<vite:asset.css inline="{true}" /> <viewhelper-asset-css>`

    *   -   `cssTagAttributes="{…}"`
        -   :ref:`<vite:asset.css additionalAttributes="{…}" /> <viewhelper-asset-css>`

    *   -   `scriptTagAttributes="{…}"`
        -   :ref:`<vite:asset.script additionalAttributes="{…}" /> <viewhelper-asset-script>`

    *   -   `priority="1"`
        -   :ref:`<vite:asset.script priority="{true}" /> <viewhelper-asset-script>`
            (CSS is added with priority by default now)

    *   -   `useNonce="1"`
        -   `csp="{true}"`

Once `<vite:asset.css>` or `<vite:asset.script>` is used, the deprecated
arguments can no longer be combined with them in the same `<vite:asset>`
tag.

Example:

..  code-block:: html
    :caption: Before

    <vite:asset
        entry="EXT:sitepackage/Resources/Private/JavaScript/Main.entry.js"
        inlineCss="1"
        scriptTagAttributes="{async: 'async'}"
    />

..  code-block:: html
    :caption: After

    <vite:asset entry="EXT:sitepackage/Resources/Private/JavaScript/Main.entry.js">
        <vite:asset.css inline="{true}" />
        <vite:asset.script async="{true}" />
    </vite:asset>

..  _migration-templates-dev-tag-attributes:

Removed argument devTagAttributes
---------------------------------

The `devTagAttributes` argument of :html:`<vite:asset>` has been removed. Additional
script attributes are now applied consistently in development and production
via :ref:`<vite:asset.script additionalAttributes="{…}" /> <viewhelper-asset-script>`.
They are no longer applied to the :html:`<script>` tag of the Vite client.

..  _migration-templates-uri:

<vite:uri> returns an URI object
--------------------------------

:ref:`<vite:uri> <viewhelper-uri>` now returns an object implementing
:php:`\Psr\Http\Message\UriInterface` instead of a string. In most templates
this makes no difference, since the object is converted to a string
automatically.

..  _migration-output:

Changed HTML output
===================

The following changes affect the generated HTML without any changes to your
templates:

*   **Automatic preloading:** Imported JavaScript modules are preloaded with
    :html:`<link rel="modulepreload">` and referenced fonts in the woff2 format with
    :html:`<link rel="preload">`. This can be configured with
    :ref:`<vite:asset.script preloadImports> <viewhelper-asset-script>`,
    :ref:`<vite:asset.font> <viewhelper-asset-font>` and
    :ref:`<vite:asset.image> <viewhelper-asset-image>`. Manual preloading
    with :html:`<vite:uri>` can be removed in most cases.
*   **CSS priority:** CSS files are added with `priority=true` by default,
    which places them before other stylesheets in the :html:`<head>`.
*   **Duplicate CSS:** The same CSS file is embedded again if it is
    requested with different tag attributes (e. g. a different `media`
    query).
*   **Asset identifiers:** The identifiers of assets in TYPO3's
    :php:`AssetCollector` have changed. Code that accesses or removes Vite
    assets from the :php:`AssetCollector` by their identifier needs to be
    adjusted.

..  _migration-php:

PHP API
=======

..  _migration-php-vite-service:

Removed ViteService
-------------------

:php:`\Praetorius\ViteAssetCollector\Service\ViteService` has been removed
and replaced by several smaller services. For most use cases, the new
:ref:`ViteHelper <php-api-vitehelper>` is the recommended replacement:

..  list-table::
    :header-rows: 1

    *   -   ViteService (1.x)
        -   Replacement (2.0)

    *   -   `addAssetsFromManifest()`, `addAssetsFromDevServer()`
        -   :php:`ViteHelper::renderAsset()`, or
            :php:`AssetRendererInterface::renderBuildAsset()` /
            :php:`AssetRendererInterface::renderDevAsset()`

    *   -   `getAssetPathFromManifest()`, `getAssetPathFromDevServer()`
        -   :php:`ViteHelper::generateUri()`, or
            :php:`AssetUriGeneratorInterface::generateUri()` /
            :php:`AssetUriGeneratorInterface::generateDevUri()`

    *   -   `useDevServer()`, `determineDevServer()`
        -   Request attribute `vite.context`, see
            :ref:`php-api-vite-context`

    *   -   `getDefaultManifestFile()`
        -   :php:`ManifestFactory::getDefault()`

    *   -   `determineEntrypointFromManifest()`
        -   :php:`Manifest::getOnlyEntrypoint()`

..  _migration-php-vite-context:

Vite context
------------

Whether the dev server is used is now determined once per request and stored
in the `vite.context` request attribute. To customize this, use the
:ref:`BuildViteContextEvent <extending-events-build-vite-context>`.

..  _migration-cli:

Removed CLI command
===================

The `vite:config` command has been removed. Use
`vite-plugin-typo3 <https://github.com/s2b/vite-plugin-typo3/>`__ or follow the
:ref:`manual Vite configuration <configuration-vite-manual>`.
