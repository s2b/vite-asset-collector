<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Acceptance;

use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Renders a page of a TYPO3 instance in composer mode and checks the embedded assets.
 * The instance is set up by Build/Testing/AcceptanceTestsBootstrap.php, see
 * Tests/Acceptance/Fixtures/sitepackage/Resources/Private/Templates/Page.html
 * for the rendered template.
 */
final class FrontendTest extends TestCase
{
    private const EXTENSION_BUILD_DIR = __DIR__ . '/Fixtures/sitepackage/Resources/Public/Vite/';
    private const PUBLIC_BUILD_DIR = __DIR__ . '/Fixtures/public/_assets/vite/';

    #[Test]
    public function buildModeEmbedsAssetsFromExtension(): void
    {
        $page = $this->fetchPage();

        $this->assertAssetIsServed(
            $this->findAttribute($page, '//script[@type="module"][contains(@src, "/Main-B8fW2pLq.js")]/@src'),
            self::EXTENSION_BUILD_DIR . 'assets/Main-B8fW2pLq.js',
        );
        $this->assertAssetIsServed(
            $this->findAttribute($page, '//link[@rel="stylesheet"][contains(@href, "/Main-Hn4vY6sE.css")]/@href'),
            self::EXTENSION_BUILD_DIR . 'assets/Main-Hn4vY6sE.css',
        );
        $this->assertAssetIsServed(
            $this->findAttribute($page, '//link[@rel="modulepreload"][contains(@href, "/Shared-Cx3k9aZb.js")]/@href'),
            self::EXTENSION_BUILD_DIR . 'assets/Shared-Cx3k9aZb.js',
        );
        $this->assertAssetIsServed(
            $this->findAttribute($page, '//img[@id="logo"]/@src'),
            self::EXTENSION_BUILD_DIR . 'assets/Logo-Dq7rT2mN.svg',
        );
    }

    #[Test]
    public function buildModeEmbedsAssetsFromPublicFolder(): void
    {
        $page = $this->fetchPage();

        $this->assertAssetIsServed(
            $this->findAttribute($page, '//script[@type="module"][contains(@src, "/Default-Kp2sQ8wX.js")]/@src'),
            self::PUBLIC_BUILD_DIR . 'assets/Default-Kp2sQ8wX.js',
        );
        $inlineCss = (string)file_get_contents(self::PUBLIC_BUILD_DIR . 'assets/Default-Rt5uV1yA.css');
        self::assertStringContainsString(trim($inlineCss), $this->findText($page, '//style'));
    }

    #[Test]
    public function buildModeOnlyReferencesReachableLocalUrls(): void
    {
        $page = $this->fetchPage();

        $urls = $this->queryValues($page, '//*[@src]/@src | //link[@href]/@href');
        self::assertNotEmpty($urls);
        foreach ($urls as $url) {
            self::assertStringNotContainsString(self::getEnvironmentVariable('ACCEPTANCE_PROJECT_PATH'), $url, 'URL contains file system path');
            self::assertStringStartsNotWith('http', $url, sprintf('Unexpected external URL "%s" in build mode', $url));
            $response = $this->createClient()->get($this->resolveUrl($url));
            self::assertSame(200, $response->getStatusCode(), sprintf('URL "%s" is not reachable', $url));
        }
    }

    #[Test]
    public function devModeReferencesDevServer(): void
    {
        $devServerUri = self::getEnvironmentVariable('ACCEPTANCE_DEV_SERVER_URI');
        $page = $this->fetchPage(devServer: true);

        self::assertSame(
            [
                $devServerUri . '/@vite/client',
                $devServerUri . '/.Build/vendor/praetorius/vite-asset-collector-test-sitepackage/Resources/Private/JavaScript/Main.js',
                $devServerUri . '/.Build/vendor/praetorius/vite-asset-collector-test-sitepackage/Resources/Private/JavaScript/Default.js',
            ],
            $this->queryValues($page, '//script[@type="module"]/@src'),
        );
        self::assertSame(
            $devServerUri . '/.Build/vendor/praetorius/vite-asset-collector-test-sitepackage/Resources/Private/Images/Logo.svg',
            $this->findAttribute($page, '//img[@id="logo"]/@src'),
        );
        self::assertSame([], $this->queryValues($page, '//link[@rel="stylesheet"]/@href | //link[@rel="modulepreload"]/@href | //style'));
    }

    private function assertAssetIsServed(string $url, string $expectedFile): void
    {
        self::assertStringNotContainsString(self::getEnvironmentVariable('ACCEPTANCE_PROJECT_PATH'), $url, 'URL contains file system path');
        $response = $this->createClient()->get($this->resolveUrl($url));
        self::assertSame(200, $response->getStatusCode(), sprintf('Asset "%s" is not reachable', $url));
        self::assertSame((string)file_get_contents($expectedFile), (string)$response->getBody(), sprintf('Asset "%s" has unexpected content', $url));
    }

    /**
     * @param bool $devServer render page as if the Vite dev server was running
     *        (see Tests/Acceptance/Fixtures/router.php)
     */
    private function fetchPage(bool $devServer = false): \DOMXPath
    {
        $response = $this->createClient()->get(
            self::getEnvironmentVariable('ACCEPTANCE_BASE_URL') . '/',
            ['headers' => ['X-Vite-Dev-Server' => $devServer ? '1' : '0']],
        );
        $html = (string)$response->getBody();
        self::assertSame(200, $response->getStatusCode(), sprintf("Page could not be rendered:\n%s", strip_tags($html)));
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        return new \DOMXPath($document);
    }

    /**
     * Returns the text content of all matching nodes (for attribute nodes: their values)
     *
     * @return list<string>
     */
    private function queryValues(\DOMXPath $page, string $query): array
    {
        $nodes = $page->query($query);
        self::assertNotFalse($nodes, sprintf('Invalid XPath query "%s"', $query));
        $values = [];
        foreach ($nodes as $node) {
            if ($node instanceof \DOMNode) {
                $values[] = (string)$node->textContent;
            }
        }
        return $values;
    }

    private function findAttribute(\DOMXPath $page, string $query): string
    {
        $values = $this->queryValues($page, $query);
        self::assertCount(1, $values, sprintf('Expected exactly one match for "%s"', $query));
        return $values[0];
    }

    private function findText(\DOMXPath $page, string $query): string
    {
        return implode('', $this->queryValues($page, $query));
    }

    private function resolveUrl(string $url): string
    {
        $baseUrl = self::getEnvironmentVariable('ACCEPTANCE_BASE_URL');
        return match (true) {
            str_starts_with($url, 'http://'), str_starts_with($url, 'https://') => $url,
            str_starts_with($url, '/') => $baseUrl . $url,
            default => $baseUrl . '/' . $url,
        };
    }

    private function createClient(): Client
    {
        return new Client(['http_errors' => false, 'allow_redirects' => false, 'timeout' => 10]);
    }

    private static function getEnvironmentVariable(string $name): string
    {
        $value = getenv($name);
        if ($value === false || $value === '') {
            self::fail(sprintf('Environment variable %s is missing. Acceptance tests need to be executed with "composer test:acceptance".', $name));
        }
        return $value;
    }
}
