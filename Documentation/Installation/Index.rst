..  include:: /Includes.rst.txt

..  _installation:

============
Installation
============

Vite AssetCollector can be installed with composer:

..  code-block:: sh

    composer req praetorius/vite-asset-collector

vite and the TYPO3 plugin can be installed with the frontend package manager
of your choice:

..  tabs::

    ..  group-tab:: npm

        ..  code-block:: sh

            npm install --save-dev vite vite-plugin-typo3

    ..  group-tab:: pnpm

        ..  code-block:: sh

            pnpm add --save-dev vite vite-plugin-typo3

    ..  group-tab:: yarn

        ..  code-block:: sh

            yarn add --dev vite vite-plugin-typo3


..  _getting-started:

Getting Started
===============

Follow these steps to get a basic vite setup for your frontend assets in a
`sitepackage` extension.

..  _vite-setup:

Vite Setup
----------

To get things started, you need to create a `vite.config.js` in the root of
your project to activate the TYPO3 plugin:

..  code-block:: javascript
    :caption: vite.config.js

    import { defineConfig } from "vite";
    import typo3 from "vite-plugin-typo3";

    export default defineConfig({
        plugins: [typo3()],
    });

..  _typo3-setup:

TYPO3 Setup
-----------

For each extension, you can define one or multiple vite entrypoints in a json file:

..  code-block:: json
    :caption: sitepackage/Configuration/ViteEntrypoints.json

    [
        "../Resources/Private/Main.entry.js"
    ]


Then you can use the included ViewHelper to embed your assets. If you use the default
configuration, you only need to specify your entrypoint.

..  code-block:: html
    :caption: Layouts/Default.html

    <html
        data-namespace-typo3-fluid="true"
        xmlns:vac="http://typo3.org/ns/Praetorius/ViteAssetCollector/ViewHelpers"
    >

    ...

    <vac:asset.vite entry="EXT:sitepackage/Resources/Private/Main.entry.js" />

..  _start-vite-server:

Start Vite Server
-----------------

For local development, you need a running vite server that serves your frontend assets
alongside the normal webserver. On production systems, this is no longer necessary.

First, TYPO3 needs to run in `Development` context for the extension to recognize the
correct mode automatically.

You have several options to run the dev server:

..  tabs::

    ..  group-tab:: npm (locally)

        Prerequisite is a local node setup and installed dependencies outside of Docker setups. Also,
        you need to configure the extension to use the correct server url:

        ..  code-block:: php
            :caption: config/system/additional.php

            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['vite_asset_collector']['devServerUri'] = 'http://localhost:5173';

        Then you can start the server, which usually launches on port `5173`:

        ..  code-block:: sh

            npm exec vite

    ..  group-tab:: pnpm (locally)

        Prerequisite is a local node setup and installed dependencies outside of Docker setups.

        ..  code-block:: php
            :caption: config/system/additional.php

            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['vite_asset_collector']['devServerUri'] = 'http://localhost:5173';


        Then you can start the server, which usually launches on port `5173`:

        ..  code-block:: sh

            pnpm exec vite

    ..  group-tab:: yarn (locally)

        Prerequisite is a local node setup and installed dependencies outside of Docker setups.

        ..  code-block:: php
            :caption: config/system/additional.php

            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['vite_asset_collector']['devServerUri'] = 'http://localhost:5173';


        Then you can start the server, which usually launches on port `5173`:

        ..  code-block:: sh

            yarn exec vite

    ..  group-tab:: in DDEV

        Prerequisite is one of the available vite add-ons for DDEV, for example:

        .. code-block:: sh

            ddev get torenware/ddev-viteserve
            ddev restart

        Then you can start the server inside DDEV:

        ..  code-block:: sh

            ddev vite-serve start

        .. .. code-block:: sh

        ..     ddev get s2b/ddev-vite-sidecar
        ..     ddev restart

        .. Then you can start the server inside DDEV:

        .. ..  code-block:: sh

        ..     ddev vite
