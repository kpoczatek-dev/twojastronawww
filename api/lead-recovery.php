<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/rate-limit.php";

$ip = $_SERVER['REMOTE_ADDR'];
// Limit: 20 szkiców na godzinę
if (!rateLimit('lead_' . md5($ip), 20, 3600)) {
    http_response_code(429);
    echo json_encode(["status" => "error"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    $data = [];
}

// honeypot
if (!empty($data['website_url'])) {
    echo json_encode(["status" => "ok"]);
    exit;
}

// CSRF pominięte dla Lead Recovery (sendBeacon compatibility)
// if (empty($data['csrf']) || $data['csrf'] !== ($_SESSION['csrf_token'] ?? null)) {
//     http_response_code(403);
//     echo json_encode(["status" => "error"]);
//     exit;
// }

$name = trim(strip_tags($data['name'] ?? ''));
$email = trim($data['email'] ?? '');
$message = trim(strip_tags($data['message'] ?? ''));

// Jeśli pusto, nic nie robimy
if (!$name && !$email) {
    echo json_encode(["status" => "ok"]);
    exit;
}

$entry = [
    "time" => date("c"),
    "ip" => $ip,
    "name" => $name,
    "email" => $email,
    "message" => mb_substr($message, 0, 1000)
];

$logDir = __DIR__ . "/../storage";
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

file_put_contents(
    "$logDir/leads.log",
    json_encode($entry) . PHP_EOL,
    FILE_APPEND
);


file_put_contents(
    "$logDir/leads_" . date('Y-m-d') . ".log", // Rotacja dzienna
    json_encode($entry) . PHP_EOL,
    FILE_APPEND
);

echo json_encode(["status" => "ok"]);
