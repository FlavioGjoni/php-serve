<?php

namespace App\Controller;

use App\Services\ResponseHandler;
use DateMalformedStringException;
use DateTimeImmutable;
use JsonException;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Random\RandomException;
use Throwable;

class JwtGenerateTokenController {

    protected array $response = [
        'success' => false,
        'errors' => [],
    ];

    protected int $httpCode = 200;

    protected array $request_data = [];

    protected array $claims = [];

    protected array $jwt_info = [
        'jwt' => null,
        'key_id' => null,
        'issuer' => null,
        'audience' => null,
        'ttl' => null,
        'expires_at' => null,
    ];

    /**
     * @throws DateMalformedStringException
     * @throws RandomException
     */
    public function index(): void {
        if (!$this->validate_inputs() || !$this->generate_token()) {
            ResponseHandler::json_response($this->response, $this->httpCode);
            return;
        }

        $this->response['success'] = true;
        ResponseHandler::json_response([...$this->response, 'jwt_info' => $this->jwt_info], $this->httpCode);
    }

    protected function validate_inputs(): bool {
        $body_data_raw = file_get_contents('php://input');

        // a. json body
        try {
            $this->request_data = json_decode($body_data_raw, true, 512, JSON_THROW_ON_ERROR);
            unset($body_data_raw);
        } catch (JsonException) {
            $this->response['message'] = "Invalid JSON";
            $this->httpCode = 400;
            return false;
        }

        // b. issuer
        $this->validate_simple_string('issuer');

        // c. audience
        $this->validate_simple_string('audience');

        // d. key_id
        $this->validate_simple_string('key_id');

        // e. ttl
        $this->validate_ttl('ttl');

        // f. error found
        if ($this->response['errors'] !== []) {
            $this->response['message'] = "Invalid inputs";
            $this->httpCode = 422;
            return false;
        }

        // g. custom claims
        if ($this->validate_simple_string('scope', false)) {
            $this->claims['scope'] = $this->request_data['scope'];
        }

        // h. no errors
        return true;
    }

    /**
     * @throws DateMalformedStringException
     * @throws RandomException
     */
    protected function generate_token(): bool {
        // token handler
        $config = $this->get_token_config();

        try {
            $this->do_generate_token($config);
            return true;
        } catch (Throwable $e) {
            $this->response['message'] = $e->getMessage();
            $this->response['trace'] = $e->getTrace();
            $this->httpCode = 422;
            return false;
        }
    }

    /**
     * @throws DateMalformedStringException
     * @throws RandomException
     */
    protected function do_generate_token(Configuration $config): void {
        // time now
        $now = new DateTimeImmutable();

        $builder = $config->builder()
            ->withHeader('kid', $this->request_data['key_id'])
            ->issuedBy($this->request_data['issuer'])
            ->permittedFor($this->request_data['audience'])
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify("+{$this->request_data['ttl']} seconds"))
            ->identifiedBy(bin2hex(random_bytes(16)));

        // claims
        foreach ($this->claims as $name => $claim) {
            $builder = $builder->withClaim($name, $claim);
        }

        $token = $builder->getToken($config->signer(), $config->signingKey());

        $jwt = $token->toString();

        $this->jwt_info = [
            'jwt' => $jwt,
            'key_id' => $this->request_data['key_id'],
            'issuer' => $this->request_data['issuer'],
            'audience' => $this->request_data['audience'],
            'ttl' => $this->request_data['ttl'],
            'expires_at' => $now->modify("+{$this->request_data['ttl']} seconds"),
        ];

        // claims
        $this->jwt_info = [...$this->jwt_info, ...$this->claims];
    }

    protected function get_token_config(): Configuration {
        // expect public_key and private_key values
        if (
            empty($this->request_data['public_key'])
            || !is_string($this->request_data['public_key'])
            || empty($this->request_data['private_key'])
            || !is_string($this->request_data['private_key'])
        ) {
            return Configuration::forAsymmetricSigner(
                new Sha256(),
                InMemory::file(__DIR__ . '/../../jwt-keys/generate_token/private.pem'),
                InMemory::file(__DIR__ . '/../../jwt-keys/generate_token/public.pem')
            );
        }

        return Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->request_data['private_key']),
            InMemory::plainText($this->request_data['public_key']),
        );
    }

    protected function validate_simple_string(string $input_name, bool $registerError = true): bool {
        if (!$this->validate_input_required($input_name, $registerError)) {
            return false;
        }

        if (!preg_match('/^[a-z0-9-]+$/', $this->request_data[$input_name])) {
            if ($registerError) {
                $this->response['errors'][$input_name] = "Input contains invalid characters - $input_name";
            }

            return false;
        }

        return true;
    }

    protected function validate_ttl(string $input_name): void {
        if (!$this->validate_input_required($input_name)) {
            return;
        }

        if (!preg_match('/^[1-9][0-9]*$/', $this->request_data[$input_name])) {
            $this->response['errors'][$input_name] = "Input must be a positive integer - $input_name";
        }
    }

    protected function validate_input_required(string $input_name, bool $registerError = true): bool {
        if (!isset($this->request_data[$input_name])) {
            if ($registerError) {
                $this->response['errors'][$input_name] = "Input required - $input_name";
            }

            return false;
        }

        return true;
    }
}
