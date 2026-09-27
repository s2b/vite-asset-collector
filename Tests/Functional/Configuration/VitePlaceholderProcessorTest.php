<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\Configuration;

use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Exception\ViteException;
use Praetorius\ViteAssetCollector\Tests\Functional\AbstractViteFunctionalTestCase;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class VitePlaceholderProcessorTest extends AbstractViteFunctionalTestCase
{
    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'vite_asset_collector' => [
                'defaultManifest' => 'EXT:vite_asset_collector/Tests/Fixtures/DefaultManifest/.vite/manifest.json',
            ],
        ],
    ];

    #[Test]
    public function placeholdersInYamlFile(): void
    {
        $yamlLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
        $result = $yamlLoader->load(self::getInstancePath() . '/fileadmin/Fixtures/VitePlaceholderProcessor/test.yaml');
        self::assertEquals(
            [
                'testWithoutManifest' => [
                    'withoutQuotes' => 'typo3conf/ext/vite_asset_collector/Tests/Fixtures/DefaultManifest/assets/Default-973bb662.css',
                    'singleQuotes' => 'typo3conf/ext/vite_asset_collector/Tests/Fixtures/DefaultManifest/assets/Default-973bb662.css',
                    'doubleQuotes' => 'typo3conf/ext/vite_asset_collector/Tests/Fixtures/DefaultManifest/assets/Default-973bb662.css',
                ],
                'testWithManifest' => [
                    'withoutQuotes' => 'typo3conf/ext/vite_asset_collector/Tests/Fixtures/MultipleEntries/assets/Main-973bb662.css',
                    'singleQuotes' => 'typo3conf/ext/vite_asset_collector/Tests/Fixtures/MultipleEntries/assets/Main-973bb662.css',
                    'doubleQuotes' => 'typo3conf/ext/vite_asset_collector/Tests/Fixtures/MultipleEntries/assets/Main-973bb662.css',
                    'whitespace' => 'typo3conf/ext/vite_asset_collector/Tests/Fixtures/MultipleEntries/assets/Main-973bb662.css',
                ],
            ],
            $result
        );
    }

    #[Test]
    public function invalidPlaceholdersInYamlFile(): void
    {
        $this->expectException(ViteException::class);
        $this->expectExceptionCode(1694537554);
        $yamlLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
        $result = $yamlLoader->load(self::getInstancePath() . '/fileadmin/Fixtures/VitePlaceholderProcessor/emptyManifest.yaml');
    }
}
