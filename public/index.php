<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

$page = $uri === '' ? 'home' : $uri;

$page = preg_replace('#[^a-zA-Z0-9\-_/]#', '', $page);

$pagePath = __DIR__ . "/../src/pages/{$page}.php";

require __DIR__ . '/../src/components/base.php';

if (file_exists($pagePath)) {
    require $pagePath;
} else {
    http_response_code(404);
    require __DIR__ . '/../src/pages/404.php';
}

require __DIR__ . '/../src/components/end_base.php';
?>