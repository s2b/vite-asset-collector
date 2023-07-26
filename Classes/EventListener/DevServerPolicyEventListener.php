<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\EventListener;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\PolicyMutatedEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceScheme;

final class DevServerPolicyEventListener
{
    public function __construct(
        private ExtensionConfiguration $extensionConfiguration
    ) {
    }

    public function __invoke(PolicyMutatedEvent $event): void
    {
        if (!$event->scope->type->isFrontend()) {
            return;
        }

        if (!$this->useDevServer()) {
            return;
        }

        $event->getCurrentPolicy()->mutate(
            new Mutation(
                MutationMode::Extend,
                Directive::ConnectSrc,
                SourceScheme::wss,
            ),
            new Mutation(
                MutationMode::Extend,
                Directive::StyleSrc,
                SourceKeyword::unsafeInline
            ),
        );
    }

    private function useDevServer(): bool
    {
        $useDevServer = $this->extensionConfiguration->get('vite_asset_collector', 'useDevServer');
        if ($useDevServer === 'auto') {
            return Environment::getContext()->isDevelopment();
        }
        return (bool)$useDevServer;
    }
}
