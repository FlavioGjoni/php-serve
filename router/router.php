<?php

$dispatcher = require __DIR__ . '/dispatcher.php';

$http_method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// strip query string
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}

$uri = rawurldecode($uri);

// trigger
/** @var FastRoute\Dispatcher $dispatcher */
$route_info = $dispatcher->dispatch($http_method, $uri);

// handle route
header('Content-Type: application/json');
switch ($route_info[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo json_encode(['error' => 'Route not Found']);
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;

    case FastRoute\Dispatcher::FOUND:
        $handler = $route_info[1];
        $vars = $route_info[2];

        call_user_func($handler, $vars);
        break;
}
