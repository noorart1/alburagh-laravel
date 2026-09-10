<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require '/home/iutfcdpi/alburagh-laravel/vendor/autoload.php';

$app = require_once '/home/iutfcdpi/alburagh-laravel/bootstrap/app.php';

$app->handleRequest(Request::capture());
