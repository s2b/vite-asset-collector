..  include:: /Includes.rst.txt

..  _backendusage:

=============
Backend Usage
=============

..  note::

    This Vite integration with TYPO3 is currently intended mainly for frontend usage. However,
    it is possible to cover at least some of the potential usages in the TYPO3 backend.

..  _rte-styling:

RTE styling
===========

You can use Vite to process your CSS files intended for the Rich-Text editor. Note that this
uses the production build, the dev server is **not available** in that context.

1.  Register a new entrypoint in your Vite setup:

..  code-block:: javascript
    :caption: EXT:sitepackage/Configuration/ViteEntrypoints.json

    [
        "../Resources/Private/Css/Rte.css"
    ]

2. Embed that entrypoint in your RTE yaml configuration:

..  code-block:: yaml
    :caption: MyPreset.yaml

    editor:
        config:
            contentsCss:
                - "%vite('EXT:sitepackage/Resources/Private/Css/Rte.css')%"

3. Run the production build (optionally as a watcher):

..  code-block:: sh

    # Build once
    vite build

    # Watch for file changes
    vite build --watch

You can learn more about this in the
`Yaml Processor documentation <https://docs.typo3.org/permalink/praetorius/vite-asset-collector:yaml-processor>`_
as well as the `RTE Configuration Examples <https://docs.typo3.org/permalink/typo3/cms-rte-ckeditor:config-examples>`_.

..  _backend-modules:

Backend Modules
===============

In the context of a custom backend module, Vite can be used in almost exactly the same way as
in the frontend context:

..  code-block:: html
    :caption: MyModule.html

    <html
        data-namespace-typo3-fluid="true"
        xmlns:vite="http://typo3.org/ns/Praetorius/ViteAssetCollector/ViewHelpers"
    >

    ...

    <vite:asset entry="EXT:sitepackage/Resources/Private/Backend.entry.js" />

However, if you want to use some of TYPO3's included JavaScript modules, like for example a
date picker, you may need to extend your Vite configuration:

..  code-block:: sh

    npm install --save-dev vite-plugin-externalize-dependencies

..  code-block:: javascript
    :caption: vite.config.js
    :emphasize-lines: 3,5-7,12,16

    import { defineConfig } from "vite";
    import typo3 from "vite-plugin-typo3";
    import externalize from "vite-plugin-externalize-dependencies";

    const external = [
        /^@typo3\/.*/
    ];

    export default defineConfig({
        plugins: [
            typo3(),
            externalize({ externals: external }),
        ],
        build: {
            rollupOptions: {
                external: external,
            },
        },
    });

The additional Vite plugin
`vite-plugin-externalize-dependencies <https://www.npmjs.com/package/vite-plugin-externalize-dependencies>`_
tells the Vite dev server to ignore all modules starting with `@typo3/`, the configuration within
`rollupOptions` does the same for the production build.

Within your Fluid template, you can use the
`Be.pageRenderer ViewHelper <f:be.pageRenderer> <https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-be-pagerenderer>`_
to define additional modules that should be added to TYPO3's own import map. For more details, see
TYPO3's documentation about
`ES6 in the TYPO3 Backend <https://docs.typo3.org/permalink/t3coreapi:backend-javascript-es6>`_.

..  _library-mode:

Library Mode as Workaround
==========================

..  warning::

    This is a very advanced use case, you should know what you're doing when you attempt this!

There might be cases where it's not possible to use any of the already mentioned workflows. As a
last resort, it is possible to enable `Vite's library mode <https://vite.dev/guide/build.html#library-mode>`_,
in which case Vite behaves just like a regular bundler without a dev server and a manifest file.

The Vite plugin already supports this on extension level, which makes it possible to bundle your
assets into files with predictable file names an then use those for example in TYPO3's own import map:

..  code-block:: javascript
    :caption: EXT:sitepackage/vite.config.js

    import { defineConfig } from "vite";
    import typo3 from "vite-plugin-typo3";

    export default defineConfig({
        plugins: [typo3({ target: "extension" })],
    });

..  code-block:: javascript
    :caption: EXT:sitepackage/Configuration/ViteEntrypoints.json

    [
        "../Resources/Private/JavaScript/Backend.entry.js"
    ]

..  code-block:: php
    :caption: EXT:sitepackage/Configuration/JavaScriptModules.php

    <?php

    return [
        'dependencies' => ['backend'],
        'imports' => [
            '@vendor/sitepackage/backend' => 'EXT:sitepackage/Resources/Public/Vite/Backend.entry.js',
        ],
    ];

..  code-block:: sh

    # Build once
    vite build

    # Watch for file changes
    vite build --watch

If you use DDEV together with the ddev-vite-sidecar add-on, you have to run the vite build command inside the
extension directory (e.g. packages/sitepackage) depending of the node package manager of your choose (e.g. pnpm):

..  code-block:: sh

    # Build once
    ddev pnpm exec vite

    # Watch for file changes
    ddev pnpm exec vite --watch

In all cases you have (of course) to add a dedicated package.json inside the extension and have to install
all packages first!
