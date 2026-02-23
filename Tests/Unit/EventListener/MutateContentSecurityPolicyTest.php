<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\EventListener\MutateContentSecurityPolicy;
use Praetorius\ViteAssetCollector\Service\ViteService;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\PolicyMutatedEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Policy;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MutateContentSecurityPolicyTest extends UnitTestCase
{
    public static function getDefaultManifestFileDataProvider(): iterable
    {
        //
        // Basic use cases
        //
        yield 'do nothing if dev server is disabled' => [
            false,
            'https://localhost:1234',
            new ConsumableNonce('theNonce'),
            new Policy(),
            [''],
        ];

        yield 'empty project policy, nonce not consumed' => [
            true,
            'https://localhost:1234',
            new ConsumableNonce('theNonce'),
            new Policy(),
            [
                'connect-src http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                'script-src-elem http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                'style-src-elem http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                'font-src http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                'img-src http://localhost:1234 https://localhost:1234 wss://localhost:1234',
            ],
        ];

        $nonce = new ConsumableNonce('theNonce');
        $nonce->consume();
        yield 'empty project policy, nonce consumed' => [
            true,
            'https://localhost:1234',
            $nonce,
            new Policy(),
            [
                'connect-src http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                "script-src-elem http://localhost:1234 https://localhost:1234 wss://localhost:1234 'nonce-" . $nonce->consume() . "'",
                "style-src-elem http://localhost:1234 https://localhost:1234 wss://localhost:1234 'nonce-" . $nonce->consume() . "'",
                'font-src http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                'img-src http://localhost:1234 https://localhost:1234 wss://localhost:1234',
            ],
        ];

        $nonce = new ConsumableNonce('theNonce');
        $nonce->consume();
        yield 'project policy with default uri, nonce consumed' => [
            true,
            'https://localhost:1234',
            $nonce,
            (new Policy(new UriValue('https://example.com'))),
            [
                'default-src https://example.com',
                'connect-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                "script-src-elem https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234 'nonce-" . $nonce->consume() . "'",
                "style-src-elem https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234 'nonce-" . $nonce->consume() . "'",
                'font-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                'img-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
            ],
        ];

        //
        // Nonces for scripts
        //
        $nonce = new ConsumableNonce('theNonce');
        $nonce->consume();
        yield 'project policy with default uri and nonce for scripts, nonce consumed' => [
            true,
            'https://localhost:1234',
            $nonce,
            (new Policy(new UriValue('https://example.com')))->extend(Directive::ScriptSrc, SourceKeyword::nonceProxy),
            [
                'default-src https://example.com',
                "script-src https://example.com 'nonce-" . $nonce->consume() . "'",
                'connect-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                "script-src-elem https://example.com 'nonce-" . $nonce->consume() . "' http://localhost:1234 https://localhost:1234 wss://localhost:1234",
                "style-src-elem https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234 'nonce-" . $nonce->consume() . "'",
                'font-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                'img-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
            ],
        ];

        $nonce = new ConsumableNonce('theNonce');
        $nonce->consume();
        yield 'project policy with default uri and nonce for script elements, nonce consumed' => [
            true,
            'https://localhost:1234',
            $nonce,
            (new Policy(new UriValue('https://example.com')))->extend(Directive::ScriptSrcElem, SourceKeyword::nonceProxy),
            [
                'default-src https://example.com',
                "script-src-elem https://example.com 'nonce-" . $nonce->consume() . "' http://localhost:1234 https://localhost:1234 wss://localhost:1234",
                'connect-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                "style-src-elem https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234 'nonce-" . $nonce->consume() . "'",
                'font-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                'img-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
            ],
        ];

        $nonce = new ConsumableNonce('theNonce');
        $nonce->consume();
        yield 'project policy with default uri and unsafe-inline for scripts, nonce consumed' => [
            true,
            'https://localhost:1234',
            $nonce,
            (new Policy(new UriValue('https://example.com')))->extend(Directive::ScriptSrc, SourceKeyword::unsafeInline),
            [
                'default-src https://example.com',
                "script-src https://example.com 'unsafe-inline'",
                'connect-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                "script-src-elem https://example.com 'unsafe-inline' http://localhost:1234 https://localhost:1234 wss://localhost:1234",
                "style-src-elem https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234 'nonce-" . $nonce->consume() . "'",
                'font-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                'img-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
            ],
        ];

        $nonce = new ConsumableNonce('theNonce');
        $nonce->consume();
        yield 'project policy with default uri and unsafe-inline for script elements, nonce consumed' => [
            true,
            'https://localhost:1234',
            $nonce,
            (new Policy(new UriValue('https://example.com')))->extend(Directive::ScriptSrcElem, SourceKeyword::unsafeInline),
            [
                'default-src https://example.com',
                "script-src-elem https://example.com 'unsafe-inline' http://localhost:1234 https://localhost:1234 wss://localhost:1234",
                'connect-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                "style-src-elem https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234 'nonce-" . $nonce->consume() . "'",
                'font-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                'img-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
            ],
        ];

        //
        // Nonces for styles
        //
        $nonce = new ConsumableNonce('theNonce');
        $nonce->consume();
        yield 'project policy with default uri and nonce for styles, nonce consumed' => [
            true,
            'https://localhost:1234',
            $nonce,
            (new Policy(new UriValue('https://example.com')))->extend(Directive::StyleSrc, SourceKeyword::nonceProxy),
            [
                'default-src https://example.com',
                "style-src https://example.com 'nonce-" . $nonce->consume() . "'",
                'connect-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                "script-src-elem https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234 'nonce-" . $nonce->consume() . "'",
                "style-src-elem https://example.com 'nonce-" . $nonce->consume() . "' http://localhost:1234 https://localhost:1234 wss://localhost:1234",
                'font-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                'img-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
            ],
        ];

        $nonce = new ConsumableNonce('theNonce');
        $nonce->consume();
        yield 'project policy with default uri and nonce for style elements, nonce consumed' => [
            true,
            'https://localhost:1234',
            $nonce,
            (new Policy(new UriValue('https://example.com')))->extend(Directive::StyleSrcElem, SourceKeyword::nonceProxy),
            [
                'default-src https://example.com',
                "style-src-elem https://example.com 'nonce-" . $nonce->consume() . "' http://localhost:1234 https://localhost:1234 wss://localhost:1234",
                'connect-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                "script-src-elem https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234 'nonce-" . $nonce->consume() . "'",
                'font-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                'img-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
            ],
        ];

        $nonce = new ConsumableNonce('theNonce');
        $nonce->consume();
        yield 'project policy with default uri and unsafe-inline for styles, nonce consumed' => [
            true,
            'https://localhost:1234',
            $nonce,
            (new Policy(new UriValue('https://example.com')))->extend(Directive::StyleSrc, SourceKeyword::unsafeInline),
            [
                'default-src https://example.com',
                "style-src https://example.com 'unsafe-inline'",
                'connect-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                "script-src-elem https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234 'nonce-" . $nonce->consume() . "'",
                "style-src-elem https://example.com 'unsafe-inline' http://localhost:1234 https://localhost:1234 wss://localhost:1234",
                'font-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                'img-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
            ],
        ];

        $nonce = new ConsumableNonce('theNonce');
        $nonce->consume();
        yield 'project policy with default uri and unsafe-inline for style elements, nonce consumed' => [
            true,
            'https://localhost:1234',
            $nonce,
            (new Policy(new UriValue('https://example.com')))->extend(Directive::StyleSrcElem, SourceKeyword::unsafeInline),
            [
                'default-src https://example.com',
                "style-src-elem https://example.com 'unsafe-inline' http://localhost:1234 https://localhost:1234 wss://localhost:1234",
                'connect-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                "script-src-elem https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234 'nonce-" . $nonce->consume() . "'",
                'font-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                'img-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
            ],
        ];
    }

    #[Test]
    #[DataProvider('getDefaultManifestFileDataProvider')]
    public function getDefaultManifestFile(bool $useDevServer, string $devServerUri, ConsumableNonce $nonce, Policy $currentPolicy, array $expectedPolicy): void
    {
        $mockViteService = $this->createMock(ViteService::class);
        $mockViteService->method('useDevServer')->willReturn($useDevServer);
        $mockViteService->method('determineDevServer')->willReturn(new Uri($devServerUri));

        $event = new PolicyMutatedEvent(
            Scope::frontend(),
            new ServerRequest(),
            new Policy(),
            $currentPolicy,
        );
        $subject = new MutateContentSecurityPolicy($mockViteService);
        $subject($event);
        self::assertEquals($expectedPolicy, explode('; ', $currentPolicy->compile($nonce)));
    }
}
