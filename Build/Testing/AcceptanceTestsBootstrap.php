<?php

declare(strict_types=1);

/*
 * Bootstrap for the acceptance tests (Build/Testing/AcceptanceTests.xml)
 *
 * The extension's composer root project is used as TYPO3 installation in composer
 * mode, with the extension itself and the test sitepackage from
 * Tests/Acceptance/Fixtures/sitepackage/ installed outside of the public folder:
 *
 * 1. Sets up TYPO3 with SQLite (creates config/ and var/ in the repository root)
 * 2. Creates a site using the sitepackage's site set
 * 3. Starts PHP's built-in web server, which is stopped after the tests
 */

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

require dirname(__DIR__, 2) . '/.Build/vendor/autoload.php';

(static function (): void {
    $rootPath = dirname(__DIR__, 2);
    $fixturePath = $rootPath . '/Tests/Acceptance/Fixtures';
    $publicPath = $rootPath . '/.Build/web';
    $port = getenv('ACCEPTANCE_PORT') ?: '8601';
    $baseUrl = 'http://127.0.0.1:' . $port;
    // Dev server is never contacted, the tests only check the generated URIs
    $devServerUri = 'https://vite.example.com:5173';

    $filesystem = new Filesystem();
    $typo3 = static fn(string ...$arguments) => (new Process([PHP_BINARY, $rootPath . '/.Build/bin/typo3', ...$arguments], $rootPath))->mustRun();

    // Fresh TYPO3 installation
    $filesystem->remove([$rootPath . '/config', $rootPath . '/var']);
    $typo3(
        'setup',
        '--no-interaction',
        '--force',
        '--driver=sqlite',
        '--admin-username=admin',
        '--admin-user-password=Acceptance-Test-1',
        '--admin-email=admin@example.com',
        '--project-name=vite_asset_collector acceptance tests',
        '--server-type=other',
    );
    $filesystem->mirror($fixturePath . '/config', $rootPath . '/config', null, ['override' => true]);
    $databases = glob($rootPath . '/var/sqlite/*.sqlite') ?: [];
    (new PDO('sqlite:' . reset($databases)))->exec(
        "INSERT INTO pages (uid, pid, title, doktype, is_siteroot, slug) VALUES (1, 0, 'Home', 1, 1, '/')"
    );

    // Build output in public folder, used via default manifest
    $filesystem->remove($publicPath . '/_assets/vite');
    $filesystem->mirror($fixturePath . '/public/_assets/vite', $publicPath . '/_assets/vite');

    $typo3('cache:flush');

    $server = new Process(
        [PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', $publicPath, $fixturePath . '/router.php'],
        $rootPath,
        ['TYPO3_CONTEXT' => 'Development', 'VITE_SERVER_URI' => $devServerUri],
    );
    $server->start();
    register_shutdown_function($server->stop(...));
    for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', (int)$port); $i++) {
        if (!$server->isRunning()) {
            throw new RuntimeException('Web server could not be started: ' . $server->getErrorOutput());
        }
        usleep(100000);
    }

    putenv('ACCEPTANCE_BASE_URL=' . $baseUrl);
    putenv('ACCEPTANCE_DEV_SERVER_URI=' . $devServerUri);
    putenv('ACCEPTANCE_PROJECT_PATH=' . $rootPath);
})();
