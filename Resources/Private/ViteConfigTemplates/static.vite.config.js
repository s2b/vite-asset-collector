import { defineConfig } from "vite"
import { dirname, resolve } from "node:path"
import { fileURLToPath } from "node:url"

// ------------------------------------------------------
// TYPO3 root path (relative to this config file)
const VITE_TYPO3_ROOT = "./";

// Vite input files (relative to TYPO3 root path)
const VITE_ENTRYPOINTS = [
  "packages/sitepackage/Resources/Private/JavaScript/Main.entry.js",
  "packages/my_extension/Resources/Private/JavaScript/Extension.entry.js",
];

// Output path for generated assets
const VITE_OUTPUT_PATH = "public/_assets/vite/";
// ------------------------------------------------------

// Base URL that will be prepended to all referenced assets in dev mode
// Set this to the URL of your vite dev server,
// e. g. https://myproject.ddev.site:5173
const VITE_DEV_ORIGIN = "";

const currentDir = dirname(fileURLToPath(import.meta.url));
const rootPath = resolve(currentDir, VITE_TYPO3_ROOT);
export default defineConfig({
  base: "",
  build: {
    manifest: true,
    rollupOptions: {
      input: VITE_ENTRYPOINTS.map(entry => resolve(rootPath, entry)),
    },
    outDir: resolve(rootPath, VITE_OUTPUT_PATH),
  },
  css: {
    devSourcemap: true,
  },
  publicDir: false,
  server: {
    origin: VITE_DEV_ORIGIN,
  },
});
