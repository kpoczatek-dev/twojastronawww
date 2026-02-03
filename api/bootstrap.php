<?php
declare(strict_types=1);

// Konfiguracja sesji (FIX: własny katalog sesji, żeby system ich nie czyścił)
$sessionDir = __DIR__ . '/sessions';
if (!file_exists($sessionDir)) {
    mkdir($sessionDir, 0777, true);
}
ini_set('session.save_path', $sessionDir);
ini_set('session.gc_probability', '1'); // Wymuś sprzątanie
ini_set('session.gc_divisor', '100');   // 1% szans na cleanup przy requeście
ini_set('session.gc_maxlifetime', '3600'); // 1h ważności sesji

session_start();

// ini_set('auto_detect_line_endings', '1'); // Deprecated in PHP 8.1+

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

// Stałe globalne
define('APP_PIN', '9f3a7c21b8e44d0f');

require_once __DIR__ . '/rate-limit.php';
require_once __DIR__ . '/leads-store.php';
require_once __DIR__ . '/csrf.php';

function get_json_request(): array {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!is_array($data)) {
        $data = $_POST;
    }
    
    // Global Honeypot check
    if (!empty($data['website_url'])) {
        // Ciche zakończenie (sukces dla bota)
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(["status" => "success"]);
        exit;
    }
    
    return $data;
}
