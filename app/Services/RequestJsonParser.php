<?php

namespace App\Services;

use JsonException;

class RequestJsonParser {

    protected array $body_data = [];

    protected string $parse_error = '';

    public function parse(): bool {
        $body_data_raw = file_get_contents('php://input');

        try {
            $this->body_data = json_decode($body_data_raw, true, flags: JSON_THROW_ON_ERROR);
            return true;
        } catch (JsonException $e) {
            $this->parse_error = $e->getMessage();
            return false;
        }
    }

    public function get_body_data(): array {
        return $this->body_data;
    }

    public function get_parse_error(): string {
        return $this->parse_error;
    }
}