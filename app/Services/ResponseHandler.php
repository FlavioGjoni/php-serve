<?php

namespace App\Services;

class ResponseHandler {

    public static function json_response($data = [], $http_code = 200): void {
        header('Content-type: application/json');
        http_response_code($http_code);
        echo json_encode($data);
    }
}
