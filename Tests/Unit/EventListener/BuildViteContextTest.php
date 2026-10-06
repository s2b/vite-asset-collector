<?php

declare(strict_types=1);

namespace Praetorius\ViteAssetCollector\Tests\Unit\Asset\Embedding;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Praetorius\ViteAssetCollector\Context\ViteContext;
use Praetorius\ViteAssetCollector\Event\BuildViteContextEvent;
use Praetorius\ViteAssetCollector\EventListener\BuildViteContext;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BuildViteContextTest extends UnitTestCase
{
    protected bool $backupEnvironment = true;

    public static function buildViteContextDataProvider(): array
    {
        return [
            'configured dev server uri' => [
                'useDevServer' => '1',
                'devServerUri' => 'https://example.com',
                'requestUri' => 'https://localhost',
                'typo3Context' => null,
                'environmentVariables' => [],
                'expectedUseDevServer' => true,
                'expectedDevServerUri' => 'https://example.com',
            ],
            'automatic dev server uri based on request' => [
                'useDevServer' => '1',
                'devServerUri' => 'auto',
                'requestUri' => 'https://localhost',
                'typo3Context' => null,
                'environmentVariables' => [],
                'expectedUseDevServer' => true,
                'expectedDevServerUri' => 'https://localhost:5173',
            ],
            'automatic dev server uri based on supplied port' => [
                'useDevServer' => '1',
                'devServerUri' => 'auto',
                'requestUri' => 'https://localhost',
                'typo3Context' => null,
                'environmentVariables' => ['VITE_PRIMARY_PORT' => 1234],
                'expectedUseDevServer' => true,
                'expectedDevServerUri' => 'https://localhost:1234',
            ],
            'automatic dev server uri based on supplied uri' => [
                'useDevServer' => '1',
                'devServerUri' => 'auto',
                'requestUri' => 'https://localhost',
                'typo3Context' => null,
                'environmentVariables' => ['VITE_PRIMARY_PORT' => 4321, 'VITE_SERVER_URI' => 'https://example.com:1234'],
                'expectedUseDevServer' => true,
                'expectedDevServerUri' => 'https://example.com:1234',
            ],
            'disabled dev server' => [
                'useDevServer' => '0',
                'devServerUri' => 'https://example.com',
                'requestUri' => 'https://localhost',
                'typo3Context' => null,
                'environmentVariables' => [],
                'expectedUseDevServer' => false,
                'expectedDevServerUri' => 'https://example.com',
            ],
            'automatic dev server status on production' => [
                'useDevServer' => 'auto',
                'devServerUri' => 'https://example.com',
                'requestUri' => 'https://localhost',
                'typo3Context' => 'Production',
                'environmentVariables' => [],
                'expectedUseDevServer' => false,
                'expectedDevServerUri' => 'https://example.com',
            ],
            'automatic dev server status on development' => [
                'useDevServer' => 'auto',
                'devServerUri' => 'https://example.com',
                'requestUri' => 'https://localhost',
                'typo3Context' => 'Development',
                'environmentVariables' => [],
                'expectedUseDevServer' => true,
                'expectedDevServerUri' => 'https://example.com',
            ],
        ];
    }

    #[Test]
    #[DataProvider('buildViteContextDataProvider')]
    public function buildViteContext(
        string $useDevServer,
        string $devServerUri,
        string $requestUri,
        ?string $typo3Context,
        array $environmentVariables,
        bool $expectedUseDevServer,
        string $expectedDevServerUri,
    ): void {
        if ($typo3Context) {
            Environment::initialize(
                new ApplicationContext($typo3Context),
                Environment::isCli(),
                Environment::isComposerMode(),
                Environment::getProjectPath(),
                Environment::getPublicPath(),
                Environment::getVarPath(),
                Environment::getConfigPath(),
                Environment::getCurrentScript(),
                Environment::isWindows() ? 'WINDOWS' : 'UNIX'
            );
        }
        $originalVariables = [];
        foreach ($environmentVariables as $name => $value) {
            $originalVariables[$name] = getenv($name);
            putenv($name . '=' . $value);
        }
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration
            ->method('get')
            ->willReturnMap([
                ['vite_asset_collector', 'useDevServer', $useDevServer],
                ['vite_asset_collector', 'devServerUri', $devServerUri],
            ]);
        $event = new BuildViteContextEvent(new ServerRequest($requestUri));
        $subject = new BuildViteContext($extensionConfiguration);
        $subject($event);
        self::assertInstanceOf(ViteContext::class, $event->getViteContext());
        self::assertSame($expectedUseDevServer, $event->getViteContext()->useDevServer());
        self::assertSame($expectedDevServerUri, (string)$event->getViteContext()->getDevServer());
        foreach ($originalVariables as $name => $value) {
            putenv($name . '=' . (string)$value);
        }
    }
}
