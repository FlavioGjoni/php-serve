<?php

namespace App\Controller;

use DateMalformedStringException;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Random\RandomException;

class JwtController {

    /**
     * @throws DateMalformedStringException|RandomException
     */
    public function index(): void {
        $secret = base64_encode(random_bytes(32));

        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::base64Encoded($secret)
        );

        $now = new DateTimeImmutable();

        $token = $config->builder()
            ->issuedBy('my-app')
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 hour'))
            ->withClaim('userId', 123)
            ->getToken($config->signer(), $config->signingKey());

        echo json_encode([
            'secret' => $secret,
            'jwt' => $token->toString(),
            'revert_result' => $this->revertToken($secret, $token->toString())
        ]);
    }

    protected function revertToken(string $secret, string $jwt): array {
        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::base64Encoded($secret)
        );

        $token = $config->parser()->parse($jwt);
        $token->toString();

        $config->validator()->assert(
            $token,
            new SignedWith($config->signer(), $config->verificationKey())
        );

        return [
            'token_to_string' => $token->toString(),
            'claims' => $token->claims()->all()
        ];
    }
}
