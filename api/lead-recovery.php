<?php
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/rate-limit.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!rateLimit('lead_draft_' . md5($ip), 20, 3600)) {
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

$file = __DIR__ . '/leads_draft_' . date('Y-m') . '.csv';
$isNew = !file_exists($file);

$fp = fopen($file, 'a');
if ($fp) {
    if ($isNew) {
        fputcsv($fp, ['date','time','name','email','message','ip_hash']);
    }

    fputcsv($fp, [
        date('Y-m-d'),
        date('H:i:s'),
        $name,
        $email,
        $message,
        hash('sha256', $ip)
    ]);

    fclose($fp);
}

echo json_encode(['status' => 'ok']);
