<?php

/**
 * Router script for `php artisan serve` / PHP built-in server.
 * Emulates Apache mod_rewrite by serving static files from /public when present,
 * otherwise forwards to the front controller.
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
