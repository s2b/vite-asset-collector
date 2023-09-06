import { defineConfig } from 'vite'
import { resolve } from 'path'

// TYPO3 root path (relative to this config file)
const VITE_TYPO3_ROOT = "../../";

// Vite input files (relative to TYPO3 root path)
const VITE_ENTRYPOINTS = [
  "Frontend/Main.js",
  "typo3conf/ext/test_extension/Resources/Private/Main.js",
];

// Output path for generated assets
const VITE_OUTPUT_PATH = "public/_assets/vite/";

const rootPath = resolve(__dirname, VITE_TYPO3_ROOT);
export default defineConfig({
  base: '',
  build: {
    manifest: true,
    rollupOptions: {
      input: VITE_ENTRYPOINTS.map(entry => resolve(rootPath, entry)),
    },
    outDir: resolve(rootPath, VITE_OUTPUT_PATH),
  },
  publicDir: false,
});
