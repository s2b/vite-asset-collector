import { defineConfig } from "vite"
import { dirname, resolve } from "node:path"
import { fileURLToPath } from "node:url"
import fg from "fast-glob"
import autoOrigin from "vite-plugin-auto-origin"

// ------------------------------------------------------
// TYPO3 root path (relative to this config file)
const VITE_TYPO3_ROOT = "./";

// Vite input files (relative to TYPO3 root path)
const VITE_ENTRYPOINTS = [
  "packages/**/*.entry.{js,ts}"
];

// Output path for generated assets
const VITE_OUTPUT_PATH = "public/_assets/vite/";
// ------------------------------------------------------

// Ignored patterns to speed up globbing
const VITE_PATTERN_IGNORE = ["**/node_modules/**", "**/.git/**"];

const currentDir = dirname(fileURLToPath(import.meta.url));
const rootPath = resolve(currentDir, VITE_TYPO3_ROOT);
export default defineConfig({
  base: "",
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
  css: {
    devSourcemap: true,
  },
  plugins: [ autoOrigin() ],
  publicDir: false,
});
