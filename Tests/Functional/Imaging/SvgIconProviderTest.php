<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\Imaging;

use Praetorius\ViteAssetCollector\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SvgIconProviderTest extends FunctionalTestCase
{
    protected IconFactory $subject;
    protected SvgIconProvider $svgIconProvider;
    protected Icon $icon;
    protected string $registeredIconIdentifier = 'typo3-logo';

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/vite_asset_collector',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/vite_asset_collector/Tests/Fixtures' => 'fileadmin/Fixtures/',
    ];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'vite_asset_collector' => [
                'defaultManifest' => 'EXT:vite_asset_collector/Tests/Fixtures/DefaultManifest/manifest.json',
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->get(IconFactory::class);

        $iconRegistry = $this->get(IconRegistry::class);
        $iconRegistry->registerIcon(
            $this->registeredIconIdentifier,
            SvgIconProvider::class,
            [
                'source' => 'typo3.svg',
            ]
        );

        $this->svgIconProvider = $this->get(SvgIconProvider::class);
        $this->icon = GeneralUtility::makeInstance(Icon::class);
        $this->icon->setIdentifier('typo3-logo');
        $this->icon->setSize(Icon::SIZE_SMALL);
    }

    /**
     * @test
     */
    public function getLogoIconMarkup(): void
    {
        $this->svgIconProvider->prepareIconMarkup($this->icon, ['source' => 'typo3.svg']);
        self::assertEquals(
            '<img src="typo3conf/ext/vite_asset_collector/Tests/Fixtures/DefaultManifest/assets/typo3-57f5650e.svg" width="16" height="16" alt="" />',
            $this->icon->getMarkup()
        );
    }

    /**
     * @test
     */
    public function getLogoIconMarkupInline(): void
    {
        // @todo: Test inline SVG
        $this->svgIconProvider->prepareIconMarkup($this->icon, ['source' => 'typo3.svg']);
        self::assertEquals(
            '<img src="o3conf/ext/vite_asset_collector/Tests/Fixtures/DefaultManifest/assets/typo3-57f5650e.svg" width="16" height="16" alt="" />',
            $this->subject->getIcon($this->registeredIconIdentifier)->getAlternativeMarkup()
        );
    }

    /**
     * @test
     */
    public function getIconReturnsCorrectMarkupIfIconIsRegisteredAsLogoIcon(): void
    {
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-medium icon-state-default icon-typo3-logo" data-identifier="typo3-logo" aria-hidden="true">',
            $this->subject->getIcon($this->registeredIconIdentifier)->render()
        );
    }
}
