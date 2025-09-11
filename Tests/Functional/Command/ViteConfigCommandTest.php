<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Command\ViteConfigCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ViteConfigCommandTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/vite_asset_collector',
        'typo3conf/ext/vite_asset_collector/Tests/Fixtures/test_extension',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/vite_asset_collector/Tests/Fixtures' => 'fileadmin/Fixtures/',
    ];

    public function setUp(): void
    {
        parent::setUp();

        // Replace symlink with copy to get realistic environment
        $symlink = $this->instancePath . '/typo3conf/ext/test_extension';
        if (is_link($symlink)) {
            $path = readlink($symlink);
            unlink($symlink);
            $this->copyRecursive($path, $symlink);
        }
    }

    public function tearDown(): void
    {
        // Delete config file from overwriteConfigFile() test
        $overwriteConfigFile = self::getInstancePath() . '/vite.config.js';
        if (file_exists($overwriteConfigFile)) {
            unlink($overwriteConfigFile);
        }
    }

    public static function outputConfigDataProvider(): array
    {
        return [
            'withoutEntrypoints' => [
                self::getInstancePath(),
                [],
                'withoutEntrypoints.vite.config.js',
            ],
            'entrypointFileadmin' => [
                self::getInstancePath(),
                [
                    '--entry' => ['fileadmin/Fixtures/test_extension/Resources/Private/JavaScript/main.js'],
                ],
                'entrypointFileadmin.vite.config.js',
            ],
            'entrypointFileadminChangedWorkingDir' => [
                self::getInstancePath() . '/fileadmin/Fixtures/',
                [
                    '--entry' => ['test_extension/Resources/Private/JavaScript/main.js'],
                ],
                'entrypointFileadmin.vite.config.js',
            ],
            'entrypointExtensionPath' => [
                self::getInstancePath(),
                [
                    '--entry' => ['EXT:test_extension/Resources/Private/JavaScript/main.js'],
                ],
                'entrypointExtensionPath.vite.config.js',
            ],
            'multipleEntrypoints' => [
                self::getInstancePath(),
                [
                    '--entry' => [
                        'fileadmin/Fixtures/test_extension/Resources/Private/JavaScript/main.js',
                        'fileadmin/Fixtures/test_extension/Resources/Private/JavaScript/another.js',
                    ],
                ],
                'multipleEntrypoints.vite.config.js',
            ],
            'globEntrypoint' => [
                self::getInstancePath(),
                [
                    '--entry' => ['EXT:test_extension/Resources/Private/*.js'],
                    '--glob' => null,
                ],
                'globEntrypoint.vite.config.js',
            ],
            'noAutoOrigin' => [
                self::getInstancePath(),
                [
                    '--entry' => ['EXT:test_extension/Resources/Private/JavaScript/main.js'],
                    '--no-auto-origin' => null,
                ],
                'noAutoOrigin.vite.config.js',
            ],
            'globNoAutoOrigin' => [
                self::getInstancePath(),
                [
                    '--entry' => ['EXT:test_extension/Resources/Private/*.js'],
                    '--glob' => null,
                    '--no-auto-origin' => null,
                ],
                'globNoAutoOrigin.vite.config.js',
            ],
            'configForExtension' => [
                self::getInstancePath(),
                [
                    'extension' => 'test_extension',
                    '--entry' => [
                        'EXT:test_extension/Resources/Private/JavaScript/main.js',
                        'typo3conf/ext/test_extension/Resources/Private/JavaScript/another.js',
                    ],
                ],
                'configForExtension.vite.config.js',
            ],
        ];
    }

    #[Test]
    #[DataProvider('outputConfigDataProvider')]
    #[IgnoreDeprecations]
    public function outputConfig(string $workingDir, array $input, string $expectedResultFile): void
    {
        chdir($workingDir);

        $output = new BufferedOutput();
        $command = $this->get(ViteConfigCommand::class);
        $command->run(new ArrayInput($input, $command->getDefinition()), $output);

        $expectedResult = file_get_contents(
            self::getInstancePath() . "/fileadmin/Fixtures/ViteConfigCommand/OutputConfig/$expectedResultFile"
        );
        self::assertEquals($expectedResult, $output->fetch());
    }

    public static function writeConfigToFileDataProvider(): array
    {
        return [
            'inProjectRoot' => [
                self::getInstancePath(),
                [
                    '--entry' => [
                        'Frontend/Main.js',
                        'EXT:test_extension/Resources/Private/Main.js',
                    ],
                    '--outputfile' => './vite.config.js',
                ],
                self::getInstancePath() . '/vite.config.js',
                'inProjectRoot.vite.config.js',
            ],
            'inProjectRootChangedWorkingDir' => [
                self::getInstancePath() . '/fileadmin',
                [
                    '--entry' => [
                        '../Frontend/Main.js',
                        'EXT:test_extension/Resources/Private/Main.js',
                    ],
                    '--outputfile' => '../vite.config.js',
                ],
                self::getInstancePath() . '/vite.config.js',
                'inProjectRoot.vite.config.js',
            ],
            'inExtensionRoot' => [
                self::getInstancePath(),
                [
                    '--entry' => [
                        'Frontend/Main.js',
                        'EXT:test_extension/Resources/Private/Main.js',
                    ],
                    '--outputfile' => 'EXT:test_extension/vite.config.js',
                ],
                self::getInstancePath() . '/typo3conf/ext/test_extension/vite.config.js',
                'inExtensionRoot.vite.config.js',
            ],
            'inExtensionRootChangedWorkingDir' => [
                self::getInstancePath() . '/typo3conf/ext/test_extension',
                [
                    '--entry' => [
                        '../../../Frontend/Main.js',
                        'EXT:test_extension/Resources/Private/Main.js',
                    ],
                    '--outputfile' => './vite.config.js',
                ],
                self::getInstancePath() . '/typo3conf/ext/test_extension/vite.config.js',
                'inExtensionRoot.vite.config.js',
            ],
            'inNonExistingSubfolder' => [
                self::getInstancePath(),
                [
                    '--entry' => [
                        'Frontend/Main.js',
                        'EXT:test_extension/Resources/Private/Main.js',
                    ],
                    '--outputfile' => 'Build/Frontend/vite.config.js',
                ],
                self::getInstancePath() . '/Build/Frontend/vite.config.js',
                'inNonExistingSubfolder.vite.config.js',
            ],
            'inNonExistingSubfolderChangedWorkingDir' => [
                self::getInstancePath() . '/fileadmin',
                [
                    '--entry' => [
                        '../Frontend/Main.js',
                        'EXT:test_extension/Resources/Private/Main.js',
                    ],
                    '--outputfile' => '../Build/Frontend/vite.config.js',
                ],
                self::getInstancePath() . '/Build/Frontend/vite.config.js',
                'inNonExistingSubfolder.vite.config.js',
            ],
            'configForExtension' => [
                self::getInstancePath(),
                [
                    'extension' => 'test_extension',
                    '--entry' => [
                        'EXT:test_extension/Resources/Private/Main.js',
                        'typo3conf/ext/test_extension/Resources/Private/Another.js',
                    ],
                    '--outputfile' => 'EXT:test_extension/vite.config.js',
                ],
                self::getInstancePath() . '/typo3conf/ext/test_extension/vite.config.js',
                'configForExtension.vite.config.js',
            ],
            'configForExtensionChangedWorkingDir' => [
                self::getInstancePath() . '/typo3conf/ext',
                [
                    'extension' => 'test_extension',
                    '--entry' => [
                        'EXT:test_extension/Resources/Private/Main.js',
                        'test_extension/Resources/Private/Another.js',
                    ],
                    '--outputfile' => 'EXT:test_extension/vite.config.js',
                ],
                self::getInstancePath() . '/typo3conf/ext/test_extension/vite.config.js',
                'configForExtension.vite.config.js',
            ],
        ];
    }

    #[Test]
    #[DataProvider('writeConfigToFileDataProvider')]
    #[IgnoreDeprecations]
    public function writeConfigToFile(string $workingDir, array $input, string $outputFile, string $expectedResultFile): void
    {
        chdir($workingDir);

        $output = new BufferedOutput();
        $command = $this->get(ViteConfigCommand::class);
        $command->run(new ArrayInput($input, $command->getDefinition()), $output);

        self::assertFileExists($outputFile);

        $expectedResult = file_get_contents(
            self::getInstancePath() . "/fileadmin/Fixtures/ViteConfigCommand/WriteConfigToFile/$expectedResultFile"
        );
        self::assertEquals($expectedResult, file_get_contents($outputFile));

        unlink($outputFile);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function overwriteConfigFile(): void
    {
        chdir(self::getInstancePath());
        $command = $this->get(ViteConfigCommand::class);

        $outputFile = self::getInstancePath() . '/vite.config.js';

        $fixturePath = self::getInstancePath() . '/fileadmin/Fixtures/ViteConfigCommand/WriteConfigToFile';
        $expectedFirstResult = file_get_contents("$fixturePath/inProjectRootBeforeOverwrite.vite.config.js");
        $expectedLaterResult = file_get_contents("$fixturePath/inProjectRoot.vite.config.js");

        $input = ['--outputfile' => './vite.config.js'];
        $output = new BufferedOutput();
        $command->run(new ArrayInput($input, $command->getDefinition()), $output);
        self::assertEquals(
            sprintf("Vite config has been written to %s.\n", $outputFile),
            $output->fetch(),
            'Initial file can be written'
        );
        self::assertEquals(
            $expectedFirstResult,
            file_get_contents($outputFile),
            'Initially generated file is correct'
        );

        $input['--entry'] = [
            'Frontend/Main.js',
            'EXT:test_extension/Resources/Private/Main.js',
        ];
        $output = new BufferedOutput();
        $command->run(new ArrayInput($input, $command->getDefinition()), $output);
        self::assertEquals(
            sprintf("Output file %s already exists. Use --force if you want to overwrite the existing file.\n", $outputFile),
            $output->fetch(),
            'Overwrite attempt without force option'
        );
        self::assertEquals(
            $expectedFirstResult,
            file_get_contents($outputFile),
            'File should not be overwritten without --force option'
        );

        $input['--force'] = null;
        $output = new BufferedOutput();
        $command->run(new ArrayInput($input, $command->getDefinition()), $output);
        self::assertEquals(
            sprintf("Vite config has been written to %s.\n", $outputFile),
            $output->fetch(),
            'Overwrite attempt with force option'
        );
        self::assertEquals(
            $expectedLaterResult,
            file_get_contents($outputFile),
            'file is overwritten due to --force option'
        );

        unlink($outputFile);
    }

    /**
     * Copy a directory structure $from a source $to a destination,
     *
     * @param string $from Absolute source path
     * @param string $to Absolute target path
     * @return bool True if all went well
     */
    protected function copyRecursive($from, $to): bool
    {
        $dir = opendir($from);
        if (!file_exists($to)) {
            mkdir($to, 0775, true);
        }
        $result = true;
        while (false !== ($file = readdir($dir))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (is_dir($from . DIRECTORY_SEPARATOR . $file)) {
                $success = $this->copyRecursive($from . DIRECTORY_SEPARATOR . $file, $to . DIRECTORY_SEPARATOR . $file);
                $result = $result & $success;
            } else {
                $success = copy($from . DIRECTORY_SEPARATOR . $file, $to . DIRECTORY_SEPARATOR . $file);
                $result = $result & $success;
            }
        }
        closedir($dir);
        return (bool)$result;
    }
}
