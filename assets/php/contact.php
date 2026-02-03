<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$toEmail = "kontakt@twojastronawww.pl";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        $data = $_POST;
    }

    // Honeypot Check
    if (!empty($data['website_url'])) {
        // Bot detected - silently fail or just exit
        echo json_encode(["status" => "success", "message" => "Wiadomość wysłana."]);
        exit;
    }

    $name = strip_tags(trim($data['name'] ?? ''));
    $email = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $message = strip_tags(trim($data['message'] ?? ''));
    $type = $data['type'] ?? 'standard'; // 'standard' or 'lead_recovery'

    if (empty($name) || empty($email) || empty($message)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Wypełnij wszystkie pola."]);
        exit;
    }

    // 1. Limit długości (anty-spam)
    if (strlen($message) > 5000) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Wiadomość jest za długa."]);
        exit;
    }

    // 2. Walidacja emaila (ostrzejsza)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Nieprawidłowy email."]);
        exit;
    }

    $subject = "Formularz Kontaktowy: $name";
    if ($type === 'lead_recovery') {
        $subject = "[SZKIC] Nieukończona wiadomość od: $name";
    }

    $emailContent = "Imię: $name\n";
    $emailContent .= "Email: $email\n\n";
    $emailContent .= "Wiadomość:\n$message\n";
    
    $headers = "From: TwojaStronaWWW <kontakt@twojastronawww.pl>\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    if (mail($toEmail, $subject, $emailContent, $headers)) {
        // Auto-reply
        $autoSubject = "Otrzymałem Twoje zapytanie";
        $autoMessage = "Dzięki za wiadomość.\nWracam z odpowiedzią zwykle w ciągu 24h.\n\nKrzysztof\nTwojaStronaWWW";
        $autoHeaders = "From: $toEmail\r\n";
        $autoHeaders .= "Reply-To: $toEmail\r\n";
        $autoHeaders .= "Content-Type: text/plain; charset=UTF-8\r\n";
        mail($email, $autoSubject, $autoMessage, $autoHeaders);

        echo json_encode(["status" => "success", "message" => "Wysłano pomyślnie."]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Błąd wysyłania."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metoda niedozwolona."]);
}
?>
