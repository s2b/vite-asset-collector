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

    protected function configure(): void
    {
        $this
            ->setHelp('Generates a boilerplate vite config file')
            ->addArgument('extension', InputArgument::OPTIONAL, 'If provided, vite config will be generated for extension context instead of project context', null)
            ->addOption('entry', 'e', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'vite entrypoint(s)')
            ->addOption('glob', 'g', InputOption::VALUE_NONE, 'Enable glob patterns for entrypoints; this requires "fast-glob" to be installed')
            ->addOption('outputfile', 'o', InputOption::VALUE_REQUIRED, 'Write generated vite config to file')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Write file even if it already exists')
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

        $entrypoints = [];
        foreach ($input->getOption('entry') as $entrypoint) {
            $entrypoint = $this->partialRealpath($this->getAbsoluteInputPath($entrypoint));
            $entrypointRelativeToRoot = PathUtility::getRelativePath($rootPath, PathUtility::dirname($entrypoint));
            $entrypoints[] = $entrypointRelativeToRoot . PathUtility::basename($entrypoint);
        }

        $viteConfig = $this->generateViteConfig(
            $configFileRelativeToRoot,
            $entrypoints,
            $input->getOption('glob'),
            $extensionName !== null
        );

        if ($configFile) {
            GeneralUtility::mkdir_deep(PathUtility::dirname($configFile));
            GeneralUtility::writeFile($configFile, $viteConfig);
            $output->write(sprintf('Vite config has been written to %s.', $configFile), true);
        } else {
            $output->write($viteConfig);
        }

        return Command::SUCCESS;
    }

    protected function generateViteConfig(
        string $rootPath,
        array $entrypoints,
        bool $useGlob = false,
        bool $configurationForExtension = false
    ): string {
        $configuration = explode(self::TEMPLATE_SEPARATOR, $this->getTemplate($useGlob), 3);

        $encodedEntrypoints = array_map(fn ($entry) => json_encode($entry, JSON_UNESCAPED_SLASHES), $entrypoints);
        $entrypointCode = implode(",\n  ", $encodedEntrypoints);

        $outputPath = ($configurationForExtension) ? 'Resources/Public/Vite/' : 'public/_assets/vite/';

        $configuration[1] = vsprintf(ltrim(self::CONFIGURATION_TEMPLATE), [
            ($configurationForExtension) ? 'Extension' : 'TYPO3',
            json_encode($rootPath, JSON_UNESCAPED_SLASHES),
            $entrypointCode ? "  $entrypointCode," : '',
            json_encode($outputPath, JSON_UNESCAPED_SLASHES),
        ]);

        return implode('', $configuration);
    }

    protected function getTemplate(bool $useGlob = false): string
    {
        $templateFile = $useGlob ? 'glob.vite.config.js' : 'static.vite.config.js';
        return file_get_contents(ExtensionManagementUtility::extPath(
            'vite_asset_collector',
            "Resources/Private/ViteConfigTemplates/$templateFile"
        ));
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

    public function injectPackageManager(PackageManager $packageManager)
    {
        $this->packageManager = $packageManager;
    }
}
