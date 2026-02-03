<?php
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    error_log('[DEBUG] CSRF TOKEN (Session ' . session_id() . '): ' . $_SESSION['csrf_token']);
    return $_SESSION['csrf_token'];
}

function csrf_check(?string $token): bool {
    return $token && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
