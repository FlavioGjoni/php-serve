<?php

namespace App\Controller;

use App\Services\InputValidator;
use App\Services\RequestJsonParser;
use App\Services\ResponseHandler;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Throwable;

class JwtGenerateTokenJsonKeysController {

    protected array $response = [
        'success' => false,
        'errors' => [],
        'jwt_keys' => []
    ];

    protected int $http_code = 200;

    protected array $jwt_keys = [];

    public function index(): void {
        if (!$this->validate_inputs()) {
            ResponseHandler::json_response($this->response, $this->http_code);
            return;
        }

        $this->validate_and_create_jwts();

        $this->inspect_result();

        ResponseHandler::json_response($this->response, $this->http_code);
    }

    protected function validate_inputs(): bool {
        $json_parser = new RequestJsonParser();

        if (!$json_parser->parse()) {
            $this->http_code = 400;
            $this->response['errors'][] = $json_parser->get_parse_error();
            return false;
        }

        $input_validator = new InputValidator($json_parser->get_body_data());

        $this->jwt_keys = $input_validator->validate_json_string('jwt_keys');

        if ($input_validator->has_errors() || $this->jwt_keys === false) {
            $this->http_code = 422;
            $this->response['errors'] = $input_validator->get_errors();
            return false;
        }

        return true;
    }

    protected function validate_and_create_jwts(): void {
        $index = -1;
        foreach ($this->jwt_keys as $jwt_key_set) {
            $index++;

            $input_validator = new InputValidator($jwt_key_set);
            $input_validator->validate_string('public_key');
            $input_validator->validate_string('private_key');

            if ($input_validator->has_errors()) {
                $this->response['jwt_keys'][$index] = [
                    'success' => false,
                    'index' => $index,
                    'errors' => $input_validator->get_errors()
                ];
                continue;
            }

            try {
                $jwt_token = $this->generate_token($jwt_key_set);

                $this->response['jwt_keys'][$index] = [
                    'success' => true,
                    'index' => $index,
                    'jwt' => $jwt_token
                ];

            } catch (Throwable $e) {
                $this->response['jwt_keys'][$index] = [
                    'success' => false,
                    'index' => $index,
                    'errors' => [$e->getMessage()]
                ];
            }
        }
    }

    protected function generate_token(array $jwt_key_set): string {
        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($jwt_key_set['private_key']),
            InMemory::plainText($jwt_key_set['public_key']),
        );

        $now = new DateTimeImmutable();

        $builder = $config->builder()
            ->issuedBy('iss')
            ->permittedFor('aud')
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify("+100 seconds"))
            ->identifiedBy(bin2hex(random_bytes(16)));

        $token = $builder->getToken($config->signer(), $config->signingKey());

        return $token->toString();
    }

    protected function inspect_result(): void {
        foreach ($this->response['jwt_keys'] as $jwt_key_result) {
            if ($jwt_key_result['success']) {
                $this->response['success'] = true;
                return;
            }
        }

        $this->http_code = 400;
    }
}
