// Contact Form Logic
const API_BASE = '/api/';
let CSRF_TOKEN = '';

let csrfPromise = null;
function loadCsrfToken() {
    if (CSRF_TOKEN) return Promise.resolve(CSRF_TOKEN);
    if (csrfPromise) return csrfPromise;

    csrfPromise = fetch(API_BASE + 'get-csrf-token.php', {
        credentials: 'same-origin',
        cache: 'no-store'
    })
        .then(r => r.json())
        .then(d => {
            CSRF_TOKEN = d.token || '';
            console.log('CSRF Token loaded');
            return CSRF_TOKEN;
        })
        .catch(err => {
            console.error('CSRF Error:', err);
            CSRF_TOKEN = '';
            return '';
        })
        .finally(() => {
            csrfPromise = null;
        });

    return csrfPromise;
}

// 1. loadCsrfToken usuwamy ze startu (lazy loading w submit)

function initContactForm() {
    const form = document.getElementById('contactForm');
    if (!form) return;

    const errorDiv = document.getElementById('formErrors');
    const successMsg = document.getElementById('formSuccess');
    
    // Autosave / Lead Recovery Variables
    let autosaveTimer;
    const AUTOSAVE_DELAY = 15 * 60 * 1000; // 15 minutes

    const inputs = form.querySelectorAll('input, textarea');

    // Helper to clear error
    function clearError() {
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';
    }

    // Helper to show error
    function showError(msg) {
        errorDiv.style.display = 'block';
        errorDiv.textContent = msg;
    }

    // Lead Recovery: Send Draft
    function sendDraft(isUnload = false) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        // data.csrf = CSRF_TOKEN; // Drafty nie wymagają CSRF (i tak nie jest sprawdzany)
        
        // Only send if there's at least an email or phone/name so we know who it is
        if (!data.email && !data.name) return;

        // Nie wysyłamy type: 'lead_recovery', bo endpoint jest dedykowany

        const payload = JSON.stringify(data);

        // 🔥 KLUCZOWE: przy zamknięciu karty
        if (isUnload && navigator.sendBeacon) {
            const params = new URLSearchParams(data);
            navigator.sendBeacon(
                API_BASE + 'lead-recovery.php',
                params
            );
            return;
        }

        console.log('Sending draft (Lead Recovery)...');
        // UPDATE: fetch from api/lead-recovery.php
        fetch(API_BASE + 'lead-recovery.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: payload
        }).then(res => res.json())
          .then(res => console.log('Draft saved:', res))
          .catch(err => console.error('Draft save failed:', err));
    }

    // Input listeners for Autosave reset
    inputs.forEach(input => {
        input.addEventListener('input', () => {
            clearTimeout(autosaveTimer);
            // check if form has content
            const hasContent = Array.from(inputs).some(i => i.value.trim() !== '');
            
            if (hasContent) {
                autosaveTimer = setTimeout(sendDraft, AUTOSAVE_DELAY);
            }
        });
    });

    // Lead Recovery on tab close / page hide
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            sendDraft(true);
        }
    });

    // Form Submission
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearError();
        successMsg.style.display = 'none';

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Optymalizacja: ZAWSZE upewniamy się o token tutaj, eliminując wyścig przy starcie
        await loadCsrfToken();
        data.csrf = CSRF_TOKEN; // Add CSRF token

        // Client-side Honeypot Check
        if (data.website_url) {
            console.warn('Bot detected via honeypot.');
            return; // Silently fail
        }

        const btn = form.querySelector('button[type="submit"]');
        const originalBtnText = btn.textContent;
        btn.textContent = 'Wysyłanie...';
        btn.disabled = true;

        try {
            if (!data.csrf) {
                showError('Błąd CSRF. Odśwież stronę i spróbuj ponownie.');
                return;
            }
            // UPDATE: fetch from api/contact.php
            const response = await fetch(API_BASE + 'contact.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            });

            // Handle non-JSON responses gracefully
            const result = await response.json().catch(() => ({ status: 'error', message: 'Błąd serwera.' }));

            if (response.ok && result.status === 'success') {
                successMsg.style.display = 'block';
                successMsg.textContent = result.message;
                form.reset();
                clearTimeout(autosaveTimer); // Cancel draft if sent
            } else {
                showError(result.message || 'Wystąpił błąd. Spróbuj ponownie.');
            }
        } catch (error) {
            console.error(error);
            showError('Błąd połączenia. Sprawdź internet.');
        } finally {
            btn.textContent = originalBtnText;
            btn.disabled = false;
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initContactForm();
});
