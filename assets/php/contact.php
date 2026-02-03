<?php
header("Content-Type: application/json; charset=UTF-8");

// 🔐 OPCJONALNIE: ogranicz domenę
$allowedOrigins = ['https://twojastronawww.pl'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

$toEmail = "kontakt@twojastronawww.pl";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metoda niedozwolona."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true) ?? $_POST;

// Honeypot
if (!empty($data['website_url'])) {
    echo json_encode(["status" => "success"]);
    exit;
}

$type = $data['type'] ?? 'standard';

$name = strip_tags(trim($data['name'] ?? ''));
$email = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$message = strip_tags(trim($data['message'] ?? ''));

// WALIDACJA
if ($type === 'standard') {
    if (!$name || !$email || !$message) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Wypełnij wszystkie pola."]);
        exit;
    }
}

if ($type === 'lead_recovery') {
    if (!$name && !$email) {
        echo json_encode(["status" => "success"]);
        exit;
    }
}

// Email poprawny?
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Nieprawidłowy email."]);
    exit;
}

if (strlen($message) > 5000) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Wiadomość jest za długa."]);
    exit;
}

// MAIL – tylko dla standard
if ($type === 'standard') {
    $subject = "Formularz kontaktowy: $name";
    $content  = "Imię: $name\n";
    $content .= "Email: $email\n\n";
    $content .= "Wiadomość:\n$message\n";

    $headers  = "From: TwojaStronaWWW <{$toEmail}>\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    if (!mail($toEmail, $subject, $content, $headers)) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Błąd wysyłania."]);
        exit;
    }

    // Autoresponder
    $autoSubject = "Otrzymałem Twoje zapytanie";
    $autoMessage = "Dzięki za wiadomość.\nOdpowiem w ciągu 24h.\n\nKrzysztof";
    mail($email, $autoSubject, $autoMessage, "From: $toEmail\r\nContent-Type: text/plain; charset=UTF-8\r\n");
}

// Lead recovery → bez maila, bez odpowiedzi
echo json_encode(["status" => "success", "message" => "Wysłano."]);
