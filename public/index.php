<?php

use Phroute\Phroute\Dispatcher;
use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;

require_once __DIR__ . '/../src/bootstrap.php';

// $routes = apcu_fetch('routes', $success);
// if ($success !== true) {
    $collector = require_once __DIR__ . '/../src/routes/index.php';
    $routes = $collector->getData();
    // apcu_store('routes', $routes, 3600);
// }
$dispatcher = new Dispatcher($routes);

try {
    error_log($_SERVER['REQUEST_URI']);
    $response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
} catch (HttpMethodNotAllowedException $e) {
    error_log($e->getMessage());
} catch (HttpRouteNotFoundException $e) {
    error_log($e->getMessage());
    http_response_code(404);
}

exit();
