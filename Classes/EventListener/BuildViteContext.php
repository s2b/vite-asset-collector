<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\EventListener;

use Praetorius\ViteAssetCollector\Context\ViteContext;
use Praetorius\ViteAssetCollector\Event\BuildViteContextEvent;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Uri;

#[AsEventListener('praetorius/vite-asset-collector/initialize-vite-context')]
final readonly class BuildViteContext
{
    private const DEFAULT_PORT = 5173;

    public function __construct(private ExtensionConfiguration $extensionConfiguration) {}

    public function __invoke(BuildViteContextEvent $event): void
    {
        $event->setViteContext(new ViteContext(
            useDevServer: $this->determineDevServerStatus(),
            devServer: $this->determineDevServer($event->getRequest()),
        ));
    }

    private function determineDevServerStatus(): bool
    {
        $useDevServer = $this->extensionConfiguration->get('vite_asset_collector', 'useDevServer');
        if ($useDevServer !== 'auto') {
            return (bool)$useDevServer;
        }
        // This constant is written by vite-plugin-typo3 to .env
        $serverRunning = getenv('VITE_SERVER_RUNNING');
        if ($serverRunning !== false) {
            return (bool)$serverRunning;
        }
        // Decide based on TYPO3 context as fallback. This makes sure that on
        // production, dev server isn't used unless configured explicitly
        return Environment::getContext()->isDevelopment();
    }

    private function determineDevServer(ServerRequestInterface $request): UriInterface
    {
        $devServerUri = $this->extensionConfiguration->get('vite_asset_collector', 'devServerUri');
        if ($devServerUri !== 'auto') {
            return new Uri($devServerUri);
        }
        // This constant is used by ddev-vite-sidecar and contains the full DDEV server uri
        $serverUri = getenv('VITE_SERVER_URI');
        if ($serverUri) {
            return new Uri($serverUri);
        }
        // This constant is used by ddev-viteserve and contains only the port that can be
        // combined with any ddev domain of the current project
        $vitePort = getenv('VITE_PRIMARY_PORT') ?: self::DEFAULT_PORT;
        return $request->getUri()->withPath('')->withPort((int)$vitePort);
    }
}
