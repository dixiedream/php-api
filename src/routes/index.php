<?php

use Phroute\Phroute\RouteCollector;
use routes\Filters;

$collector = new RouteCollector();

/**
 * Filters
 */
$collector->filter('auth', [Filters::class, 'auth']);

/**
 * Login required routes
 */
$collector->group(['before' => 'auth'], function (RouteCollector $collector) {
    $collector->group(['prefix' => 'api'], function (RouteCollector $collector) {
        // Put your routes here, PHRoute documentation https://github.com/mrjgreen/phroute
        // Ex. $collector->get('/', [Controller::class, 'method']);
    });
});

//  DEBUGGING ROUTES
if (getenv('DEBUG')) {
    $collector->get('/PHPInfo', 'phpinfo');
}

return $collector;
