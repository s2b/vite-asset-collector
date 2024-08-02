# Vite AssetCollector for TYPO3

[![Maintainability](https://api.codeclimate.com/v1/badges/161b455fe0abc70be677/maintainability)](https://codeclimate.com/github/s2b/vite-asset-collector/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/161b455fe0abc70be677/test_coverage)](https://codeclimate.com/github/s2b/vite-asset-collector/test_coverage)
[![tests](https://github.com/s2b/vite-asset-collector/actions/workflows/tests.yaml/badge.svg)](https://github.com/s2b/vite-asset-collector/actions/workflows/tests.yaml)
[![Total downloads](https://typo3-badges.dev/badge/vite_assetcollector/downloads/shields.svg)](https://extensions.typo3.org/extension/vite_asset_collector)
[![TYPO3 versions](https://typo3-badges.dev/badge/vite_assetcollector/typo3/shields.svg)](https://extensions.typo3.org/extension/vite_asset_collector)
[![Latest version](https://typo3-badges.dev/badge/vite_assetcollector/version/shields.svg)](https://extensions.typo3.org/extension/vite_asset_collector)

Vite AssetCollector allows you to use the modern frontend bundler **[vite](https://vitejs.dev/)**
to build your TYPO3 project's frontend assets.

## Documentation

**[Documentation on docs.typo3.org](https://docs.typo3.org/p/praetorius/vite-asset-collector/main/en-us/)**

### tl;dr

```sh
composer req praetorius/vite-asset-collector
npm install --save-dev vite vite-plugin-typo3
```

* Include PlugIn in vite config
* Add `Configuration/ViteEntrypoints.json` to extension(s)
* Add `<vac:asset.vite />` to template
* Run vite server
* Have fun!

## Discussion & Support

You can join **[#vite on TYPO3 Slack](https://typo3.slack.com/app_redirect?channel=vite)**
to discuss anything about vite and TYPO3.

Feel free to **[contact me directly](mailto:moin@praetorius.me)** if you are interested
in a (paid) workshop to introduce vite to your TYPO3 projects.
