<?php

function csv_files(): array {
    return glob(__DIR__ . '/leads_*.csv');
}

function is_draft_file(string $file): bool {
    return strpos(basename($file), '_draft_') !== false;
}

function read_leads(bool $draft = false, int $limit = 200): array {
    $rows = [];
    $files = csv_files();
    rsort($files); // Od najnowszych

    foreach ($files as $file) {
        if ($draft !== is_draft_file($file)) {
            continue;
        }

        if (($h = fopen($file, 'r')) === false) {
            continue;
        }
        
        // Zbieramy wiersze z jednego pliku (odwracamy kolejność w pliku, żeby mieć najnowsze na górze)
        $fileRows = [];
        fgetcsv($h); // header (pomiń)
        
        while (($row = fgetcsv($h)) !== false) {
            if (count($row) === 6) {
                // Dodajemy na początek tablicy pliku, bo CSV jest chronologicznie (stare -> nowe).
                // Ale my czytamy pliki od najnowszych. 
                // W pliku nowe wpisy są na dole.
                // Więc musimy odwrócić zawartość pliku, żeby najnowszy wpis był pierwszy.
                array_unshift($fileRows, $row);
            }
        }
        fclose($h);

        // Dodajemy do głównej listy
        foreach ($fileRows as $r) {
            $rows[] = $r;
            if (count($rows) >= $limit) {
                break 2; // Przerywamy pętlę foreach i while/if
            }
        }
        
        if (count($rows) >= $limit) {
            break; 
        }
    }
    return $rows;
}

function lead_id(array $row): string {
    // Deterministyczny Hash z zawartości wiersza
    return hash('sha256', implode('|', $row));
}

function save_lead(array $data, bool $draft = false): void {
    if (count($data) !== 6) {
        return;
    }
    $suffix = $draft ? '_draft' : '';
    // Zapis w katalogu API (__DIR__)
    $file = __DIR__ . '/leads' . $suffix . '_' . date('Y-m') . '.csv';
    $isNew = !file_exists($file);

    $fp = fopen($file, 'a');
    if (!$fp) return;

    if ($isNew) {
        fputcsv($fp, ['date','time','name','email','message','ip_hash']);
    }

    fputcsv($fp, $data);
    fclose($fp);
}
