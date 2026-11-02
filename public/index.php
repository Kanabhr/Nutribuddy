<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Prevent PHP notices/deprecations from being output into the response body.
// Laravel handles its own error display; raw PHP errors corrupt JSON API responses.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
