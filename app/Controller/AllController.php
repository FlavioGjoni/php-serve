<?php

namespace App\Controller;

class AllController {

    public function index(): void {
        $uri = $_SERVER['REQUEST_URI'] ?? null;
        $body_data_raw = file_get_contents('php://input');

        echo json_encode([
            'request_path' => $uri !== null ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : null,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'request_url' => $_SERVER['REQUEST_URI'] ?? null,
            'request_ip' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
            'headers' => getallheaders(),
            'query_params' => $_GET,
            'body_data_raw' => $body_data_raw,
        ]);
    }
}
