// Tech Stack Interactions - Dynamic Description
function initTechStack() {
    console.log('Initializing Tech Stack...');
    const techItems = document.querySelectorAll('.tech-item');
    const techDesc = document.getElementById('tech-description');

    if (!techDesc) {
        console.error('Tech description element not found');
        return;
    }

    if (techItems.length === 0) {
        console.warn('No tech items found');
    }

    // Set initial text
    techDesc.textContent = "Najedź na technologię, aby zobaczyć szczegóły.";
    techDesc.style.opacity = '0.8';

    techItems.forEach(item => {
        // Use mouseover for potentially better bubbling support
        item.addEventListener('mouseover', () => {
            const text = item.getAttribute('data-tooltip');
            if (text) {
                techDesc.style.opacity = '1';
                techDesc.textContent = text;
                techDesc.classList.add('active');
                techDesc.style.color = '#fff';
            }
        });

        item.addEventListener('mouseout', () => {
             techDesc.style.opacity = '0.8';
             techDesc.textContent = "Najedź na technologię, aby zobaczyć szczegóły.";
             techDesc.classList.remove('active');
             techDesc.style.color = ''; 
        });
    });
}

// Contact Form Logic
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
    function sendDraft() {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Only send if there's at least an email or phone/name so we know who it is
        if (!data.email && !data.name) return;

        data.type = 'lead_recovery';

        console.log('Sending draft (Lead Recovery)...');
        fetch('contact.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
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

    // Form Submission
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearError();
        successMsg.style.display = 'none';

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

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
            const response = await fetch('contact.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            // Handle non-JSON responses gracefully
            const result = await response.json().catch(() => ({ status: 'error', message: 'Błąd sewera.' }));

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
    // Check if startSequence exists
    if (typeof startSequence === 'function') {
        startSequence();
    }
    // Small delay to ensure DOM is fully ready
    setTimeout(initTechStack, 100);
    initContactForm();
});
