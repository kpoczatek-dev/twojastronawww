<?php
session_start();
ini_set('auto_detect_line_endings', true);

$PIN = '9f3a7c21b8e44d0f';

// logowanie PIN-em
if (isset($_GET['pin']) && $_GET['pin'] === $PIN) {
    $_SESSION['auth_pin'] = $PIN;
    header("Location: drafts.php");
    exit;
}

// sprawdzenie sesji
if (!isset($_SESSION['auth_pin']) || $_SESSION['auth_pin'] !== $PIN) {
    http_response_code(403);
    exit('Brak dostępu.');
}

// wczytanie draftów
$files = glob(__DIR__ . '/leads_draft_*.csv');
rsort($files);

$drafts = [];
foreach ($files as $file) {
    if (($h = fopen($file, 'r')) !== false) {
        fgetcsv($h); // nagłówek
        while (($row = fgetcsv($h)) !== false) {
            if (count($row) === 6) {
                $drafts[] = [
                    'date' => $row[0] . ' ' . $row[1],
                    'name' => $row[2],
                    'email' => $row[3],
                    'message' => $row[4]
                ];
            }
        }
        fclose($h);
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<title>Drafty formularza</title>
<style>
body { font-family: sans-serif; padding: 20px; }
table { border-collapse: collapse; margin-top: 20px; width: 100%; max-width: 1000px; }
th, td { border: 1px solid #ccc; padding: 8px 12px; }
th { background: #f3f3f3; }
</style>
</head>
<body>

<h1>Drafty (<?= count($drafts) ?>)</h1>

<table>
<tr>
    <th>Data</th>
    <th>Imię</th>
    <th>Email</th>
    <th>Wiadomość</th>
</tr>

<?php foreach ($drafts as $d):
    $msg = (string)($d['message'] ?? '');
?>
<tr>
    <td><?= htmlspecialchars($d['date']) ?></td>
    <td><?= htmlspecialchars($d['name']) ?></td>
    <td><?= htmlspecialchars($d['email']) ?></td>
    <td><?= htmlspecialchars(mb_substr($msg, 0, 50)) ?></td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>
