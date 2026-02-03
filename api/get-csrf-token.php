<?php
require_once __DIR__ . '/bootstrap.php';
header("Content-Type: application/json; charset=UTF-8");

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
// Limit: 20 requestów na godzinę (pobranie tokena)
// Limit: 20 requestów na godzinę (pobranie tokena)
if (!rate_limit('csrf_' . md5($ip), 20, 3600)) {
    http_response_code(429);
    echo json_encode(["status" => "error", "message" => "Rate limit exceeded"]);
    exit;
}

echo json_encode([
    "token" => csrf_token()
]);
