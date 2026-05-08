<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\Imaging;

use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconSize;
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
                'defaultManifest' => 'EXT:vite_asset_collector/Tests/Fixtures/DefaultManifest/.vite/manifest.json',
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
        $this->icon->setSize(class_exists(IconSize::class) ? IconSize::SMALL : Icon::SIZE_SMALL);
    }

    #[Test]
    public function getLogoIconMarkup(): void
    {
        $this->svgIconProvider->prepareIconMarkup($this->icon, ['source' => 'typo3.svg']);
        self::assertEquals(
            '<img src="typo3conf/ext/vite_asset_collector/Tests/Fixtures/DefaultManifest/assets/typo3-57f5650e.svg" width="16" height="16" alt="" />',
            $this->icon->getMarkup()
        );
    }

    #[Test]
    public function getLogoIconMarkupInline(): void
    {
        $this->svgIconProvider->prepareIconMarkup($this->icon, ['source' => 'typo3.svg']);
        $expectedSvgMarkup = <<<SVG_MARKUP
<svg xmlns="http://www.w3.org/2000/svg" width="180" height="180">
    <g fill="#f49700">
        <path d="M122.254 114.079c-1.888.558-3.391.766-5.365.766-16.171 0-39.915-56.51-39.915-75.317 0-6.922 1.638-9.233 3.951-11.216-19.792 2.31-43.539 9.572-51.134 18.805-1.641 2.314-2.637 5.938-2.637 10.56 0 29.362 31.339 95.984 53.445 95.984 10.226-.001 27.467-16.813 41.655-39.582M111.931 26.34c20.449 0 40.915 3.298 40.915 14.84 0 23.42-14.854 51.802-22.433 51.802-13.527 0-30.352-37.614-30.352-56.422 0-8.576 3.298-10.22 11.87-10.22"/>
    </g>
</svg>
SVG_MARKUP;

        self::assertEquals(
            $expectedSvgMarkup,
            $this->subject->getIcon($this->registeredIconIdentifier)->getAlternativeMarkup(SvgIconProvider::MARKUP_IDENTIFIER_INLINE)
        );
    }

    #[Test]
    public function getIconReturnsCorrectMarkupIfIconIsRegisteredAsLogoIcon(): void
    {
        $iconMarkup = $this->subject->getIcon($this->registeredIconIdentifier)->render();
        self::assertMatchesRegularExpression(
            '<span class="t3js-icon icon icon-size-([a-z]*) icon-state-default icon-typo3-logo" data-identifier="typo3-logo"(.*)>',
            $iconMarkup
        );

        self::assertStringContainsString(
            '<img src="typo3conf/ext/vite_asset_collector/Tests/Fixtures/DefaultManifest/assets/typo3-57f5650e.svg" width="32" height="32" alt="" />',
            $iconMarkup
        );
    }
}
