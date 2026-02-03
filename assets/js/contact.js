// Contact Form Logic
const API_BASE = '/api/';
let CSRF_TOKEN = '';

async function loadCsrfToken() {
    try {
        const response = await fetch(API_BASE + 'get-csrf-token.php');
        const data = await response.json();
        if (data && data.token) {
            CSRF_TOKEN = data.token;
            console.log('CSRF Token loaded.');
        }
    } catch (e) {
        console.error('Error loading CSRF token:', e);
    }
}

function initContactForm() {
    const form = document.getElementById('contactForm');
    if (!form) return;

    // Load Token immediately
    loadCsrfToken();

    const errorDiv = document.getElementById('formErrors');
    const successMsg = document.getElementById('formSuccess');
    
    // Autosave / Lead Recovery Variables
    let autosaveTimer;
    const AUTOSAVE_DELAY = 15 * 60 * 1000; // 15 minutes

    const inputs = form.querySelectorAll('input, textarea');
    const btn = form.querySelector('button[type="submit"]');
    const originalBtnText = btn ? btn.textContent : 'Wyślij';

    // Helper to clear error
    function clearError() {
        if(errorDiv) {
            errorDiv.style.display = 'none';
            errorDiv.textContent = '';
        }
    }

    // Helper to show error
    function showError(msg) {
        if(errorDiv) {
            errorDiv.style.display = 'block';
            errorDiv.textContent = msg;
        } else {
            alert(msg);
        }
    }

    // Lead Recovery: Send Draft
    function sendDraft(isUnload = false) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        // Add CSRF to draft? Not strictly enforced yet but good practice
        if (CSRF_TOKEN) data.csrf_token = CSRF_TOKEN;
        
        // Only send if there's at least an email or phone/name so we know who it is
        if (!data.email && !data.name) return;

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
        if(successMsg) successMsg.style.display = 'none';
        
        if (btn) {
            btn.textContent = 'Wysyłanie...';
            btn.disabled = true;
        }

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Append CSRF
        if (CSRF_TOKEN) {
            data.csrf_token = CSRF_TOKEN;
        } else {
            console.warn('Submitting without CSRF token (fetch failed?)');
        }

        try {
            const response = await fetch(API_BASE + 'contact.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            });

            // Handle non-JSON responses gracefully
            const result = await response.json().catch(() => ({ status: 'error', message: 'Błąd serwera.' }));

            if (response.ok && result.status === 'success') {
                if(successMsg) {
                    successMsg.style.display = 'block';
                    successMsg.textContent = result.message || 'Wiadomość wysłana!';
                } else {
                    alert(result.message || 'Wiadomość wysłana!');
                }
                form.reset();
                clearTimeout(autosaveTimer); // Cancel draft if sent
                // Refresh token for next send (if needed, though single use usually fine for contact)
                loadCsrfToken(); 
            } else {
                showError(result.message || 'Wystąpił błąd. Spróbuj ponownie.');
                // Refetch token if it was invalid
                if (response.status === 403) loadCsrfToken();
            }
        } catch (error) {
            console.error(error);
            showError('Błąd połączenia. Sprawdź internet.');
        } finally {
            if (btn) {
                btn.textContent = originalBtnText;
                btn.disabled = false;
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initContactForm();
});
