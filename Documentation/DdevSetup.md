# Set up Vite AssetCollector in a DDEV project

## Initial TYPO3 setup

If you need to setup TYPO3 first, have a look at the official documentation:

[Set up a new project via DDEV and Composer](https://get.typo3.org/version/12)

Then you need to create a *sitepackage* extensions where your project's
configuration, templates and frontend assets will be located:

[Set up your sitepackage](https://docs.typo3.org/m/typo3/tutorial-sitepackage/main/en-us/)

## Setup vite-serve for ddev

Next, you can setup the vite process in your ddev environment with
[ddev-viteserve](https://github.com/torenware/ddev-viteserve):

```sh
ddev get torenware/ddev-viteserve
```

You should check the created `.ddev/.env file for the correct configuration,
for example:

```sh
# start vite
VITE_PROJECT_DIR=.
VITE_PRIMARY_PORT=5173
VITE_SECONDARY_PORT=5273
VITE_JS_PACKAGE_MGR=npm
# end vite
```

The settings take effect with a restart, then you can start the vite process:

```sh
ddev restart
ddev vite-serve start
```

## Configure vite and set up the extension

See [extension documentation](../README.md).

## Switch between development and production

During development, the vite *watcher* process will provide the assets on-the-fly,
in production the assets need to exist in a built state. By default, the extension
differentiates between development and production based on the `TYPO3_CONTEXT`
environment variable. You can set this variable either in your `.ddev/config.yaml`
in `web_environment` or you can
[setup an .env file for your TYPO3](https://github.com/helhum/dotenv-connector).

With a **Development** context, you need to run `ddev vite-serve`, either manually
or as a `post-start` hook for ddev.

With a **Production** context, you need to run `ddev npm exec vite build` before
to create the assets and the manifest file, which will then be picked up by the
ViewHelper.
