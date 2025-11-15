<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Middleware;

use Praetorius\ViteAssetCollector\Service\ViteService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class AddCspNonceMetaTag implements MiddlewareInterface
{
    public function __construct(
        private readonly ViteService $viteService,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $nonceAttribute = $request->getAttribute('nonce');
        if (!$nonceAttribute instanceof ConsumableNonce) {
            return $handler->handle($request);
        }

        if (!$this->viteService->useDevServer()) {
            return $handler->handle($request);
        }

        // Add metatag to <head> to allow vite to consume the current nonce
        // see: https://de.vitejs.dev/guide/features.html#content-security-policy-csp
        $nonce = $nonceAttribute->consume();
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addHeaderData('<meta property="csp-nonce" nonce="' . $nonce . '">');

        return $handler->handle($request);
    }
}
