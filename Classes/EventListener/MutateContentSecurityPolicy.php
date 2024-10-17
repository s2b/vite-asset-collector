<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\EventListener;

use Praetorius\ViteAssetCollector\Service\ViteService;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\PolicyMutatedEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;

final class MutateContentSecurityPolicy
{
    public function __construct(
        private readonly ViteService $viteService,
    ) {}

    public function __invoke(PolicyMutatedEvent $event): void
    {
        if (!$this->viteService->useDevServer()) {
            return;
        }

        $request = $GLOBALS['TYPO3_REQUEST'] ?? new ServerRequest();
        $viteServerUri = $this->viteService->determineDevServer($request);
        $uris = [
            new UriValue((string)$viteServerUri),
            new UriValue('wss://' . $viteServerUri->getHost() . ':' . $viteServerUri->getPort()),
        ];

        // Allow viteServer url in CSP
        $event->getCurrentPolicy()->extend(
            Directive::ConnectSrc,
            ...$uris,
        );
        $event->getCurrentPolicy()->extend(
            Directive::ScriptSrcElem,
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

        // Ensure that nonces are allowed for script and style tags
        $event->getCurrentPolicy()->extend(
            Directive::ScriptSrcElem,
            SourceKeyword::nonceProxy
        );
        $event->getCurrentPolicy()->extend(
            Directive::StyleSrcElem,
            SourceKeyword::nonceProxy
        );
    }
}
