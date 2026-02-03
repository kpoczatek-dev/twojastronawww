<?php
require_once __DIR__ . '/bootstrap.php';
header("Content-Type: application/json; charset=UTF-8");

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
// Limit: 5 prób wysyłki na 5 minut
// Limit: 5 prób wysyłki na 5 minut
if (!rate_limit('contact_' . md5($ip), 5, 300)) {
    http_response_code(429);
    echo json_encode(["status" => "error", "message" => "Za dużo prób."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    $data = [];
}

// honeypot
if (!empty($data['website_url'])) {
    echo json_encode(["status" => "success"]);
    exit;
}

// Strict Origin / Referer check
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';

// Musimy mieć pewność skąd pochodzi żądanie
if (!$origin && !$referer) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Forbidden (Missing Origin/Referer)"]);
    exit;
}

// Dynamiczne sprawdzanie domeny (Localhost + Produkcja)
$allowedDomains = ['twojastronawww.pl', 'localhost', '127.0.0.1'];
$isAllowed = false;

// Sprawdzamy Origin (jeśli jest)
if ($origin) {
    foreach ($allowedDomains as $domain) {
        if (strpos($origin, $domain) !== false) {
            $isAllowed = true;
            break;
        }
    }
}
// Jeśli brak Origin, sprawdzamy Referer
else if ($referer) {
    foreach ($allowedDomains as $domain) {
        if (strpos($referer, $domain) !== false) {
            $isAllowed = true;
            break;
        }
    }
}

if (!$isAllowed) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Forbidden Origin"]);
    exit;
}

// CSRF Check
if (!csrf_check($data['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Błąd zabezpieczeń (CSRF). Odśwież stronę."]);
    exit;
}

$name = trim(strip_tags($data['name'] ?? ''));
$email = trim($data['email'] ?? '');
$message = trim(strip_tags($data['message'] ?? ''));

if (!$name || !$email || !$message) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Uzupełnij wszystkie pola."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Nieprawidłowy email."]);
    exit;
}

$to = "kontakt@twojastronawww.pl";
$subject = "Formularz kontaktowy: $name";

$body = "Imię: $name\nEmail: $email\n\n$message";

$headers  = "From: TwojaStronaWWW <$to>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

if (!mail($to, $subject, $body, $headers)) {
    http_response_code(500);
    echo json_encode(["status" => "error"]);
    exit;
}

// autoresponder
mail(
    $email,
    "Otrzymałem Twoją wiadomość",
    "Dzięki za kontakt.\nOdpowiem w ciągu 24h.\n\nKrzysztof",
    "From: $to\r\nContent-Type: text/plain; charset=UTF-8\r\n"
);

// ====== ZAPIS FINALNEGO LEADA ======
save_lead([
    date('Y-m-d'),
    date('H:i:s'),
    $name,
    $email,
    $message,
    hash('sha256', $ip)
], false);

echo json_encode(["status" => "success", "message" => "Wysłano."]);
