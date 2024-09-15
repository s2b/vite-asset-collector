..  include:: /Includes.rst.txt

..  _introduction:

============
Introduction
============

..  _what-it-does:

What does it do?
================

Vite AssetCollector allows you to use the modern frontend bundler `Vite <https://vitejs.dev/>`__
to build your TYPO3 project's frontend assets.

Vite supports all common CSS processors and TypeScript out-of-the-box and has a wide range
of well-supported plugins for popular frontend frameworks. Its hot module replacement feature
allows you to develop your frontend assets without constantly reloading the browser to see your
changes. As a bonus, Vite offers a simple configuration and is easy to use.

The extension is necessary both on development and on production systems because it needs to treat
these environments differently.

It is recommended to use this extension together with a corresponding Vite plugin, however
it's also possible to configure Vite manually.

..  _components:

Components
==========

There are two additional projects that can be used in combination with the TYPO3 extension. When used together,
the setup experience is seamless and requires almost no configuration.

Vite AssetCollector
-------------------

(this TYPO3 extension)

* switches between Development and Production integration
* brings tools to embed assets from Vite in TYPO3 (like ViewHelpers)


Vite Plugin
-----------

(`vite-plugin-typo3 <https://github.com/s2b/vite-plugin-typo3/>`__)

* configures Vite for TYPO3
* discovers and bundles TYPO3 extensions in composer project


DDEV Add-On
-----------

(`ddev-vite-sidecar <https://github.com/s2b/ddev-vite-sidecar>`__)

* allows to run the Vite development server inside ddev setups
* supports Apache and Nginx

..  _credits:

Credits
=======

This extension is inspired by `typo3-vite-demo <https://github.com/fgeierst/typo3-vite-demo>`__
which was created by `Florian Geierstanger <https://github.com/fgeierst/>`__.
