<?php

namespace App\Services;

use JsonException;

class InputValidator {

    private array $errors = [];

    public function __construct(
        protected array $request_body,
    ) {
    }

    public function has_errors(): bool {
        return $this->errors !== [];
    }

    public function get_errors(): array {
        return $this->errors;
    }

    public function validate_input_required(string $input_name): bool {
        if (!isset($this->request_body[$input_name])) {
            $this->errors[$input_name] = "Input required - $input_name";
            return false;
        }

        return true;
    }

    public function validate_json_string(string $input_name): false|array {
        if (!$this->validate_input_required($input_name)) {
            return false;
        }

        try {
            return json_decode($this->request_body[$input_name], true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->errors[$input_name] = $e->getMessage();
            return false;
        }
    }

    public function validate_string(string $input_name): bool {
        if (!$this->validate_input_required($input_name)) {
            return false;
        }

        return is_string($this->request_body[$input_name]);
    }
}
