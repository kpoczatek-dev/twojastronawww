<?php
require_once __DIR__ . '/bootstrap.php';

// Konfiguracja TTL (30 min)
$SESSION_TTL = 1800;

// 1. Wylogowanie
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// 2. Obsługa POST (Logowanie)
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = $_POST['pin'] ?? '';
    if ($pin === APP_PIN) {
        // Regeneracja ID sesji dla bezpieczeństwa (session fixation)
        session_regenerate_id(true);
        $_SESSION['auth'] = true;
        $_SESSION['auth_at'] = time();
        header("Location: admin.php");
        exit;
    } else {
        $error = "Nieprawidłowy PIN.";
        // Opóźnienie przeciw brute-force (prymitywne ale skuteczne)
        sleep(2);
    }
}

// 3. Sprawdzenie Sesji
if (!empty($_SESSION['auth']) && $_SESSION['auth'] === true) {
    // Sprawdź TTL
    if (time() - ($_SESSION['auth_at'] ?? 0) > $SESSION_TTL) {
        session_destroy();
        $error = "Sesja wygasła. Zaloguj się ponownie.";
        // Fallback do formularza niżej
    } else {
        // Odśwież czas (jeśli chcesz sliding expiration)
        $_SESSION['auth_at'] = time();
        
        // JESTEŚMY ZALOGOWANI - POKAŻ PANEL
        $leads  = read_leads(false);
        $drafts = read_leads(true);
        $csrf = csrf_token(); // Potrzebujemy tokena do formularza usuwania
        ?>
<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Panel administracyjny</title>
<style>
body { font-family: sans-serif; padding: 20px; background: #f9f9f9; }
h1, h2, h3 { color: #333; }
table { border-collapse: collapse; width: 100%; max-width: 1200px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
th { background: #f4f4f4; }
tr:hover { background: #f1f1f1; }
.small { color: #666; font-size: 14px; margin-bottom: 20px; }
.logout { float: right; color: red; text-decoration: none; font-weight: bold; }
.btn-del { background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; }
.btn-del:hover { background: #c82333; }

/* Tabela Fixed Layout */
table {
  table-layout: fixed;
  width: 100%;
  max-width: 1200px;
}
th:nth-child(1), td:nth-child(1) { width: 140px; } /* Data */
th:nth-child(2), td:nth-child(2) { width: 180px; } /* Imię */
th:nth-child(3), td:nth-child(3) { width: 220px; } /* Email */
th:nth-child(5), td:nth-child(5) { width: 80px; text-align: center; } /* Akcja */

/* Message Column (Auto + Ellipsis) */
th:nth-child(4), td:nth-child(4) {
  width: auto;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
</head>
<body>

<a href="?logout=1" class="logout">Wyloguj się</a>
<h1>Panel administracyjny</h1>

<p class="small">
Status: Zalogowano | Finalne leady: <?= count($leads) ?> | Drafty: <?= count($drafts) ?>
</p>

<h2>Finalne leady</h2>
<table>
<tr><th>Data</th><th>Imię</th><th>Email</th><th>Wiadomość</th><th>Akcja</th></tr>
<?php foreach ($leads as $l): $msg = (string)$l[4]; $id = lead_id($l); ?>
<tr>
    <td><?= htmlspecialchars($l[0] . ' ' . $l[1]) ?></td>
    <td><?= htmlspecialchars($l[2]) ?></td>
    <td><?= htmlspecialchars($l[3]) ?></td>
    <td><?= htmlspecialchars(mb_substr($msg, 0, 100)) ?></td>
    <td>
        <form method="post" action="delete-lead.php" onsubmit="return confirm('Czy na pewno usunąć ten wpis?');">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <button type="submit" class="btn-del">Usuń</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</table>

<h2>Drafty formularzy</h2>
<table>
<tr><th>Data</th><th>Imię</th><th>Email</th><th>Wiadomość</th><th>Akcja</th></tr>
<?php foreach ($drafts as $d): $msg = (string)$d[4]; $id = lead_id($d); ?>
<tr>
    <td><?= htmlspecialchars($d[0] . ' ' . $d[1]) ?></td>
    <td><?= htmlspecialchars($d[2]) ?></td>
    <td><?= htmlspecialchars($d[3]) ?></td>
    <td><?= htmlspecialchars(mb_substr($msg, 0, 100)) ?></td>
    <td>
        <form method="post" action="delete-lead.php" onsubmit="return confirm('Czy na pewno usunąć ten szkic?');">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <button type="submit" class="btn-del">Usuń</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</table>

</body>
</html>
        <?php
        exit; // Koniec skryptu dla zalogowanego
    }
}

// 4. Formularz Logowania (Domyślny widok)
?>
<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Logowanie</title>
<style>
body { display: flex; align-items: center; justify-content: center; height: 100vh; font-family: sans-serif; background: #f0f2f5; margin: 0; }
.login-box { background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; width: 100%; max-width: 320px; }
input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
button:hover { background: #0056b3; }
.error { color: red; margin-bottom: 10px; font-size: 14px; }
</style>
</head>
<body>
<div class="login-box">
    <h2>Dostęp Chroniony</h2>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="POST">
        <input type="password" name="pin" placeholder="Wprowadź PIN" required autofocus>
        <button type="submit">Zaloguj</button>
    </form>
</div>
</body>
</html>
