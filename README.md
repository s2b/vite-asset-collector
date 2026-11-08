# Vite AssetCollector for TYPO3

[![tests](https://github.com/s2b/vite-asset-collector/actions/workflows/tests.yaml/badge.svg)](https://github.com/s2b/vite-asset-collector/actions/workflows/tests.yaml)
[![Total downloads](https://typo3-badges.dev/badge/vite_assetcollector/downloads/shields.svg)](https://extensions.typo3.org/extension/vite_asset_collector)
[![TYPO3 versions](https://typo3-badges.dev/badge/vite_assetcollector/typo3/shields.svg)](https://extensions.typo3.org/extension/vite_asset_collector)
[![Latest version](https://typo3-badges.dev/badge/vite_assetcollector/version/shields.svg)](https://extensions.typo3.org/extension/vite_asset_collector)

<img src="./Resources/Public/Icons/Extension.svg" width="250" height="250" alt="Vite Logo" />

Vite AssetCollector allows you to use the modern frontend bundler **[Vite](https://vitejs.dev/)**
to build your TYPO3 project's frontend assets.

## Documentation

**[Documentation on docs.typo3.org](https://docs.typo3.org/p/praetorius/vite-asset-collector/main/en-us/)**

### tl;dr

```sh
composer req praetorius/vite-asset-collector
npm install --save-dev vite vite-plugin-typo3
```

*   Include PlugIn in vite config
*   Add `Configuration/ViteEntrypoints.json` to extension(s)
*   Add `<vite:asset entry="..." />` to template
*   Run vite dev server
*   Have fun!

## Discussion & Support

You can join **[#vite on TYPO3 Slack](https://typo3.slack.com/app_redirect?channel=vite)**
to discuss anything about vite and TYPO3.

Feel free to **[contact me directly](mailto:moin@praetorius.me)** if you are interested
in a (paid) workshop to introduce vite to your TYPO3 projects.

## Development

<details>
<summary><strong>Setup</strong></summary>

```sh
composer install
```

</details>

<details>
<summary><strong>Switching TYPO3 version</strong></summary>

The extension supports multiple TYPO3 versions. To install the dependencies for a specific
version locally (e. g. to run the tests or PHPStan against it), use the same command as CI:

```sh
composer update \
    --with typo3/cms-core:^13.4 \
    --with typo3/cms-fluid:^13.4 \
    --with typo3/cms-extbase:^13.4 \
    --with typo3/cms-backend:^13.4 \
    --with typo3/cms-frontend:^13.4
```

</details>

<details>
<summary><strong>Tests</strong></summary>

```sh
composer test
composer test:unit
composer test:functional
```

Functional tests use SQLite, no database server needed.

</details>

<details>
<summary><strong>Code quality</strong></summary>

```sh
composer lint
composer fix
composer phpstan -- -c Build/CodeQuality/phpstan14.neon
```

Use the PHPStan configuration that matches the installed TYPO3 version.

</details>

<details>
<summary><strong>Documentation</strong></summary>

The documentation in `Documentation/` is rendered with Docker:

```sh
docker run --rm -v $(pwd):/project ghcr.io/typo3-documentation/render-guides:latest --config=Documentation
```

The output is written to `Documentation-GENERATED-temp/`. CI fails on any rendering warning.

After changing ViewHelper arguments or docblocks, regenerate the ViewHelper reference:

```sh
composer doc:viewhelpers
```

</details>

<details>
<summary><strong>Release workflow</strong></summary>

Releases are published to GitHub and [TER](https://extensions.typo3.org/extension/vite_asset_collector)
by GitHub Actions. Packagist picks up new tags automatically.

1.  Update the version in `composer.json` and `ext_emconf.php`, then commit and push the change:
    ```sh
    composer set-version 2.1.0
    ```
    For pre-releases (e. g. `2.1.0-rc1`), set the version in both files manually.
2.  Run the [prepare release](https://github.com/s2b/vite-asset-collector/actions/workflows/prepare-release.yaml)
    workflow on `main`, entering the version (and checking "Pre-release" if applicable).
    It verifies the versions in both files and creates a draft release with generated release notes.
3.  Edit the release notes of the draft and publish it. This creates the tag. The
    [publish](https://github.com/s2b/vite-asset-collector/actions/workflows/publish.yaml) workflow then
    uploads the release to TER (except pre-releases), using the release notes as upload comment,
    and attaches the TER ZIP file to the GitHub release.

Publishing to TER requires the repository secret `TYPO3_API_TOKEN`
(created at extensions.typo3.org with the scopes `extension:read,extension:write`).
If the upload fails, re-run the failed jobs in the Actions tab instead of publishing the release again.

</details>
