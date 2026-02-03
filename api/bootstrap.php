<?php
declare(strict_types=1);

session_start();

ini_set('auto_detect_line_endings', '1');

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

// Stałe globalne
define('APP_PIN', '9f3a7c21b8e44d0f');

require_once __DIR__ . '/rate-limit.php';
require_once __DIR__ . '/leads-store.php';
require_once __DIR__ . '/csrf.php';
