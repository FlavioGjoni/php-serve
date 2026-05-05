<?php

$uri = $_SERVER['REQUEST_URI'] ?? null;

header('Content-Type: application/json');
echo json_encode([
    'request_path' => $uri !== null
        ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
        : null,
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
    'request_url' => $_SERVER['REQUEST_URI'] ?? null,
]);

