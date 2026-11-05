<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\EventListener;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Context\ViteContext;
use Praetorius\ViteAssetCollector\EventListener\MutateContentSecurityPolicy;
use Praetorius\ViteAssetCollector\Tests\Functional\AbstractViteFunctionalTestCase;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Configuration\Behavior;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\DirectiveHashCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\PolicyMutatedEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Middleware\PolicyBag;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Policy;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\CMS\Core\Type\Map;

final class MutateContentSecurityPolicyTest extends AbstractViteFunctionalTestCase
{
    /**
     * @return iterable<string, array<string, mixed>>
     */
    public static function getDefaultManifestFileDataProvider(): iterable
    {
        //
        // Basic use cases
        //
        yield 'do nothing if dev server is disabled' => [
            'useDevServer' => false,
            'devServerUri' => 'https://localhost:1234',
            'nonce' => new ConsumableNonce('theNonce'),
            'currentPolicy' => new Policy(),
            'expectedPolicy' => [''],
        ];

        yield 'empty project policy, nonce not consumed' => [
            'useDevServer' => true,
            'devServerUri' => 'https://localhost:1234',
            'nonce' => new ConsumableNonce('theNonce'),
            'currentPolicy' => new Policy(),
            'expectedPolicy' => [
                'connect-src http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                'script-src-elem http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                'style-src-elem http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                'font-src http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                'img-src http://localhost:1234 https://localhost:1234 wss://localhost:1234',
            ],
        ];

        // e.g. ddev-vite-sidecar: VITE_SERVER_URI=https://vite.example.ddev.site
        yield 'dev server uri without port' => [
            'useDevServer' => true,
            'devServerUri' => 'https://vite.example.ddev.site',
            'nonce' => new ConsumableNonce('theNonce'),
            'currentPolicy' => new Policy(),
            'expectedPolicy' => [
                'connect-src http://vite.example.ddev.site https://vite.example.ddev.site wss://vite.example.ddev.site',
                'script-src-elem http://vite.example.ddev.site https://vite.example.ddev.site wss://vite.example.ddev.site',
                'style-src-elem http://vite.example.ddev.site https://vite.example.ddev.site wss://vite.example.ddev.site',
                'font-src http://vite.example.ddev.site https://vite.example.ddev.site wss://vite.example.ddev.site',
                'img-src http://vite.example.ddev.site https://vite.example.ddev.site wss://vite.example.ddev.site',
            ],
        ];

        $nonce = new ConsumableNonce('theNonce');
        $nonce->consume();
        yield 'empty project policy, nonce consumed' => [
            'useDevServer' => true,
            'devServerUri' => 'https://localhost:1234',
            'nonce' => $nonce,
            'currentPolicy' => new Policy(),
            'expectedPolicy' => [
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
            'useDevServer' => true,
            'devServerUri' => 'https://localhost:1234',
            'nonce' => $nonce,
            'currentPolicy' => (new Policy(new UriValue('https://example.com'))),
            'expectedPolicy' => [
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
            'useDevServer' => true,
            'devServerUri' => 'https://localhost:1234',
            'nonce' => $nonce,
            'currentPolicy' => (new Policy(new UriValue('https://example.com')))->extend(Directive::ScriptSrc, SourceKeyword::nonceProxy),
            'expectedPolicy' => [
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
            'useDevServer' => true,
            'devServerUri' => 'https://localhost:1234',
            'nonce' => $nonce,
            'currentPolicy' => (new Policy(new UriValue('https://example.com')))->extend(Directive::ScriptSrcElem, SourceKeyword::nonceProxy),
            'expectedPolicy' => [
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
            'useDevServer' => true,
            'devServerUri' => 'https://localhost:1234',
            'nonce' => $nonce,
            'currentPolicy' => (new Policy(new UriValue('https://example.com')))->extend(Directive::ScriptSrc, SourceKeyword::unsafeInline),
            'expectedPolicy' => [
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
            'useDevServer' => true,
            'devServerUri' => 'https://localhost:1234',
            'nonce' => $nonce,
            'currentPolicy' => (new Policy(new UriValue('https://example.com')))->extend(Directive::ScriptSrcElem, SourceKeyword::unsafeInline),
            'expectedPolicy' => [
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
            'useDevServer' => true,
            'devServerUri' => 'https://localhost:1234',
            'nonce' => $nonce,
            'currentPolicy' => (new Policy(new UriValue('https://example.com')))->extend(Directive::StyleSrc, SourceKeyword::nonceProxy),
            'expectedPolicy' => [
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
            'useDevServer' => true,
            'devServerUri' => 'https://localhost:1234',
            'nonce' => $nonce,
            'currentPolicy' => (new Policy(new UriValue('https://example.com')))->extend(Directive::StyleSrcElem, SourceKeyword::nonceProxy),
            'expectedPolicy' => [
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
            'useDevServer' => true,
            'devServerUri' => 'https://localhost:1234',
            'nonce' => $nonce,
            'currentPolicy' => (new Policy(new UriValue('https://example.com')))->extend(Directive::StyleSrc, SourceKeyword::unsafeInline),
            'expectedPolicy' => [
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
            'useDevServer' => true,
            'devServerUri' => 'https://localhost:1234',
            'nonce' => $nonce,
            'currentPolicy' => (new Policy(new UriValue('https://example.com')))->extend(Directive::StyleSrcElem, SourceKeyword::unsafeInline),
            'expectedPolicy' => [
                'default-src https://example.com',
                "style-src-elem https://example.com 'unsafe-inline' http://localhost:1234 https://localhost:1234 wss://localhost:1234",
                'connect-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                "script-src-elem https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234 'nonce-" . $nonce->consume() . "'",
                'font-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
                'img-src https://example.com http://localhost:1234 https://localhost:1234 wss://localhost:1234',
            ],
        ];
    }

    /**
     * @param string[] $expectedPolicy
     */
    #[Test]
    #[DataProvider('getDefaultManifestFileDataProvider')]
    public function getDefaultManifestFile(bool $useDevServer, string $devServerUri, ConsumableNonce $nonce, Policy $currentPolicy, array $expectedPolicy): void
    {
        $event = new PolicyMutatedEvent(
            Scope::frontend(),
            (new ServerRequest())->withAttribute('vite.context', new ViteContext($useDevServer, new Uri($devServerUri))),
            new Policy(),
            $currentPolicy,
        );
        $subject = new MutateContentSecurityPolicy();
        $subject($event);
        // TODO remove switch once support for TYPO3 v13 is dropped
        if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() > 13) {
            $compiledPolicy = $currentPolicy->compile(
                new PolicyBag(Scope::frontend(), new Map(), new Behavior(), $nonce, $this->get(DirectiveHashCollection::class))
            );
        } else {
            $compiledPolicy = $currentPolicy->compile($nonce);
        }
        self::assertEquals($expectedPolicy, explode('; ', $compiledPolicy));
    }
}
