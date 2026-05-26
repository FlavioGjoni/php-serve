<?php

namespace App\Controller;

use App\Jwt\Firebase;
use App\Jwt\Lcobucci;

class JwtCompareController {

    public function index(): void {

        $response = [];

        $response[] = $this->executionWrapper(
            'Firebase',
            'RS256',
            5000,
            function () {
                new Firebase()('RS256');
            }
        );

        $response[] = $this->executionWrapper(
            'Firebase',
            'ES256',
            5000,
            function () {
                new Firebase()('ES256');
            }
        );


        $response[] = $this->executionWrapper(
            'Lcobucci',
            'RS256',
            5000,
            function () {
                New Lcobucci()('RS256');
            }
        );

        $response[] = $this->executionWrapper(
            'Lcobucci',
            'ES256',
            5000,
            function () {
                New Lcobucci()('ES256');
            }
        );

        echo json_encode($response);
    }

    protected function executionWrapper(string $libraryName, string $signingAlg, int $iterations, callable $callback): array {
        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $callback();
        }

        $end = microtime(true);

        return [
            'library_name' => $libraryName,
            'signing_alg' => $signingAlg,
            'iterations' => $iterations,
            'execution_time' => round($end - $start, 5),
        ];
    }
}
