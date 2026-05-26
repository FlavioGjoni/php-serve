<?php

namespace App\Jwt;

use DateTimeImmutable;
use DateTimeZone;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Signer\Ecdsa\Sha256 as EcdsaSha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;

class Lcobucci {

    protected string $algorithm;

    public function __invoke(string $algorithm): void {
        $this->algorithm = $algorithm;

        $jwt = $this->create();
        $this->read($jwt);
    }

    protected function create(): string {
        $config = Configuration::forAsymmetricSigner(
            $this->algorithm === 'RS256' ? new Sha256() : new EcdsaSha256(),
            InMemory::file(
                $this->algorithm === 'RS256'
                    ? __DIR__ . '/../../jwt-keys/rs256-private.pem'
                    : __DIR__ . '/../../jwt-keys/es256-private.pem'
            ),
            InMemory::file($this->algorithm === 'RS256'
                ? __DIR__ . '/../../jwt-keys/rs256-public.pem'
                : __DIR__ . '/../../jwt-keys/es256-public.pem'
            )
        );

        $now = new DateTimeImmutable();

        $token = $config->builder()
            ->issuedBy('wasp-tas')
            ->permittedFor('wasp-internal')
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify('+30 seconds'))
            ->identifiedBy(bin2hex(random_bytes(16)))
            ->withClaim('values', require __DIR__ . '/static_payload.php')
            ->getToken($config->signer(), $config->signingKey());

        return $token->toString();
    }

    protected function read(string $jwt): void {
        $config = Configuration::forAsymmetricSigner(
            $this->algorithm === 'RS256' ? new Sha256() : new EcdsaSha256(),
            InMemory::file(
                $this->algorithm === 'RS256'
                    ? __DIR__ . '/../../jwt-keys/rs256-private.pem'
                    : __DIR__ . '/../../jwt-keys/es256-private.pem'
            ),
            InMemory::file($this->algorithm === 'RS256'
                ? __DIR__ . '/../../jwt-keys/rs256-public.pem'
                : __DIR__ . '/../../jwt-keys/es256-public.pem'
            )
        );

        $token = $config->parser()->parse($jwt);

        $config->validator()->assert(
            $token,
            new SignedWith($config->signer(), $config->verificationKey()),
            new IssuedBy('wasp-tas'),
            new PermittedFor('wasp-internal'),
            new StrictValidAt(new SystemClock(new DateTimeZone('UTC')))
        );
    }
}
