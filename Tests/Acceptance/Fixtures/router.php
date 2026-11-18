<?php

declare(strict_types=1);

/*
 * Router for PHP's built-in web server, see Build/Testing/AcceptanceTestsBootstrap.php
 *
 * Requests with the header "X-Vite-Dev-Server: 1" are rendered as if the Vite dev
 * server was running, all other requests use the build output.
 */

putenv('VITE_SERVER_RUNNING=' . (($_SERVER['HTTP_X_VITE_DEV_SERVER'] ?? '') === '1' ? '1' : '0'));

// Serve existing files directly
$file = $_SERVER['DOCUMENT_ROOT'] . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (is_file($file) && !str_ends_with($file, '.php')) {
    return false;
}
require $_SERVER['DOCUMENT_ROOT'] . '/index.php';
