<?php

namespace App\Controller;

use DateMalformedStringException;
use DateTimeImmutable;
use Exception;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Random\RandomException;

class JwtFinalController {

    /**
     * @throws DateMalformedStringException
     * @throws RandomException
     * @throws Exception
     */
    public function index(): void {
        // In dev environments, keys will be read locally
        // Dev: .env for the local file location
        // Prod: Vault will be used for the key values
        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::file(__DIR__ . '/../../jwt-keys/es256-private.pem'),
            InMemory::file(__DIR__ . '/../../jwt-keys/es256-public.pem')
        );

        // time now
        $now = new DateTimeImmutable();

        // Vault/.env
        $kid = 'es256-v1';

        // more claims (payload values) can be added using the method `withClaim`
        $token = $config->builder()
            ->withHeader('kid', $kid)
            ->issuedBy('wasp-tas')  // Vault/.env
            ->permittedFor('wasp-internal')  // Vault/.env
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify('+30 seconds')) // 30 is the value in seconds, also configurable by Vault/.env
            ->identifiedBy(bin2hex(random_bytes(16))) // we can use uuid v4 library
            ->withClaim('transactionKey', '9f2c1a7b4d8e6f3c0b5a9d2e7f1c4b8e6a3d9f0c2b7e5a1d4c6f8b0e9a3d1c7f')
            ->getToken($config->signer(), $config->signingKey());

        $jwt = $token->toString();

        $result = $this->read($jwt);
        echo json_encode([
            'jwt' => $jwt,
            'result' => $result
        ]);
    }

    /**
     * @throws Exception
     */
    protected function read(string $jwt): array {
        // key values are not relevant here
        // The public key used to get retrieve the values will the handle by the `kid`
        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText('key1'),
            InMemory::plainText('key2')
        );

        // parse value and get kid value
        $token = $config->parser()->parse($jwt);
        $kid = $token->headers()->get('kid');

        // add custom logic to validate which file (test) or value from Vault (production) matches the correct `kid`
        $publicKey = match ($kid) {
            'es256-v1' => InMemory::file(__DIR__ . '/../../jwt-keys/es256-public.pem'), // Vault/.env
            default => throw new Exception('Unknown kid'),
        };

        // perform validation
        $config->validator()->assert(
            $token,
            new SignedWith($config->signer(), $publicKey),
            new IssuedBy('wasp-tas'), // Vault/.env
            new PermittedFor('wasp-internal'), // Vault/.env
            new StrictValidAt(SystemClock::fromUTC())
        );

        // read value
        // can be structure in any way
        return [
            'iss' => $token->claims()->get('iss'),
            'jti' => $token->claims()->get('jti'),
            'kid' => $token->headers()->get('kid'),
            'transactionKey' => $token->claims()->get('transactionKey'),
        ];
    }
}
