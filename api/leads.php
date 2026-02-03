<?php
session_start();
// ====== MINIMALNA OCHRONA (SESJA) ======
$PIN = '9f3a7c21b8e44d0f'; // Bezpieczny PIN

// 1. Logowanie PIN-em z URL
if (isset($_GET['pin']) && $_GET['pin'] === $PIN) {
    $_SESSION['auth_pin'] = $PIN;
    header("Location: leads.php"); // Czysty URL
    exit;
}

// 2. Sprawdzenie sesji
if (!isset($_SESSION['auth_pin']) || $_SESSION['auth_pin'] !== $PIN) {
    http_response_code(403);
    exit('Brak dostępu. Użyj linku z PIN-em.');
}

// ====== WCZYTANIE DANYCH (CSV) ======
$files = glob(__DIR__ . '/leads_*.csv');
rsort($files); // Najnowsze pliki pierwsze

$leads = [];
foreach ($files as $file) {
    if (($handle = fopen($file, "r")) !== FALSE) {
        // Pomijamy nagłówek
        $header = fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== FALSE) {
            // Format CSV: date, time, name, email, message, ip_hash
            // Chcemy wyświetlić np. Email, Date, Name
            if (count($data) >= 6) {
                $leads[] = [
                    'date' => $data[0] . ' ' . $data[1],
                    'name' => $data[2],
                    'email' => $data[3],
                    'message' => $data[4]
                ];
            }
        }
        fclose($handle);
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<title>Leady</title>
<style>
body { font-family: sans-serif; padding: 20px; }
table { border-collapse: collapse; margin-top: 20px; width: 100%; max-width: 1000px; }
th, td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; }
th { background: #f3f3f3; }
.btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
.btn:hover { background: #0056b3; }
</style>
</head>
<body>

<h1>Leady (<?= count($leads) ?>)</h1>

<p>
<a href="export-leads.php" class="btn">Eksportuj wszystkie do CSV</a>
</p>

<table>
<tr>
    <th>Data</th>
    <th>Imię</th>
    <th>Email</th>
    <th>Wiadomość</th>
</tr>

<?php foreach ($leads as $lead): 
    $msg = (string)($lead['message'] ?? '');
?>
<tr>
    <td><?= htmlspecialchars($lead['date']) ?></td>
    <td><?= htmlspecialchars($lead['name']) ?></td>
    <td><?= htmlspecialchars($lead['email']) ?></td>
    <td><?= htmlspecialchars(mb_substr($msg, 0, 50)) . (mb_strlen($msg) > 50 ? '...' : '') ?></td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>
