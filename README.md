# Vite AssetCollector for TYPO3

This TYPO3 extension uses TYPO3's AssetCollector API to embed frontend assets
generated with [vite](https://vitejs.dev/). This means that you can use
vite's hot reloading and hot module replacement features (and many others)
in your TYPO3 project.

This extension is inspired by
[typo3-vite-demo](https://github.com/fgeierst/typo3-vite-demo) which was created
by [Florian Geierstanger](https://github.com/fgeierst/).

## Installation

The extension can be installed via composer:

```sh
composer req praetorius/vite-asset-collector
```

## Usage

### Vite Configuration

First, you need to make sure that vite:

* generates a `manifest.json` file and
* outputs assets to a publicly accessible directory

Example **vite.config.js**:

```js
import { defineConfig } from 'vite'

export default defineConfig({
    publicDir: false,
    build: {
        manifest: true,
        rollupOptions: {
            input: 'path/to/sitepackage/Resources/Private/JavaScript/Main.js'
        },
        outDir: 'path/to/sitepackage/Resources/Public/Vite/',
    },
    css: {
        devSourcemap: true,
    }
})
```

Note that you should not use `resolve(__dirname, ...)` for `input` because the
value is both a path and an identifier.

### Fluid Usage

Then you can use the included ViewHelper to embed your assets. Note that the
`entry` value is both a path and an identifier, which is why we cannot
use `EXT:` here. This also means that this path needs to be consistent between
your development and your production environment.

Example **Layouts/Default.html**:

```xml
<html
    data-namespace-typo3-fluid="true"
    xmlns:vac="http://typo3.org/ns/Praetorius/ViteAssetCollector/ViewHelpers"
>

...

<vac:asset.vite
    manifest="EXT:sitepackage/Resources/Public/Vite/manifest.json"
    entry="path/to/sitepackage/Resources/Private/JavaScript/Main.js"
/>
```

### Setup development environment

Development environments can be highly individual. However, if ddev is your
tool of choice for local development, a few steps can get you started with
a ready-to-use development environment with vite, composer and TYPO3.

[Instructions for DDEV](./Documentation/DdevSetup.md)

## Configuration

The extension has two configuration options to setup the vite dev server.
By default, both are set to `auto`, which means:

* Dev server will only be used in `Development` context
* Dev server uri will be determined automatically for environments with
[vite-serve for DDEV](https://github.com/torenware/ddev-viteserve) set up

You can adjust both options in your `$TYPO3_CONF_VARS`, for example:

```php
// Setup vite dev server based on configuration in .env file
// TYPO3_VITE_DEV_SERVER='https://localhost:1234'
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['vite_asset_collector']['useDevServer'] = (bool) getenv('TYPO3_VITE_DEV_SERVER');
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['vite_asset_collector']['devServerUri'] = (string) getenv('TYPO3_VITE_DEV_SERVER');
```

## ViewHelper Arguments

* `devTagAttributes` (type: `array`): HTML attributes that should be added to
script tags that point to the vite dev server
* `scriptTagAttributes` (type: `array`): HTML attributes that should be added
to script tags for built JavaScript assets
* `cssTagAttributes` (type: `array`): HTML attributes that should be added to
css link tags for built CSS assets
* `priority` (type: `bool`, default: `false`): Include assets before other assets
in HTML

Example:

```xml
<vac:asset.vite
    manifest="EXT:sitepackage/Resources/Public/Vite/manifest.json"
    entry="path/to/sitepackage/Resources/Private/JavaScript/Main.js"
    scriptTagAttributes="{
        type: 'text/javascript',
        async: 1
    }"
    cssTagAttributes="{
        media: 'print'
    }"
    priority="1"
/>
```
