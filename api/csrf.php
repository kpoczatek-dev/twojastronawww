<?php
function csrf_token(): string {
    // Sprawdzamy, czy klient ma już ciastko
    if (empty($_COOKIE['csrf_token'])) {
        $token = bin2hex(random_bytes(32));
        // Ustawiamy ciastko (ważne 1h, Double-Submit)
        // httponly=false, ale w tym modelu backend i tak zwraca token w JSON
        // Możemy dać true, bo i tak zwracamy token w body response.
        // Ale user sugerował httponly=false w przykładzie.
        // Jednak bezpieczniej: httponly=true + zwracamy token w get-csrf-token.php
        // Wtedy JS nie czyta ciastka, tylko JSON.
        // Ale w Double Submit Cookie klasycznym JS czyta ciastko.
        // W naszym flow: JS pobiera JSON z tokenem.
        // Zróbmy tak by było zgodne z istniejącym JS flow.
        
        // Zgodnie z sugestią użytkownika (Option 1):
        setcookie('csrf_token', $token, [
            'expires' => time() + 3600,
            'path' => '/',
            'samesite' => 'Lax',
            'secure' => true,
            'httponly' => true
        ]);
        return $token;
    }
    return $_COOKIE['csrf_token'];
}

function csrf_check(?string $token): bool {
    // Porównujemy token z requestu (JSON) z tokenem w ciastku (Browser)
    return $token && isset($_COOKIE['csrf_token'])
        && hash_equals($_COOKIE['csrf_token'], $token);
}
