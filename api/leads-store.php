<?php

function csv_files(): array {
    return glob(__DIR__ . '/leads_*.csv');
}

function is_draft_file(string $file): bool {
    return strpos(basename($file), '_draft_') !== false;
}

function read_leads(bool $draft = false): array {
    $rows = [];
    $files = csv_files();
    rsort($files);

    foreach ($files as $file) {
        if ($draft !== is_draft_file($file)) {
            continue;
        }

        if (($h = fopen($file, 'r')) === false) {
            continue;
        }

        fgetcsv($h); // header
        while (($row = fgetcsv($h)) !== false) {
            if (count($row) === 6) {
                $rows[] = $row;
            }
        }
        fclose($h);
    }
    return $rows;
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
