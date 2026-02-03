<?php
require_once __DIR__ . '/bootstrap.php';

/* ========= AUTORYZACJA ========= */
if (isset($_GET['pin']) && $_GET['pin'] === APP_PIN) {
    $_SESSION['auth_pin'] = APP_PIN;
    unset($_GET['pin']); // Cleaning footprintll
    header("Location: admin.php");
    exit;
}

if (!isset($_SESSION['auth_pin']) || $_SESSION['auth_pin'] !== APP_PIN) {
    http_response_code(403);
    exit('Brak dostępu. Użyj linku z PIN-em.');
}

/* ========= WCZYTANIE DANYCH ========= */
$leads  = read_leads(false);
$drafts = read_leads(true);
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
