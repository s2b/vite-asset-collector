import { defineConfig } from 'vite'
import { resolve } from 'path'
import fg from 'fast-glob'

// ------------------------------------------------------
// TYPO3 root path (relative to this config file)
const VITE_TYPO3_ROOT = './';

// Vite input files (relative to TYPO3 root path)
const VITE_ENTRYPOINTS = [
  'packages/**/*.entry.{js,ts}'
];
// ------------------------------------------------------

// Ignored patterns to speed up globbing
const VITE_PATTERN_IGNORE = ['**/node_modules/**', '**/.git/**'];

// Output path for generated assets
const VITE_OUTPUT_PATH = 'public/_assets/vite/';

const rootPath = resolve(__dirname, VITE_TYPO3_ROOT);
export default defineConfig({
  base: '',
  build: {
    manifest: true,
    rollupOptions: {
      input: fg.sync(
        VITE_ENTRYPOINTS.map(pattern => resolve(rootPath, pattern)),
        { ignore: VITE_PATTERN_IGNORE }
      ),
    },
    outDir: resolve(rootPath, VITE_OUTPUT_PATH),
  },
  publicDir: false,
});