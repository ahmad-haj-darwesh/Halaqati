<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists(__DIR__.'/storage/framework/down')) {
    require __DIR__.'/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/HandleRequests.php';
}

// Register the Composer autoloader...
require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/bootstrap/app.php')
    ->run();
