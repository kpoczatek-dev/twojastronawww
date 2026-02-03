# Dokumentacja Projektu - TwojaStronaWWW

## 📂 Struktura Plików i Odpowiedzialność

### 1. Backend (Katalog `api/`)

Te pliki odpowiadają za "mózg" formularza i bezpieczeństwo.

| Plik | Opis | Kiedy działa? |
| :--- | :--- | :--- |
| **`contact.php`** | **Główny skrypt wysyłki.** Wysyła maila do Ciebie, autoresponder do klienta i zapisuje "twardego" leada w `leads_YYYY-MM.csv`. | Po kliknięciu "Wyślij". |
| **`lead-recovery.php`** | **Ratowanie porzuconych koszyków.** Zapisuje wpisywane dane w tle (drafty) do `leads_draft_YYYY-MM.csv`. | Gdy użytkownik pisze, ale nie wysyła. |
| **`get-csrf-token.php`** | **Endpoint CSRF.** Zwraca token w JSON dla JavaScriptu. | Przy ładowaniu strony (AJAX). |
| **`libs`** | `csrf.php`, `rate-limit.php`, `leads-store.php`. Biblioteki funkcji (nie uruchamiać bezpośrednio). | Używane wewnątrz PHP. |
| **`bootstrap.php`** | **Jądro systemu.** Startuje sesję, ładuje biblioteki, ustawia nagłówki security i PIN. | Załączany przez każdy inny plik PHP. |
| **`admin.php`** | **Panel Administracyjny.** Pozwala przeglądać zarówno finalne leady, jak i drafty. Wymaga PINu. | Ręczne wejście przez przeglądarkę. |
| **`export-leads.php`** | **Eksport danych.** Pobiera wszystkie finalne leady ze wszystkich miesięcy i łączy w jeden plik CSV. | Po kliknięciu "Eksportuj" w panelu. |

---

### 2. Frontend (Strona)

| Plik | Opis |
| :--- | :--- |
| **`index.html`** | Główna strona. Zawiera formularz HTML (bez atrybutów `required`, żeby JS mógł działać). |
| **`assets/js/contact.js`** | **Logika przeglądarki.** <br>1. Pobiera token CSRF.<br>2. Wysyła drafty co 2 sekundy (`lead-recovery`).<br>3. Waliduje formularz.<br>4. Wysyła finalne dane (`contact.php`).<br>5. Obsługuje błędy i komunikaty. |

---

### 3. Dane (Katalog `api/`) - Pliki generowane automatycznie

| Plik | Opis |
| :--- | :--- |
| `leads_2026-02.csv` | **Baza Klientów.** Tutaj lądują poprawne zgłoszenia. Jeden plik na miesiąc. |
| `leads_draft_2026-02.csv` | **Brudnopis.** Tutaj lądują nieskończone wpisy. Jeden plik na miesiąc. |
| `rate_limit.json` | Plik techniczny. Przechowuje liczniki blokad dla adresów IP. |

---

## 🔐 Dostęp do Paneli

*   **Panel Administracyjny:** `https://twojastronawww.pl/api/admin.php?pin=9f3a7c21b8e44d0f` (PIN jest usuwany z adresu po zalogowaniu).

> **Wskazówka:** Po pierwszym wejściu PIN zostaje zapamiętany w Twojej przeglądarce (sesja), więc przy kolejnych odświeżeniach nie musisz go wpisywać.
