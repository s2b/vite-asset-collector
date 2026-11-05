<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Functional\Middleware;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Tests\Fixtures\ViteContextEventListener\EventListener\VariableAvailabilityListener;
use Praetorius\ViteAssetCollector\Tests\Functional\AbstractViteFunctionalTestCase;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Http\Application;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

final class ViteContextMiddlewareTest extends AbstractViteFunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/vite_asset_collector',
        'typo3conf/ext/vite_asset_collector/Tests/Fixtures/vite_context_event_listener',
    ];

    // Frontend requests need database tables
    protected bool $initializeDatabase = true;

    /**
     * Requests a non-existing page to make sure that:
     *
     * - the vite context is already available when page resolution fails, so that
     *   error pages (e.g. 404, rendered by ErrorController) can use it
     * - BuildViteContextEvent listeners can determine the vite context based on
     *   the current site and language
     */
    #[Test]
    public function viteContextIsBuiltWithSiteAndLanguageBeforePageNotFoundHandling(): void
    {
        VariableAvailabilityListener::reset();
        $this->setUpSite();

        $response = $this->executeFrontendSubRequest(new InternalRequest('https://website.local/does-not-exist'));

        self::assertSame(404, $response->getStatusCode(), 'Page resolution should fail after vite context has been built');
        self::assertInstanceOf(ServerRequestInterface::class, VariableAvailabilityListener::$request, 'Vite context has not been built');
        $site = VariableAvailabilityListener::$request->getAttribute('site');
        self::assertInstanceOf(Site::class, $site);
        self::assertSame('main', $site->getIdentifier());
        $language = VariableAvailabilityListener::$request->getAttribute('language');
        self::assertInstanceOf(SiteLanguage::class, $language);
        self::assertSame(0, $language->getLanguageId());
    }

    /**
     * BuildViteContextEvent listeners should be able to determine the vite context
     * based on the current backend user
     */
    #[Test]
    public function backendUserIsAvailableToBackendEventListeners(): void
    {
        VariableAvailabilityListener::reset();
        unset($GLOBALS['BE_USER']);

        $request = new ServerRequest('https://localhost/typo3/login', 'GET', null, [], [
            'HTTP_HOST' => 'localhost',
            'HTTPS' => 'on',
            'REQUEST_URI' => '/typo3/login',
            'SCRIPT_NAME' => '/index.php',
            'SCRIPT_FILENAME' => Environment::getPublicPath() . '/index.php',
        ]);
        $response = $this->get(Application::class)->handle(
            $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertInstanceOf(ServerRequestInterface::class, VariableAvailabilityListener::$request, 'Vite context has not been built');
        self::assertInstanceOf(BackendUserAuthentication::class, VariableAvailabilityListener::$backendUser);
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function devServerIsAddedToFrontendContentSecurityPolicyDataProvider(): array
    {
        return [
            'page' => ['https://website.local/', 200],
            'page not found' => ['https://website.local/does-not-exist', 404],
        ];
    }

    #[Test]
    #[DataProvider('devServerIsAddedToFrontendContentSecurityPolicyDataProvider')]
    public function devServerIsAddedToFrontendContentSecurityPolicy(string $uri, int $expectedStatusCode): void
    {
        $this->get(ExtensionConfiguration::class)->set('vite_asset_collector', [
            'useDevServer' => '1',
            'devServerUri' => 'https://vite.example.com:1234',
        ]);
        $this->setUpSite(enforceContentSecurityPolicy: true);

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));

        self::assertSame($expectedStatusCode, $response->getStatusCode());
        self::assertTrue($response->hasHeader('Content-Security-Policy'), 'CSP header is missing');
        $policy = $response->getHeaderLine('Content-Security-Policy');
        self::assertMatchesRegularExpression('#script-src-elem [^;]*https://vite\.example\.com:1234#', $policy);
        self::assertMatchesRegularExpression('#connect-src [^;]*wss://vite\.example\.com:1234#', $policy);
    }

    /**
     * Creates site "main" on https://website.local/ with a simple root page
     */
    private function setUpSite(bool $enforceContentSecurityPolicy = false): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Database/pages.csv');
        $this->setUpFrontendRootPage(1, ['EXT:vite_asset_collector/Tests/Fixtures/Frontend/page.typoscript']);
        if ($enforceContentSecurityPolicy) {
            // CSP configuration is read from csp.yaml, there is no API to write it
            $siteConfigPath = Environment::getConfigPath() . '/sites/main';
            GeneralUtility::mkdir_deep($siteConfigPath);
            file_put_contents($siteConfigPath . '/csp.yaml', "enforce: true\n");
        }
        // Writing the site configuration also flushes the site cache
        $this->get(SiteWriter::class)->write('main', [
            'rootPageId' => 1,
            'base' => 'https://website.local/',
            'languages' => [
                [
                    'title' => 'English',
                    'enabled' => true,
                    'languageId' => 0,
                    'base' => '/',
                    'locale' => 'en_US.UTF-8',
                    'navigationTitle' => 'English',
                    'flag' => 'us',
                ],
            ],
        ]);
    }
}
