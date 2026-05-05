<?php

header('Content-Type: application/json');
echo json_encode([
    'bruh',
    'request_path' => $_SERVER['REQUEST_URI'] ?? null
]);
