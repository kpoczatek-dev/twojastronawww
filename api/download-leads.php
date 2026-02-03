<?php
$SECRET_TOKEN = 'recovery'; // ← zmień na coś swojego

if (!isset($_GET['token']) || $_GET['token'] !== $SECRET_TOKEN) {
    http_response_code(403);
    exit('Brak dostępu');
}

$logDir = __DIR__ . '/../storage';
$files = glob($logDir . '/leads_*.csv');

if (!$files) {
    exit('Brak danych');
}

// bierzemy najnowszy plik
rsort($files);
$file = $files[0];

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($file));

readfile($file);
exit;
