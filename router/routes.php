<?php

use App\Controller\AllController;
use App\Controller\JwtCompareController;
use App\Controller\JwtController;
use App\Controller\JwtFinalController;
use App\Controller\JwtGenerateTokenController;

return [
    [
        'method' => 'get',
        'route' => '/jwt',
        'handler' => function () {
            new JwtController()->index();
        }
    ],
    [
        'method' => 'get',
        'route' => '/jwt/compare',
        'handler' => function () {
            new JwtCompareController()->index();
        }
    ],
    [
        'method' => 'get',
        'route' => '/jwt/final',
        'handler' => function () {
            new JwtFinalController()->index();
        }
    ],
    [
        'method' => 'post',
        'route' => '/jwt/generate-token',
        'handler' => function () {
            new JwtGenerateTokenController()->index();
        }
    ],
    [
        'method' => ['get', 'post', 'put', 'patch', 'delete', 'options'],
        'route' => '/{any:.*}',
        'handler' => function () {
            new AllController()->index();
        }
    ]
];
