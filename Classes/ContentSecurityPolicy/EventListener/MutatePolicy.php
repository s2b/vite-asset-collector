<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\ContentSecurityPolicy\EventListener;

use Praetorius\ViteAssetCollector\Service\ViteService;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\PolicyMutatedEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;

final class MutatePolicy
{
    public function __construct(
        private readonly ViteService $viteService,
    ) {}

    public function __invoke(PolicyMutatedEvent $event): void
    {
        if ($event->scope->type->isBackend()) {
            return;
        }
        if (!$this->viteService->useDevServer()) {
            return;
        }

        $viteServerUri = $this->viteService->determineDevServer($GLOBALS['TYPO3_REQUEST']);
        $uris = [
            #SourceKeyword::strictDynamic,
            SourceKeyword::unsafeInline,
            new UriValue((string)$viteServerUri),
            new UriValue('wss://' . $viteServerUri->getHost() . ':' . $viteServerUri->getPort())
        ];

        // Allow viteServer url in CSP
        $event->getCurrentPolicy()->extend(
            Directive::ConnectSrc,
            ...$uris,
        );
        $event->getCurrentPolicy()->extend(
            Directive::ScriptSrc,
            ...$uris,
        );
        $event->getCurrentPolicy()->extend(
            Directive::StyleSrcElem,
            ...$uris,
        );
        $event->getCurrentPolicy()->extend(
            Directive::FontSrc,
            ...$uris,
        );
        $event->getCurrentPolicy()->extend(
            Directive::ImgSrc,
            ...$uris,
        );

        // remove nonce is currently necessary to allow 'unsafe inline' for viteServer
        $event->getCurrentPolicy()->reduce(
            Directive::ScriptSrc,
            SourceKeyword::nonceProxy
        );
        $event->getCurrentPolicy()->reduce(
            Directive::StyleSrcElem,
            SourceKeyword::nonceProxy
        );
    }
}
