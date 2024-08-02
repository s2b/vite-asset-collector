..  include:: /Includes.rst.txt

..  _configuration:

=============
Configuration
=============

If you use the default setup, no configuration should be necessary.
However, you can customize almost everything to create your individual
development setup.

..  _configuration-devserver:

Adjust vite dev server
======================

The extension has two configuration options to setup the vite dev server.
By default, both are set to `auto`, which means:

*   Dev server will only be used in `Development` context
*   Dev server uri will be determined automatically for environments with
    `vite-serve for DDEV <https://github.com/torenware/ddev-viteserve>`__ set up

You can adjust both options in your :php:`$TYPO3_CONF_VARS`, for example:

..  code-block:: php
    :caption: config/system/additional.php

    // Setup vite dev server based on configuration in .env file
    // TYPO3_VITE_DEV_SERVER='https://localhost:1234'
    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['vite_asset_collector']['useDevServer'] = (bool) getenv('TYPO3_VITE_DEV_SERVER');
    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['vite_asset_collector']['devServerUri'] = (string) getenv('TYPO3_VITE_DEV_SERVER');


..  _configuration-manifest:

Change location of manifest.json
================================

You can specify the path to vite's `manifest.json` in the extension configuration.
By default, this is set to `_assets/vite/.vite/manifest.json`, so it will run
out-of-the-box with vite 5 and the vite TYPO3 plugin.

If you still use vite < 5, you should to change this to `_assets/vite/manifest.json`.

..  code-block:: php
    :caption: config/system/additional.php

    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['vite_asset_collector']['defaultManifest'] = 'EXT:sitepackage/Resources/Public/Vite/.vite/manifest.json';

If you change the path here, please be aware that you may need to adjust `outDir` in
your `vite.config.js` as well:

..  code-block:: javascript
    :caption: vite.config.js

    export default defineConfig({
        // ...
        outDir: 'path/to/sitepackage/Resources/Public/Vite/',
    })

