<?php
require_once __DIR__ . '/bootstrap.php';

// Weryfikacja sesji (korzystamy z tej samej flagi co admin.php)
if (empty($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    http_response_code(403);
    exit('Brak dostępu');
}

// Opcjonalnie: Sprawdź CSRF (dla zwiększenia bezpieczeństwa operacji destrukcyjnej)
if (!csrf_check($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('CSRF Invalid');
}

$id = $_POST['id'] ?? '';
if (!$id) {
    exit('Brak ID');
}

$files = csv_files();

foreach ($files as $file) {
    // Pomijamy drafty jeśli chcemy usuwać tylko finalne, ale w sumie drafty też można usuwać.
    // Użytkownik nie sprecyzował, ale admin.php wyświetla drafty w osobnej tabeli.
    // Funkcja lead_id działa tak samo dla obu.
    // Pozwólmy usuwać wszystko.
    
    $rows = [];
    $foundAndDeleted = false;

    if (($h = fopen($file, 'r')) === false) continue;

    // Odczyt nagłówka (jeśli istnieje)
    $header = fgetcsv($h); 
    
    // Jeśli plik pusty lub tylko header, to pomiń
    if (!$header && feof($h)) {
        fclose($h);
        continue;
    }

    while (($row = fgetcsv($h)) !== false) {
        // Pomijamy puste linie
        if (!$row) continue;
        
        // Sprawdzamy ID
        if (lead_id($row) === $id) {
            $foundAndDeleted = true;
            continue; // Nie dodawaj do $rows (czyli usuń)
        }
        $rows[] = $row;
    }
    fclose($h);

    // Jeśli znaleziono i usunięto, przpisz plik
    if ($foundAndDeleted) {
        $fp = fopen($file, 'w');
        if ($header) fputcsv($fp, $header);
        foreach ($rows as $r) {
            fputcsv($fp, $r);
        }
        fclose($fp);
        break; // Zakładamy unikalność hasha (lub usuwamy pierwsze wystąpienie), break file loop
    }
}

// Powrót do admina
header('Location: admin.php');
exit;
