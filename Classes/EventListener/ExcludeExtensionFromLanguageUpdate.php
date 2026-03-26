<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\EventListener;

use TYPO3\CMS\Install\Service\Event\ModifyLanguagePacksEvent;

final readonly class ExcludeExtensionFromLanguageUpdate
{
    public function __invoke(ModifyLanguagePacksEvent $event): void
    {
        $event->removeExtension('vite_asset_collector');
    }
}
