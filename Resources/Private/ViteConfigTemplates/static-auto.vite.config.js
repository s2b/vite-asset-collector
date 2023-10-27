import { defineConfig } from "vite"
import { resolve } from "path"
import autoOrigin from "vite-plugin-auto-origin"

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

const rootPath = resolve(__dirname, VITE_TYPO3_ROOT);
export default defineConfig({
  base: "",
  build: {
    manifest: true,
    rollupOptions: {
      input: VITE_ENTRYPOINTS.map(entry => resolve(rootPath, entry)),
    },
    outDir: resolve(rootPath, VITE_OUTPUT_PATH),
  },
  plugins: [ autoOrigin() ],
  publicDir: false,
});
