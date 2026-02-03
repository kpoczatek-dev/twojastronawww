<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/rate-limit.php";

$ip = $_SERVER['REMOTE_ADDR'];
// Limit: 20 requestów na godzinę dla pobrania tokena (zapobiega spamowaniu sesjami)
if (!rateLimit('csrf_' . md5($ip), 20, 3600)) {
    http_response_code(429);
    echo json_encode(["status" => "error", "message" => "Rate limit exceeded"]);
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo json_encode([
    "token" => $_SESSION['csrf_token']
]);
