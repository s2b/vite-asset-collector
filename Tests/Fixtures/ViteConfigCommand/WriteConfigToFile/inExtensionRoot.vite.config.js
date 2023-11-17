import { defineConfig } from "vite"
import { dirname, resolve } from "node:path"
import { fileURLToPath } from "node:url"
import autoOrigin from "vite-plugin-auto-origin"

// TYPO3 root path (relative to this config file)
const VITE_TYPO3_ROOT = "../../../";

// Vite input files (relative to TYPO3 root path)
const VITE_ENTRYPOINTS = [
  "Frontend/Main.js",
  "typo3conf/ext/test_extension/Resources/Private/Main.js",
];

// Output path for generated assets
const VITE_OUTPUT_PATH = "public/_assets/vite/";

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
  plugins: [ autoOrigin() ],
  publicDir: false,
});
