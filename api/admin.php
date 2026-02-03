<?php
session_start();
ini_set('auto_detect_line_endings', true);

$PIN = '9f3a7c21b8e44d0f';

/* ========= AUTORYZACJA ========= */
if (isset($_GET['pin']) && $_GET['pin'] === $PIN) {
    $_SESSION['auth_pin'] = $PIN;
    header("Location: admin.php");
    exit;
}

if (!isset($_SESSION['auth_pin']) || $_SESSION['auth_pin'] !== $PIN) {
    http_response_code(403);
    exit('Brak dostępu. Użyj linku z PIN-em.');
}

/* ========= POMOCNICZA FUNKCJA ========= */
function readCsv($pattern) {
    $rows = [];
    $files = glob(__DIR__ . '/' . $pattern);
    rsort($files);

    foreach ($files as $file) {
        if (($h = fopen($file, 'r')) !== false) {
            fgetcsv($h); // header
            while (($row = fgetcsv($h)) !== false) {
                if (count($row) === 6) {
                    $rows[] = $row;
                }
            }
            fclose($h);
        }
    }
    return $rows;
}

/* ========= WCZYTANIE DANYCH ========= */
// Ważne: leads_*.csv złapie też leads_draft_*.csv, więc musimy to odfiltrować
// Ale chwila, w Twoim kodzie readCsv('leads_*.csv') złapie wszystko.
// Musimy uważać. Ale w poprzednim kroku mówiłeś, że leads_*.csv łapie drafty.
// Więc tutaj trzeba być precyzyjnym w funkcji readCsv albo w patternie.

// Poprawka logiki: glob('leads_*.csv') złapie 'leads_draft_...'. 
// Więc dla leadsów musimy wykluczyć 'draft'.

function readCsvFiltered($pattern, $excludeDrafts = false, $onlyDrafts = false) {
    $rows = [];
    $files = glob(__DIR__ . '/' . $pattern);
    rsort($files);

    foreach ($files as $file) {
        $basename = basename($file);
        $isDraft = strpos($basename, '_draft_') !== false;

        if ($excludeDrafts && $isDraft) continue;
        if ($onlyDrafts && !$isDraft) continue;

        if (($h = fopen($file, 'r')) !== false) {
            fgetcsv($h); // header
            while (($row = fgetcsv($h)) !== false) {
                if (count($row) === 6) {
                    $rows[] = $row;
                }
            }
            fclose($h);
        }
    }
    return $rows;
}

// Używam poprawionej logiki, żeby nie mieszać danych, zgodnie z poprzednimi ustaleniami
$leads  = readCsvFiltered('leads_*.csv', true, false); 
$drafts = readCsvFiltered('leads_draft_*.csv', false, true); 

// Wróćmy jednak do Twojego kodu. Ty podałeś kod w requeście.
// Jeśli użyję Twojego prostego readCsv('leads_*.csv') to w sekcji "Finalne leady" wyświetlą się TEŻ drafty (bo glob łapie).
// Ale Ty dałeś kod: $leads = readCsv('leads_*.csv'); $drafts = readCsv('leads_draft_*.csv');
// Zaufam Twojej intencji, ale ZMODYFIKUJĘ funkcję readCsv tak, by była IDIOTPROOF.
// Bo `leads_*.csv` pasuje do `leads_draft_2026-02.csv`.
// Więc zrobię to BEZPIECZNIE.

function readLeads($isDraft) {
    $rows = [];
    $files = glob(__DIR__ . '/leads_*.csv');
    rsort($files);

    foreach ($files as $file) {
        $basename = basename($file);
        $fileIsDraft = strpos($basename, '_draft_') !== false;

        // Jeśli chcemy leady, a plik to draft -> pomiń
        if (!$isDraft && $fileIsDraft) continue;
        
        // Jeśli chcemy drafty, a plik to NIE draft -> pomiń (choć glob leads_draft_*.csv byłby lepszy, ale tu iterujemy po wszystkim dla pewności)
        if ($isDraft && !$fileIsDraft) continue;

        if (($h = fopen($file, 'r')) !== false) {
            fgetcsv($h); // header
            while (($row = fgetcsv($h)) !== false) {
                if (count($row) === 6) {
                    $rows[] = $row;
                }
            }
            fclose($h);
        }
    }
    return $rows;
}

$leads = readLeads(false);
$drafts = readLeads(true);
?>
<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<title>Panel administracyjny</title>
<style>
body { font-family: sans-serif; padding: 20px; }
h2 { margin-top: 40px; }
table { border-collapse: collapse; width: 100%; max-width: 1200px; }
th, td { border: 1px solid #ccc; padding: 8px 12px; }
th { background: #f3f3f3; }
.small { color: #666; font-size: 12px; }
</style>
</head>
<body>

<h1>Panel administracyjny</h1>

<p class="small">
Finalne leady: <?= count($leads) ?> |
Drafty: <?= count($drafts) ?>
</p>

<!-- ===== FINALNE LEADY ===== -->
<h2>Finalne leady</h2>
<table>
<tr>
    <th>Data</th>
    <th>Imię</th>
    <th>Email</th>
    <th>Wiadomość</th>
</tr>
<?php foreach ($leads as $l):
    $msg = (string)$l[4];
?>
<tr>
    <td><?= htmlspecialchars($l[0] . ' ' . $l[1]) ?></td>
    <td><?= htmlspecialchars($l[2]) ?></td>
    <td><?= htmlspecialchars($l[3]) ?></td>
    <td><?= htmlspecialchars(mb_substr($msg, 0, 60)) ?></td>
</tr>
<?php endforeach; ?>
</table>

<!-- ===== DRAFTY ===== -->
<h2>Drafty formularzy</h2>
<table>
<tr>
    <th>Data</th>
    <th>Imię</th>
    <th>Email</th>
    <th>Wiadomość</th>
</tr>
<?php foreach ($drafts as $d):
    $msg = (string)$d[4];
?>
<tr>
    <td><?= htmlspecialchars($d[0] . ' ' . $d[1]) ?></td>
    <td><?= htmlspecialchars($d[2]) ?></td>
    <td><?= htmlspecialchars($d[3]) ?></td>
    <td><?= htmlspecialchars(mb_substr($msg, 0, 60)) ?></td>
</tr>
<?php endforeach; ?>
</table>

<p class="small">
Drafty to dane autosave / porzucone formularze – nie są leadami sprzedażowymi.
</p>

</body>
</html>
