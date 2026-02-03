<?php
function csrf_token(): string {
    // Sprawdzamy, czy klient ma już ciastko
    if (empty($_COOKIE['csrf_token'])) {
        $token = bin2hex(random_bytes(32));
        
        // Dynamiczne określenie domeny dla ciasteczka
        // Jeśli jesteśmy na localhost, nie ustawiamy domeny (domyślnie host)
        // Jeśli na produkcji, ustawiamy .twojastronawww.pl (lub pobieramy z SERVER_NAME)
        
        $domain = $_SERVER['HTTP_HOST'] ?? '';
        // Usuwamy port, jeśli jest (np. localhost:8080)
        $domain = explode(':', $domain)[0];
        
        // Opcjonalnie: hardcode dla produkcji jeśli $domain to twojastronawww.pl
        $cookieDomain = ''; // Domyślnie puste = bieżący host
        if (strpos($domain, 'twojastronawww.pl') !== false) {
             $cookieDomain = '.twojastronawww.pl';
        }

        // Ustawiamy ciastko (ważne 1h, Double-Submit)
        setcookie('csrf_token', $token, [
            'expires'  => time() + 3600,
            'path'     => '/',
            'domain'   => $cookieDomain, 
            'secure'   => true, // Wymaga HTTPS (na localhost może wymagać wyjątku lub https)
            'httponly' => true, // JS nie widzi ciastka, widzi tylko JSON
            'samesite' => 'Lax', // Lax pozwala na linki z zewnątrz, ale POST wymaga tokena
        ]);
        return $token;
    }
    return $_COOKIE['csrf_token'];
}

function csrf_check(?string $token): bool {
    // Porównujemy token z requestu (JSON) z tokenem w ciastku (Browser)
    if (!$token || !isset($_COOKIE['csrf_token'])) {
        return false;
    }
    return hash_equals($_COOKIE['csrf_token'], $token);
}
