<?php

namespace App\Route;

use FastRoute\RouteCollector;

class RouteHandler {

    public function __construct(
        protected array $routes,
    ) {
    }

    public function register_routes(RouteCollector $r): void {
        foreach ($this->routes as $route_info) {
            $method = $route_info['method'];
            $route = $route_info['route'];
            $handler = $route_info['handler'];

            // string
            if (is_string($method)) {
                $this->register_single_route($r, $method, $route, $handler);
                continue;
            }

            // array
            foreach ($method as $single_method) {
                $this->register_single_route($r, $single_method, $route, $handler);
            }
        }
    }

    public function register_single_route(RouteCollector $r, string $method, string $route, callable $handler): void {
        $r->addRoute(strtoupper($method), $route, $handler);
    }
}
