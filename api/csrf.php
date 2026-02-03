<?php
function csrf_token(): string {
    // Sprawdzamy, czy w sesji jest już token
    if (empty($_SESSION['csrf_token'])) {
        // Generujemy nowy silny token
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
    }
    return $_SESSION['csrf_token'];
}

function csrf_check(?string $token): bool {
    // Wymagamy aktywnej sesji i tokena w niej
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Porównujemy token z requestu (JSON) z tokenem w sesji (Server)
    if (!$token) {
        return false;
    }
    
    $result = hash_equals($_SESSION['csrf_token'], $token);
    
    // One-Time Token: Jeśli weryfikacja się powiodła, usuwamy token (rotacja)
    if ($result) {
        unset($_SESSION['csrf_token']);
    }
    
    return $result;
}
