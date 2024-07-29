<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * @deprecated will be removed with v2; Use vite-plugin-typo3 instead.
 */
class ViteConfigCommand extends Command
{
    protected const TEMPLATE_SEPARATOR = "// ------------------------------------------------------\n";
    protected const CONFIGURATION_TEMPLATE = '
// %1$s root path (relative to this config file)
const VITE_TYPO3_ROOT = %2$s;

// Vite input files (relative to %1$s root path)
const VITE_ENTRYPOINTS = [
%3$s
];

// Output path for generated assets
const VITE_OUTPUT_PATH = %4$s;
';
    protected PackageManager $packageManager;

    public function injectPackageManager(PackageManager $packageManager)
    {
        $this->packageManager = $packageManager;
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Generates a boilerplate vite config file')
            ->addArgument('extension', InputArgument::OPTIONAL, 'If provided, vite config will be generated for extension context instead of project context', null)
            ->addOption('entry', 'e', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'vite entrypoint(s)')
            ->addOption('glob', 'g', InputOption::VALUE_NONE, 'Enable glob patterns for entrypoints; this requires "fast-glob" to be installed')
            ->addOption('outputfile', 'o', InputOption::VALUE_REQUIRED, 'Write generated vite config to file')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Write file even if it already exists')
            ->addOption('auto-origin', null, InputOption::VALUE_NEGATABLE, 'Use "vite-plugin-auto-origin" to determine origin for dev server automatically (default: enabled)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $extensionName = $input->getArgument('extension');
        $rootPath = $extensionName ? ExtensionManagementUtility::extPath($extensionName) : Environment::getProjectPath();

        $configFile = $input->getOption('outputfile');
        if ($configFile) {
            $configFile = $this->partialRealpath($this->getAbsoluteInputPath($configFile));

            if (!GeneralUtility::isAllowedAbsPath($configFile)) {
                $output->write('Output file is outside of TYPO3 project directory.', true);
                return Command::FAILURE;
            }

            if (file_exists($configFile) && !$input->getOption('force')) {
                $output->write(sprintf('Output file %s already exists. Use --force if you want to overwrite the existing file.', $configFile), true);
                return Command::FAILURE;
            }

            $configFileRelativeToRoot = PathUtility::getRelativePath(PathUtility::dirname($configFile), $rootPath) ?? './';
        } else {
            $configFileRelativeToRoot = './';
        }

        $viteConfig = $this->generateViteConfig(
            $configFileRelativeToRoot,
            $this->prepareEntrypoints($input->getOption('entry'), $rootPath),
            $input->getOption('glob'),
            $input->getOption('auto-origin') !== false,
            $extensionName !== null
        );

        if ($configFile) {
            $this->writeConfigFile($configFile, $viteConfig);
            $output->write(sprintf('Vite config has been written to %s.', $configFile), true);
        } else {
            $output->write($viteConfig);
        }

        return Command::SUCCESS;
    }

    protected function prepareEntrypoints(array $entrypoints, string $rootPath): array
    {
        return array_map(function ($entrypoint) use ($rootPath) {
            $entrypoint = $this->partialRealpath($this->getAbsoluteInputPath($entrypoint));
            $entrypointRelativeToRoot = PathUtility::getRelativePath($rootPath, PathUtility::dirname($entrypoint));
            return $entrypointRelativeToRoot . PathUtility::basename($entrypoint);
        }, $entrypoints);
    }

    protected function getTemplate(bool $useGlob = false, bool $useAutoOrigin = true): string
    {
        $templateName = $useGlob ? 'glob' : 'static';
        $templateName .= $useAutoOrigin ? '-auto' : '';
        return file_get_contents(ExtensionManagementUtility::extPath(
            'vite_asset_collector',
            "Resources/Private/ViteConfigTemplates/$templateName.vite.config.js"
        ));
    }

    protected function generateViteConfig(
        string $rootPath,
        array $entrypoints,
        bool $useGlob = false,
        bool $useAutoOrigin = true,
        bool $configurationForExtension = false
    ): string {
        $configuration = explode(self::TEMPLATE_SEPARATOR, $this->getTemplate($useGlob, $useAutoOrigin), 3);

        $encodedEntrypoints = array_map($this->jsonEncode(...), $entrypoints);
        $entrypointCode = implode(",\n  ", $encodedEntrypoints);

        $outputPath = ($configurationForExtension) ? 'Resources/Public/Vite/' : 'public/_assets/vite/';

        $configuration[1] = vsprintf(ltrim(self::CONFIGURATION_TEMPLATE), [
            ($configurationForExtension) ? 'Extension' : 'TYPO3',
            $this->jsonEncode($rootPath),
            $entrypointCode ? "  $entrypointCode," : '',
            $this->jsonEncode($outputPath),
        ]);

        return implode('', $configuration);
    }

    protected function writeConfigFile(string $file, string $content): void
    {
        GeneralUtility::mkdir_deep(PathUtility::dirname($file));
        GeneralUtility::writeFile($file, $content);
    }

    protected function getAbsoluteInputPath(string $path): string
    {
        if (PathUtility::isAbsolutePath($path)) {
            return $path;
        }

        if (PathUtility::isExtensionPath($path)) {
            return $this->packageManager->resolvePackagePath($path);
        }

        return getcwd() . DIRECTORY_SEPARATOR . $path;
    }

    protected function partialRealpath(string $absolutePath): string
    {
        $staticPath = PathUtility::getCanonicalPath($absolutePath);
        $dynamicPath = [];

        do {
            $dynamicPath[] = PathUtility::basename($staticPath);
            $staticPath = PathUtility::dirname($staticPath);
            if ($staticPath === '') {
                return $absolutePath;
            }
        } while (realpath($staticPath) === false);

        $dynamicPath[] = realpath($staticPath);
        return implode(DIRECTORY_SEPARATOR, array_reverse($dynamicPath));
    }

    protected function jsonEncode($input): string
    {
        return (string)json_encode($input, JSON_UNESCAPED_SLASHES);
    }
}
