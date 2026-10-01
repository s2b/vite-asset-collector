<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;

#[AsEventListener('praetorius/vite-asset-collector/exclude-extension-from-language-update')]
final readonly class ExcludeExtensionFromLanguageUpdate
{
    // TODO Remove old event name once support for v13 is dropped
    public function __invoke(\TYPO3\CMS\Core\Localization\Event\ModifyLanguagePacksEvent|\TYPO3\CMS\Install\Service\Event\ModifyLanguagePacksEvent $event): void
    {
        $event->removeExtension('vite_asset_collector');
    }
}
