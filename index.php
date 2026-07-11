<?php

/**
 * OpenGifs — Root entry point.
 *
 * Some hosting providers do not allow changing the document root
 * to the `public/` directory. This file forwards all requests
 * to the Laravel front controller in `public/index.php`.
 *
 * If your host supports it, point your document root to `public/`
 * and delete this file.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
