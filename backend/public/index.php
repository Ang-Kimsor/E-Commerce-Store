<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Mock fileinfo functions if extension is not available (workaround)
if (!extension_loaded('fileinfo')) {
    if (!function_exists('finfo_open')) {
        function finfo_open($options = FILEINFO_MIME_TYPE, $magic_file = null) {
            return true;
        }
        function finfo_file($finfo, $filename, $options = FILEINFO_MIME_TYPE, $context = null) {
            // Return generic mime type
            return 'application/octet-stream';
        }
        function finfo_close($finfo) {
            return true;
        }
    }
}

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
