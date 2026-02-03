# 📘 Dokumentacja Techniczna - TwojaStronaWWW

Kompletny przewodnik po architekturze, bezpieczeństwie i działaniu systemu kontaktowego.

---

## 🏛 Architektura Systemu

Projekt jest lekki, oparty na **PHP (Backend)** i **Vanilla JS (Frontend)**. Nie wymaga bazy SQL – wszystkie dane są zapisywane w plikach CSV. Skupia się na bezpieczeństwie (CSRF, Rate Limiting) i niezawodności (Lead Recovery).

### Struktura Katalogów

```
d:/Projekty/twojastronawww/
├── api/                  # Logika backendowa (PHP)
│   ├── admin.php         # Panel administratora (wymaga PIN)
│   ├── bootstrap.php     # Konfiguracja globalna (sesje, nagłówki)
│   ├── contact.php       # Endpoint wysyłki formularza
│   ├── csrf.php          # Ochrona przed Cross-Site Request Forgery
│   ├── error_log         # Logi błędów PHP
│   ├── export-leads.php  # Eksport danych do CSV
│   ├── get-csrf-token.php# Endpoint pobierania tokena dla JS
│   ├── leads-store.php   # Biblioteka zapisu CSV
│   ├── lead-recovery.php # Zapis wersji roboczych (draftów)
│   ├── rate-limit.php    # Ochrona przed spamem/brute-force
│   └── sessions/         # Bezpieczny katalog sesji serwera
├── assets/
│   └── js/
│       └── contact.js    # Logika formularza (AJAX, walidacja, auto-save)
├── index.html            # Strona główna
├── jak-pracuje.html      # Podstrona informacyjna
└── dokumentacja_systemu.md # Ten plik
```

---

## 🛡 Bezpieczeństwo i Funkcje

### 1. Ochrona CSRF (Cross-Site Request Forgery)
System używa modelu **"Double Submit Cookie"** dostosowanego do nowoczesnych przeglądarek.
-   **Działanie:** Przy wejściu na stronę, JS pobiera unikalny token z `api/get-csrf-token.php`.
-   **Weryfikacja:** Przy wysyłce formularza, token jest wysyłany w nagłówku/body JSON. Backend sprawdza zgodność tokena z ciasteczkiem `csrf_token`.
-   **Smart Domain:** System automatycznie wykrywa czy działa na `localhost` czy na `twojastronawww.pl` i odpowiednio ustawia flagi ciasteczek (`Secure`, `HttpOnly`, `SameSite=Lax`).

### 2. Rate Limiting (Ochrona przed Spamem)
Każdy endpoint jest chroniony licznikiem opartym na IP.
-   **Pobranie tokena:** Max 20/h.
-   **Wysyłka wiadomości:** Max 5/5min.
-   **Drafty (pisanie):** Max 20/h.
> **Reset:** Limity są przechowywane w katalogu tymczasowym systemu (`/tmp` lub `AppData/Local/Temp`).

### 3. Lead Recovery (Odzyskiwanie Koszyków)
Kiedy użytkownik zaczyna pisać, ale nie wysyła wiadomości:
-   Skrypt `contact.js` co 15 sekund (oraz przy zamknięciu karty) wysyła treść do `api/lead-recovery.php`.
-   Dane trafiają do pliku `api/leads_draft_YYYY-MM.csv`.
-   Dzięki temu możesz odzyskać potencjalnego klienta, który zrezygnował w ostatniej chwili.

---

## 💻 Backend (API)

| Plik | Rola | Opis |
| :--- | :--- | :--- |
| **`contact.php`** | Core | Waliduje dane, sprawdza CSRF/Origin, wysyła e-mail i zapisuje leada. Odpowiada JSON-em. |
| **`bootstrap.php`** | Config | Ładowany przez każdy plik. Konfiguruje sesje, nagłówki bezpieczeństwa (`X-Frame-Options` itp.) i stałe. |
| **`leads-store.php`** | Data | Obsługuje odczyt i zapis do plików CSV. Dba o blokowanie plików (race conditions). |

---

## 📦 Dane i Logi

Wszystkie dane są w katalogu `api/`:

1.  **Leady (Sukces):** `leads_2026-02.csv`
    -   Zawiera: Data, Czas, Imię, Email, Wiadomość, Hash IP.
2.  **Drafty (Robocze):** `leads_draft_2026-02.csv`
    -   Zawiera te same pola, ale dla niedokończonych wiadomości.

> **Backup:** Pliki CSV warto regularnie kopiować (np. przez FTP). Panel admina posiada funkcję Eksportu.

---

## 🔧 Rozwiązywanie Problemów

### Błąd 403 (Forbidden) przy wysyłce
-   **Przyczyna:** Błędny token CSRF lub wygasła sesja.
-   **Rozwiązanie:** Odśwież stronę. JS automatycznie spróbuje pobrać nowy token. Sprawdź czy Twoja przeglądarka nie blokuje ciasteczek.

### Błąd 429 (Too Many Requests)
-   **Przyczyna:** Zbyt częste klikanie "Wyślij" lub odświeżanie.
-   **Rozwiązanie:** Odczekaj 5-60 minut. Na serwerze można wyczyścić pliki `rate_*.json` w katalogu temp.

### "Błąd Serwera" (500)
-   **Przyczyna:** Często problem z funkcją `mail()` na localhost (brak serwera SMTP).
-   **Rozwiązanie:** Na produkcji powinno działać. Na localhost sprawdź logi PHP (`api/error_log`).

---

## 🔐 Panel Administracyjny
Dostęp do podglądu leadów:
`https://twojastronawww.pl/api/admin.php?pin=9f3a7c21b8e44d0f`

> **Ważne:** PIN jest jednorazowy w sesji (po wejściu system go pamięta). Nie udostępniaj go nikomu.
