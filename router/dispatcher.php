<?php

use App\Controller\AllController;
use App\Controller\JwtCompareController;
use App\Controller\JwtController;
use App\Controller\JwtFinalController;
use App\Controller\JwtGenerateTokenController;
use FastRoute\RouteCollector;

$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {
    // jwt
    $r->addRoute('GET', '/jwt', function () {
        new JwtController()->index();
    });

    // /jwt/compare
    $r->addRoute('GET', '/jwt/compare', function () {
        new JwtCompareController()->index();
    });

    // /jwt/final
    $r->addRoute('GET', '/jwt/final', function () {
        new JwtFinalController()->index();
    });

    // /jwt/generate-token
    $r->addRoute('POST', '/jwt/generate-token', function () {
        new JwtGenerateTokenController()->index();
    });

    // any route
    foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'] as $method) {
        $r->addRoute($method, '/{any:.*}', function () {
            new AllController()->index();
        });
    }
});