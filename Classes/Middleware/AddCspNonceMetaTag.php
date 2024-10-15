<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Domain\ConsumableString;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class AddCspNonceMetaTag implements MiddlewareInterface
{

    public function __construct(
        private readonly Features $features,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // return early in case CSP shall not be used
        if (!$this->features->isFeatureEnabled('security.frontend.enforceContentSecurityPolicy')) {
            return $handler->handle($request);
        }
        if (Environment::getContext()->isDevelopment()) {
            /** @var ConsumableString|null $nonce */
            $nonceAttribute = $request->getAttribute('nonce');
            if ($nonceAttribute instanceof ConsumableString) {
                $nonce = $nonceAttribute->consume();
                $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
                $pageRenderer->addHeaderData('<meta property="csp-nonce" nonce="' . $nonce . '">');
            }
        }
        return $handler->handle($request);
    }
}
