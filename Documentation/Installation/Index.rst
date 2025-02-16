..  include:: /Includes.rst.txt

..  _installation:

============
Installation
============

Vite AssetCollector can be installed with composer:

..  code-block:: sh

    composer req praetorius/vite-asset-collector

Vite and the TYPO3 plugin can be installed with the frontend package manager
of your choice:

..  tabs::

    ..  group-tab:: npm

        ..  code-block:: sh

            npm install --save-dev vite vite-plugin-typo3

        Make sure to execute this inside of your DDEV container if you want to use the DDEV add-on afterwards.

    ..  group-tab:: pnpm

        ..  code-block:: sh

            pnpm add --save-dev vite vite-plugin-typo3

        Make sure to execute this inside of your DDEV container if you want to use the DDEV add-on afterwards.

    ..  group-tab:: yarn

        ..  code-block:: sh

            yarn add --dev vite vite-plugin-typo3

        Make sure to execute this inside of your DDEV container if you want to use the DDEV add-on afterwards.


..  _getting-started:

Getting Started
===============

Follow these steps to get a basic Vite setup for your frontend assets in a
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

For more information about the Vite plugin, have a look at its `dedicated documentation <https://github.com/s2b/vite-plugin-typo3/blob/main/README.md>`__.

..  _typo3-setup:

TYPO3 Setup
-----------

Vite uses so-called entrypoints, which are your frontend source files you want to process and
bundle with vite. For each extension, you can define one or multiple Vite entrypoints in a json file:

..  code-block:: json
    :caption: sitepackage/Configuration/ViteEntrypoints.json

    [
        "../Resources/Private/Main.entry.js"
    ]


It is also possible to define a glob pattern like this: `"../Resources/Private/*.entry.{js,ts}"`. Inside
of each `entrypoint file <entrypoint-files>`_ you can import all frontend assets you want to bundle.

Then you can use the included ViewHelper to embed your assets. If you use the default
configuration, you only need to specify your entrypoint.

..  code-block:: html
    :caption: Layouts/Default.html

    <html
        data-namespace-typo3-fluid="true"
        xmlns:vite="http://typo3.org/ns/Praetorius/ViteAssetCollector/ViewHelpers"
    >

    ...

    <vite:asset entry="EXT:sitepackage/Resources/Private/Main.entry.js" />


..  _start-vite-server:

Start Vite Server
-----------------

For local development, you need a running Vite server that serves your frontend assets
alongside the normal webserver. On production systems, this is no longer necessary.

First, TYPO3 needs to run in `Development` context for the extension to recognize the
correct mode automatically.

You have several options to run the dev server:

..  tabs::

    ..  group-tab:: in DDEV

        Prerequisite is an add-on for DDEV called `ddev-vite-sidecar`.
        For DDEV v1.23.5 or above run:

        .. code-block:: sh

            ddev add-on get s2b/ddev-vite-sidecar
            ddev restart

        For earlier versions of DDEV run:

        .. code-block:: sh

            ddev get s2b/ddev-vite-sidecar
            ddev restart

        Then you can start the server inside DDEV:

        ..  code-block:: sh

            ddev vite

        For more information about the add-on, have a look at its `dedicated documentation <https://github.com/s2b/ddev-vite-sidecar/blob/main/README.md>`__.

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

..  _build_for_production:

Build for Production
--------------------

During deployment, the following command builds static asset files that can be used in Production:

..  tabs::

    ..  group-tab:: npm

        ..  code-block:: sh

            npm exec vite build

    ..  group-tab:: pnpm

        ..  code-block:: sh

            pnpm exec vite build

    ..  group-tab:: yarn

        ..  code-block:: sh

            yarn exec vite -- build
