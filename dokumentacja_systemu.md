# 📘 Dokumentacja Techniczna - TwojaStronaWWW

Kompletny przewodnik po architekturze, bezpieczeństwie i działaniu systemu kontaktowego.

---

## 🏛 Architektura Systemu

Projekt jest lekki, oparty na **PHP (Backend)** i **Vanilla JS (Frontend)**. Nie wymaga bazy SQL – wszystkie dane są zapisywane w plikach CSV. Skupia się na bezpieczeństwie (Strict CSRF, Session Auth) i niezawodności.

### Struktura Katalogów

```
d:/Projekty/twojastronawww/
├── api/                  # Logika backendowa (PHP)
│   ├── admin.php         # Panel administratora (Logowanie + Zarządzanie)
│   ├── bootstrap.php     # Konfiguracja globalna (sesje, nagłówki, stałe)
│   ├── contact.php       # Endpoint wysyłki formularza
│   ├── csrf.php          # Ochrona CSRF (Session One-Time Token)
│   ├── delete-lead.php   # [NOWY] Usuwanie rekordów (wymaga Auth)
│   ├── export-leads.php  # Eksport danych do CSV
│   ├── get-csrf-token.php# Endpoint pobierania tokena sesyjnego
│   ├── leads-store.php   # Biblioteka zapisu/odczytu CSV (z Hashem ID)
│   ├── lead-recovery.php # Zapis wersji roboczych (draftów)
│   ├── rate-limit.php    # Ochrona przed spamem (lokalna baza plików)
│   ├── rate_limits/      # Katalog liczników (chroniony .htaccess)
│   └── sessions/         # Bezpieczny katalog sesji serwera
├── assets/
│   └── js/
│       └── contact.js    # Logika formularza (AJAX, walidacja, auto-save 60s)
├── index.html            # Strona główna
└── dokumentacja_systemu.md # Ten plik
```

---

## 🛡 Bezpieczeństwo

### 1. Panel Administratora (`api/admin.php`)
-   **Logowanie:** Formularz POST (PIN nie jest widoczny w URL).
-   **Sesja:** Oparta na `$_SESSION['auth']` z czasem życia **30 minut** (TTL). Po bezczynności następuje automatyczne wylogowanie.
-   **Usuwanie:** Wymaga potwierdzenia JS oraz poprawnego tokena CSRF. Fizycznie usuwa wiersz z pliku CSV.

### 2. Ochrona CSRF (Strict Session)
-   **One-Time Token:** Token jest ważny tylko na jedno użycie (rotacja po każdej wysyłce). Zapobiega atakom typu Replay.
-   **Przechowywanie:** Wyłącznie w sesji serwera (brak ciasteczka `csrf_token`).
-   **Origin Check:** Jeśli przeglądarka wysyła nagłówek `Origin` lub `Referer`, jest on weryfikowany z listą zaufanych domen.

### 3. Rate Limiting (Anti-Spam)
-   **Lokalizacja:** Liczniki w katalogu `api/rate_limits` (zabezpieczone przed dostępem z zewnątrz).
-   **Mechanizm:** File Locking (`flock`) zapobiega błędom przy dużym ruchu.
-   **Limity:**
    -   Wysyłka: 5 prób / 5 minut.
    -   Drafty: 20 prób / h.

---

## 💻 Backend (API)

| Plik | Funkcja | Opis |
| :--- | :--- | :--- |
| **`contact.php`** | Formularz | Walidacja, Honeypot, CSRF, wysyłka e-mail, zapis CSV. |
| **`leads-store.php`** | Baza Danych | Odczyt/Zapis CSV. Generuje unikalny **Hash ID** rekordu (SHA-256) dla funkcji usuwania. Optymalizacja odczytu (limit 200). |
| **`delete-lead.php`** | Admin | Usuwa wskazany rekord z pliku CSV na podstawie Hash ID. Wymaga zalogowania. |

---

## 📦 Dane i Logi

Dane w plikach CSV (`api/leads_*.csv`). 
Format wiersza: `Data, Czas, Imię, Email, Wiadomość, Hash IP`.

> **Backup:** Dane są trwale zapisane w plikach tekstowych. Zalecane regularne kopiowanie katalogu `api/*.csv`.

---

## 🔧 Rozwiązywanie Problemów

### Błąd "Forbidden (CSRF)"
-   Odśwież stronę (token jest jednorazowy).
-   Upewnij się, że obsługujesz pliki cookies (sesja).

### Brak dostępu do Admina
-   Sesja wygasa po 30 minutach. Zaloguj się ponownie PIN-em.
-   Jeśli zapomniałeś PIN-u, sprawdź plik `api/bootstrap.php` (`APP_PIN`).
