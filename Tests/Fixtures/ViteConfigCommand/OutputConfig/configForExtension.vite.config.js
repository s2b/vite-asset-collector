import { defineConfig } from 'vite'
import { resolve } from 'path'

// Extension root path (relative to this config file)
const VITE_TYPO3_ROOT = "./";

// Vite input files (relative to Extension root path)
const VITE_ENTRYPOINTS = [
  "Resources/Private/JavaScript/main.js",
  "Resources/Private/JavaScript/another.js",
];

// Output path for generated assets
const VITE_OUTPUT_PATH = "Resources/Public/Vite/";

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
