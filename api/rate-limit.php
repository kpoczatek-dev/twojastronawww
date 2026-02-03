<?php

function rate_limit(string $key, int $limit, int $seconds): bool {
    // 1. Katalog na limity (lokalny, nie systemowy /tmp)
    $dir = __DIR__ . '/rate_limits';
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0777, true)) {
            // Fallback do temp jeśli nie uda się stworzyć katalogu
            $dir = sys_get_temp_dir();
        } else {
             // Zabezpieczenie katalogu przed podglądaniem
             file_put_contents($dir . '/.htaccess', "Deny from all");
             file_put_contents($dir . '/index.html', ""); 
        }
    }

    $file = $dir . '/rate_' . preg_replace('/[^a-zA-Z0-9_]/', '', $key) . '.json';
    
    // 2. Garbage Collector (1% szans)
    if (rand(1, 100) === 1) {
        $files = glob($dir . '/rate_*.json');
        foreach ($files as $f) {
            if (time() - filemtime($f) > 3600) { // Starsze niż 1h
                @unlink($f);
            }
        }
    }

    // 3. Otwarcie z LOCKIEM
    $fp = fopen($file, 'c+'); // c+ = read/write, nie czyści pliku przy otwarciu
    if (!$fp) return true; // Fail-open (jeśli błąd pliku, nie blokuj usera)

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return true; // Fail-open
    }

    $current = [];
    $content = stream_get_contents($fp);
    if ($content) {
        $current = json_decode($content, true);
    }

    $now = time();
    
    // Inicjalizacja lub reset starych danych
    if (!$current || !isset($current['start_time']) || ($now - $current['start_time'] > $seconds)) {
        $current = [
            'start_time' => $now,
            'count' => 0
        ];
    }

    // Sprawdzenie limitu
    if ($current['count'] >= $limit) {
        flock($fp, LOCK_UN);
        fclose($fp);
        return false;
    }

    // Inkrementacja
    $current['count']++;

    // Zapis
    ftruncate($fp, 0); // Czyścimy plik
    rewind($fp);
    fwrite($fp, json_encode($current));

    flock($fp, LOCK_UN);
    fclose($fp);

    return true;
}
