<?php

use App\Route\RouteHandler;
use FastRoute\RouteCollector;

$routes = require __DIR__ . '/routes.php';
$route_handler = new RouteHandler($routes);

return FastRoute\simpleDispatcher(function (RouteCollector $r) use ($route_handler) {
    $route_handler->register_routes($r);
});
