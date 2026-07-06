<?php

/**
 * Fallback front controller for shared hosts where you cannot set a custom
 * document root (i.e. the domain is locked to serving from `public_html`).
 *
 * Use this ONLY if you deployed with the "split root" layout described in
 * docs/deployment/hostinger-shared-hosting.md:
 *
 *   /home/username/laravel_app/   <- everything from the Laravel repo
 *                                     EXCEPT the contents of public/
 *   /home/username/public_html/   <- the contents of Laravel's public/
 *                                     folder, with THIS file replacing
 *                                     public_html/index.php
 *
 * If your hosting plan lets you point the domain's document root directly at
 * `laravel_app/public`, you don't need this file at all — just use the
 * original public/index.php unmodified and skip the split-root layout.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$laravelRoot = __DIR__.'/../laravel_app';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $laravelRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $laravelRoot.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $laravelRoot.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
