function initContactForm() {
    const form = document.getElementById('contactForm');
    if (!form) return;

    const errorDiv = document.getElementById('formErrors');
    const successMsg = document.getElementById('formSuccess');
    const inputs = form.querySelectorAll('input, textarea');

    let autosaveTimer;
    const AUTOSAVE_DELAY = 15 * 60 * 1000; // 15 minut

    function clearError() {
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';
    }

    function showError(msg) {
        errorDiv.style.display = 'block';
        errorDiv.textContent = msg;
    }

    function collectData(extra = {}) {
        const fd = new FormData(form);
        return Object.assign(Object.fromEntries(fd.entries()), extra);
    }

    function hasAnyContent() {
        return Array.from(inputs).some(i => i.value.trim() !== '');
    }

    async function sendDraft() {
        const data = collectData({ type: 'lead_recovery' });

        if (!data.email && !data.name) return;

        try {
            await fetch('assets/php/contact.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
        } catch (e) {
            console.warn('Lead recovery failed silently');
        }
    }

    inputs.forEach(input => {
        input.addEventListener('input', () => {
            clearTimeout(autosaveTimer);
            if (hasAnyContent()) {
                autosaveTimer = setTimeout(sendDraft, AUTOSAVE_DELAY);
            }
        });
    });

    // Wyślij szkic przy opuszczaniu strony
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            sendDraft();
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearError();
        successMsg.style.display = 'none';

        const data = collectData({ type: 'standard' });

        // Honeypot
        if (data.website_url) return;

        if (!data.name || !data.email || !data.message) {
            showError('Uzupełnij wszystkie wymagane pola.');
            return;
        }

        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.textContent;
        btn.textContent = 'Wysyłanie...';
        btn.disabled = true;

        try {
            const res = await fetch('assets/php/contact.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await res.json();

            if (res.ok && result.status === 'success') {
                successMsg.style.display = 'block';
                successMsg.textContent = result.message;
                form.reset();
                clearTimeout(autosaveTimer);
            } else {
                showError(result.message || 'Błąd wysyłania.');
            }
        } catch {
            showError('Błąd połączenia z serwerem.');
        } finally {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    });
}

document.addEventListener('DOMContentLoaded', initContactForm);
