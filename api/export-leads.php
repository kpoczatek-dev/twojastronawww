session_start();
$PIN = '9f3a7c21b8e44d0f';

// Dostęp z sesji LUB bezpośrednio z PIN (dla API)
$is_authorized = (isset($_SESSION['auth_pin']) && $_SESSION['auth_pin'] === $PIN) ||
                 (isset($_GET['pin']) && $_GET['pin'] === $PIN);

if (!$is_authorized) {
    http_response_code(403);
    exit('Brak dostępu');
}

$files = glob(__DIR__ . '/leads_*.csv');
// Sortujemy, żeby mieć chronologię (lub odwrotnie)
sort($files); 

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="all_leads_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
// Wspólny nagłówek
fputcsv($out, ['date', 'time', 'name', 'email', 'message', 'ip_hash']);

foreach ($files as $file) {
    // Ignorujemy pliki draftów
    if (strpos(basename($file), '_draft_') !== false) {
        continue;
    }

    if (($handle = fopen($file, "r")) !== FALSE) {
        $header = fgetcsv($handle); // Pomijamy nagłówek z pojedynczego pliku
        while (($data = fgetcsv($handle)) !== FALSE) {
            fputcsv($out, $data);
        }
        fclose($handle);
    }
}

fclose($out);
