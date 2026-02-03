<?php
require_once __DIR__ . '/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if (!rate_limit('lead_draft_' . md5($ip), 20, 3600)) {
    echo json_encode(['status' => 'ok']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = $_POST;
}

if (!empty($data['website_url'])) {
    echo json_encode(['status' => 'ok']);
    exit;
}

$name = trim(strip_tags($data['name'] ?? ''));
$email = trim($data['email'] ?? '');
$message = trim(strip_tags($data['message'] ?? ''));

// zapisujemy tylko jeśli COŚ istnieje
if (!$name && !$email && !$message) {
    echo json_encode(['status' => 'ok']);
    exit;
}

save_lead([
    date('Y-m-d'),
    date('H:i:s'),
    $name,
    $email,
    $message,
    hash('sha256', $ip)
], true);

echo json_encode(['status' => 'ok']);
