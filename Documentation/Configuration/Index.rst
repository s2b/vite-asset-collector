..  include:: /Includes.rst.txt

..  _configuration:

=============
Configuration
=============

If you use the default setup, no configuration should be necessary.
However, you can customize almost everything to create your individual
development setup.

..  _configuration-devserver:

Adjust Vite Dev Server
======================

The extension has two configuration options to setup the Vite dev server.
By default, both are set to `auto`, which means:

*   Dev server will only be used in `Development` context
*   Dev server uri will be determined automatically for environments with
    `vite-sidecar <https://github.com/s2b/ddev-vite-sidecar>`__ or
    `vite-serve for DDEV <https://github.com/torenware/ddev-viteserve>`__ set up

You can adjust both options in your :php:`$TYPO3_CONF_VARS`, for example:

..  code-block:: php
    :caption: config/system/additional.php

    // Setup Vite dev server based on configuration in .env file
    // TYPO3_VITE_DEV_SERVER='https://localhost:1234'
    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['vite_asset_collector']['useDevServer'] = (bool) getenv('TYPO3_VITE_DEV_SERVER');
    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['vite_asset_collector']['devServerUri'] = (string) getenv('TYPO3_VITE_DEV_SERVER');


..  _configuration-manifest:

Change Location of manifest.json
================================

You can specify the path to Vite's `manifest.json` in the extension configuration.
By default, this is set to `_assets/vite/.vite/manifest.json`, so it will run
out-of-the-box with Vite 5 and the Vite TYPO3 plugin.

If you still use Vite < 5, you should change this to `_assets/vite/manifest.json`.

..  code-block:: php
    :caption: config/system/additional.php

    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['vite_asset_collector']['defaultManifest'] = 'EXT:sitepackage/Resources/Public/Vite/.vite/manifest.json';

If you change the path here, please be aware that you may need to adjust `outDir` in
your `vite.config.js` as well:

..  code-block:: javascript
    :caption: vite.config.js

    export default defineConfig({
        // ...
        build: {
            // ...
            outDir: 'path/to/sitepackage/Resources/Public/Vite/',
        }
    })


..  _configuration-vite-manual:

Manual Vite Configuration
=========================

If you don't want or can't use the `Vite plugin <https://github.com/s2b/vite-plugin-typo3/>`__, you can configure
Vite yourself to work together with the TYPO3 extension. In that case, it is highly recommended to install
`vite-plugin-auto-origin`:

..  tabs::

    ..  group-tab:: npm

        ..  code-block:: sh

            npm install --save-dev vite-plugin-auto-origin

    ..  group-tab:: pnpm

        ..  code-block:: sh

            pnpm add --save-dev vite-plugin-auto-origin

    ..  group-tab:: yarn

        ..  code-block:: sh

            yarn add --dev vite-plugin-auto-origin

The manual Vite configuration could look something like this:

..  code-block:: javascript
    :caption: vite.config.js
    :emphasize-lines: 5-15

    import { defineConfig } from "vite"
    import { dirname, resolve } from "node:path"
    import { fileURLToPath } from "node:url"
    import autoOrigin from "vite-plugin-auto-origin"

    // TYPO3 root path (relative to this config file)
    const VITE_TYPO3_ROOT = "./";

    // Vite input files (relative to TYPO3 root path)
    const VITE_ENTRYPOINTS = [

    ];

    // Output path for generated assets
    const VITE_OUTPUT_PATH = "public/_assets/vite/";

    const currentDir = dirname(fileURLToPath(import.meta.url));
    const rootPath = resolve(currentDir, VITE_TYPO3_ROOT);
    export default defineConfig({
        base: "",
        build: {
            manifest: true,
            rollupOptions: {
            input: VITE_ENTRYPOINTS.map(entry => resolve(rootPath, entry)),
            },
            outDir: resolve(rootPath, VITE_OUTPUT_PATH),
        },
        css: {
            devSourcemap: true,
        },
        plugins: [ autoOrigin() ],
        publicDir: false,
    });

You can also `create aliases yourself <https://vitejs.dev/config/shared-options.html#resolve-alias>`__ to
refer to other assets in CSS files:

..  code-block:: javascript
    :caption: vite.config.js

    //...
    export default defineConfig({
        // ...
        resolve: {
            alias: [
                { find: "@frontend", replacement: resolve(currentDir, "frontend/") }
            ]
        }
    });
