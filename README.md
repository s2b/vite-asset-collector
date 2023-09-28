# Vite AssetCollector for TYPO3

[![Maintainability](https://api.codeclimate.com/v1/badges/161b455fe0abc70be677/maintainability)](https://codeclimate.com/github/s2b/vite-asset-collector/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/161b455fe0abc70be677/test_coverage)](https://codeclimate.com/github/s2b/vite-asset-collector/test_coverage)
[![tests](https://github.com/s2b/vite-asset-collector/actions/workflows/tests.yaml/badge.svg)](https://github.com/s2b/vite-asset-collector/actions/workflows/tests.yaml)
[![Total downloads](https://typo3-badges.dev/badge/vite_assetcollector/downloads/shields.svg)](https://extensions.typo3.org/extension/vite_asset_collector)
[![TYPO3 versions](https://typo3-badges.dev/badge/vite_assetcollector/typo3/shields.svg)](https://extensions.typo3.org/extension/vite_asset_collector)
[![Latest version](https://typo3-badges.dev/badge/vite_assetcollector/version/shields.svg)](https://extensions.typo3.org/extension/vite_asset_collector)

Bundle your TYPO3 frontend assets with **[vite](https://vitejs.dev/)**, a modern
and flexible frontend tool. This TYPO3 extension provides a future-proof
integration for vite using TYPO3's AssetCollector API.
This means that you can use vite's hot reloading and hot module replacement features
(and many others) in your TYPO3 projects.

This extension is inspired by
[typo3-vite-demo](https://github.com/fgeierst/typo3-vite-demo) which was created
by [Florian Geierstanger](https://github.com/fgeierst/).

## Installation

Vite AssetCollector can be installed with composer:

```sh
composer req praetorius/vite-asset-collector
```

vite can be installed with the frontend package manager of your choice:

```sh
npm install --save-dev vite
```

## Getting Started

### 1. Vite Setup

To get things started, the extension provides a console command that generates a
ready-to-use `vite.config.js` file:

```sh
vendor/bin/typo3 vite:config --entry 'EXT:sitepackage/Resources/Private/Main.entry.js' --outputfile ./vite.config.js
```

The generated configuration makes sure that vite

* knows the location of your main JavaScript file, also known as *entrypoint*
* generates a `manifest.json` file
* outputs assets to a publicly accessible directory

**Note that you might need to set `VITE_DEV_ORIGIN` in the generated config file manually
to the URL of your vite dev server in order to be able to load generated assets (e. g.
font files referenced in a css file) correctly. There will probably be a cleaner solution
in an upcoming version of the extension.**

<details>
    <summary><i>More Options</i></summary>

* You can define multiple `--entry` options.
* You can add `--glob` to enable pattern matching for your entrypoint paths
([fast-glob](https://www.npmjs.com/package/fast-glob) required).
* If you omit `--outputfile`, the file content will be outputted instead.
* `--help` shows all available options.

</details>

### 2. TYPO3 Setup

Then you can use the included ViewHelper to embed your assets. If you use the default
configuration, you only need to specify your entrypoint.

**Layouts/Default.html**:

```xml
<html
    data-namespace-typo3-fluid="true"
    xmlns:vac="http://typo3.org/ns/Praetorius/ViteAssetCollector/ViewHelpers"
>

...

<vac:asset.vite entry="EXT:sitepackage/Resources/Private/Main.entry.js" />
```

### 3. Start Developing

Development environments can be highly individual. However, if ddev is your
tool of choice for local development, a few steps can get you started with
a ready-to-use development environment with vite, composer and TYPO3.

[Instructions for DDEV](./Documentation/DdevSetup.md)

## Configuration

If you use the setup as described above, no configuration should be necessary.
However, you can customize almost everything to create your individual development
setup:

<details>
    <summary><i>Adjust vite dev server</i></summary>

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

</details>

<details>
    <summary><i>Change location of default manifest.json</i></summary>

You can specify a default manifest file in the extension configuration.
By default, this is set to `_assets/vite/manifest.json`, so it will run
out-of-the-box if you generated your vite configuration with this extension.

If you change the path here, please be aware that you need to adjust your
the `outDir` in your `vite.config.js` as well:

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['vite_asset_collector']['defaultManifest'] = 'EXT:sitepackage/Resources/Public/Vite/manifest.json';
```

If you use the `vite.config.js` provided by the extension:

```js
// Output path for generated assets
const VITE_OUTPUT_PATH = 'packages/sitepackage/Resources/Public/Vite/';
```

If you use your own `vite.config.js`:

```js
export default defineConfig({
    // ...
    outDir: 'path/to/sitepackage/Resources/Public/Vite/',
})
```

</details>

## ViewHelper Reference

### asset.vite ViteHelper

The `asset.vite` ViewHelper embeds all JavaScript and CSS belonging to the
specified vite `entry` using TYPO3's AssetCollector API.

<details>
    <summary><i>Arguments</i></summary>

* `manifest` (type: `string`): Path to your manifest.json file. If omitted,
default manifest from extension configuration will be used instead.

* `entry` (type: `string`): Identifier of the desired vite entrypoint;
this is the value specified as `input` in the vite configuration file. Can be
omitted if manifest file exists and only one entrypoint is present.

* `devTagAttributes` (type: `array`): HTML attributes that should be added to
script tags that point to the vite dev server

* `scriptTagAttributes` (type: `array`): HTML attributes that should be added
to script tags for built JavaScript assets

* `cssTagAttributes` (type: `array`): HTML attributes that should be added to
css link tags for built CSS assets

* `priority` (type: `bool`, default: `false`): Include assets before other assets
in HTML

* `useNonce` (type: `bool`, default: `false`): Whether to use the global nonce value

* `addCss` (type: `bool`, default: `true`): If set to `false`, CSS files associated
with the entry point won't be added to the asset collector

</details>

<details>
    <summary><i>Example</i></summary>

```xml
<vac:asset.vite
    manifest="EXT:sitepackage/Resources/Public/Vite/manifest.json"
    entry="EXT:sitepackage/Resources/Private/JavaScript/Main.js"
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

</details>


### resource.vite ViteHelper

The `resource.vite` ViewHelper extracts the uri to one specific asset file from a vite
manifest file.

<details>
    <summary><i>Arguments</i></summary>

* `manifest` (type: `string`): Path to your manifest.json file. If omitted,
default manifest from extension configuration will be used instead.

* `file` (type: `string`): Identifier of the desired asset file for which a uri
should be generated

</details>

<details>
    <summary><i>Example</i></summary>

This can be used to preload certain assets in the HTML `<head>` tag:

```xml
<f:section name="HeaderAssets">
    <link
        rel="preload"
        href="{vac:resource.vite(file: 'EXT:sitepackage/Resources/Private/Fonts/webfont.woff2')}"
        as="font"
        type="font/woff2"
        crossorigin
    />
</f:section>
```

</details>

## Vite Assets in Yaml Files

Besides ViewHelpers, the extension includes a processor for Yaml files, which allows you
to use assets generated by vite in your configuration files. This is especially useful for
[custom RTE presets](https://docs.typo3.org/c/typo3/cms-rte-ckeditor/main/en-us/Configuration/Examples.html):

```yaml
editor:
    config:
        contentsCss:
            # Using the default manifest file
            - "%vite('EXT:sitepackage/Resources/Private/Css/Rte.css')%"

            # Using another manifest.json
            - "%vite('EXT:sitepackage/Resources/Private/Css/Rte.css', 'path/to/manifest.json')%"
```
