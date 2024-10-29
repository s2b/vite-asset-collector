..  include:: /Includes.rst.txt

..  _bestpractices:

==============
Best Practices
==============

..  _project-structure:

Project Structure
=================

It is recommended to put your `package.json` and your `vite.config.js` next to the `composer.json`
in the project root. Vite will be responsible for your whole project, so it makes sense to treat your
frontend dependencies similar to your PHP dependencies.

There are multiple ways to structure your frontend assets in TYPO3 projects. If you want to use the
`Vite plugin <https://github.com/s2b/vite-plugin-typo3/>`__, it is recommended to place all your
frontend assets inside TYPO3 extensions, from where they can automatically get picked up, for
example:

..  directory-tree::

    * :path:`node_modules`
    * :path:`packages`
        * :path:`sitepackage`
            * :path:`Configuration`
                * :file:`ViteEntrypoints.json`
            * :path:`Resources`
                * :path:`Private`
                    * :file:`Main.entry.js`
                    * :file:`Slider.entry.js`
        * :path:`my_custom_ext`
            * :path:`Configuration`
                * :file:`ViteEntrypoints.json`
            * :path:`Resources`
                * :path:`Private`
                    * :file:`MyCustomExt.entry.js`
    * :path:`vendor`
    * :file:`composer.json`
    * :file:`package.json`
    * :file:`vite.config.js`


Different Folder Structures
---------------------------

If you want to put your frontend assets separately, for example in a `frontend/` folder in your project
root, you are better off :ref:`configuring Vite yourself <configuration-vite-manual>` and not using the Vite plugin.
The TYPO3 extension doesn't require a specific folder structure, as long as you take care to set the paths correctly
both in `vite.config.js` and in the extension configuration.

..  _entrypoint-files:

Entrypoint Files
================

It is recommended to name your entrypoint files differently from other asset files to clarify their usage.
It might also make sense to use entrypoint files only to create a collection of assets that should become
one bundle:

..  code-block:: javascript
    :caption: Slider.entry.js

    import "path/to/Slider.js"
    import "swiper/css"
    import "path/to/Slider.css"

..  _glob-imports:

Glob Imports
============

It is possible to use `glob patterns <https://en.wikipedia.org/wiki/Glob_(programming)>`__ to import/collect
several assets at once. This can make your setup much more flexible. *Glob* can be used both in
`ViteEntrypoints.json` and in your entrypoint files:

..  code-block:: json
    :caption: sitepackage/Configuration/ViteEntrypoints.json

    [
        "../Resources/Private/*.entry.js"
    ]

In entrypoint files, you can use :javascript:`{ eager: true }` to force Vite to collect everything:

..  code-block:: javascript
    :caption: Main.entry.js

    // Import all CSS files
    import.meta.glob(
        "path/to/*.css",
        { eager: true }
    )

More complex expressions are also possible, like negative patterns:

..  code-block:: javascript
    :caption: Main.entry.js

    // Import everything except Slider
    import.meta.glob([
        "Components/**/*.{css,js}",
        "!**/Organism/Slider/*"
    ], { eager: true })

..  _css-preprocessors:

CSS Preprocessors
=================

If you want to use Vite to compile your SCSS or LESS files, you need to install the required JavaScript
libraries as well. No further configuration is necessary.

..  tabs::

    ..  group-tab:: npm

        ..  code-block:: sh

            npm install --save-dev sass
            npm install --save-dev less
            npm install --save-dev stylus

    ..  group-tab:: pnpm

        ..  code-block:: sh

            pnpm add --save-dev sass
            pnpm add --save-dev less
            pnpm add --save-dev stylus

    ..  group-tab:: yarn

        ..  code-block:: sh

            yarn add --dev sass
            yarn add --dev less
            yarn add --dev stylus

..  _aliases:

Referencing Assets in CSS
=========================

If you use the Vite plugin, it automatically registers an alias for each TYPO3 extension, which allows you
to reference other assets (like webfonts, svg images...) easily in your CSS files. This also works for CSS
preprocessors:

..  code-block:: css
    :caption: _Fonts.scss

    @font-face {
        font-family: 'MyFont';
        src: url(
            '@sitepackage/Resources/.../MyFont.eot'
        );
    }
