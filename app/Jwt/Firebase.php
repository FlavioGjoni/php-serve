<?php

namespace App\Jwt;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Firebase {

    protected string $algorithm;

    public function __invoke(string $algorithm): void {
        $this->algorithm = $algorithm;

        $jwt = $this->create();
        $this->read($jwt);
    }

    protected function create(): string {
        $privateKey = file_get_contents(
            $this->algorithm === 'RS256'
                ? __DIR__ . '/../../jwt-keys/rs256-private.pem'
                : __DIR__ . '/../../jwt-keys/es256-private.pem'
        );

        $now = time();

        $payload = [
            // standard claims
            'iss' => 'wasp-tas',
            'aud' => 'wasp-internal',
            'iat' => $now,
            'exp' => $now + 30,
            'jti' => bin2hex(random_bytes(16)),

            // custom claims
            'values' => require __DIR__ . '/static_payload.php'
        ];

        return JWT::encode($payload, $privateKey, $this->algorithm);
    }

    protected function read(string $jwt): void {
        $publicKey = file_get_contents(
            $this->algorithm === 'RS256'
                ? __DIR__ . '/../../jwt-keys/rs256-public.pem'
                : __DIR__ . '/../../jwt-keys/es256-public.pem'
        );

        $decoded = JWT::decode(
            $jwt,
            new Key($publicKey, $this->algorithm)
        );
    }
}
