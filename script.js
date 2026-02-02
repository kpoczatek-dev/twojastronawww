const techSkills = [
    "Optymalizacja pod Google (SEO) ...",
    "Błyskawiczne czasy ładowania ...",
    "Pełna responsywność RWD ...",
    "Bezpieczeństwo danych i kopie zapasowe ...",
    "Indywidualny projekt graficzny ...",
    "Czysty kod PHP & Flat-File ...",
    "Automatyzacja procesów ..."
];

const softSkills = [
    "Darmowa wycena projektu ...",
    "Wsparcie posprzedażowe ...",
    "Jasna komunikacja i relacje ...",
    "Terminowość realizacji ...",
    "Indywidualne konsultacje ...",
    "Zrozumienie Twojego biznesu ..."
];

const scene = document.getElementById('terminalScene');
const term2 = document.getElementById('term2');
const termInput = document.getElementById('terminal-input');
const cursor1 = document.getElementById('cursor1');

// Helper to auto-scroll to bottom
function scrollToBottom(historyElement) {
    if (historyElement && historyElement.parentElement) {
        historyElement.parentElement.scrollTop = historyElement.parentElement.scrollHeight;
    }
}

async function runTerminal(termId, textArray, nextCallback) {
    const historyEl = document.getElementById(`history${termId}`);
    // ...
    // Note: The rest of the logic uses scrollToBottom(historyEl), which now correctly scrolls the parent (.terminal-body)

    const typerEl = document.getElementById(`typewriter${termId}`);
    const promptStr = termId === 1 ? '➜ dev ~' : '➜ boss ~';
    const colorDone = termId === 1 ? '#27c93f' : '#3b82f6';

    historyEl.innerHTML = '';
    typerEl.textContent = '';

    for (let i = 0; i < textArray.length; i++) {
        const text = textArray[i];

        // Typing effect
        for (let j = 0; j <= text.length; j++) {
            typerEl.textContent = text.substring(0, j);
            await new Promise(r => setTimeout(r, 20 + Math.random() * 30));
        }

        // Wait before finishing line
        await new Promise(r => setTimeout(r, 50));

        // Move to history
        const line = document.createElement('div');
        line.className = 'history-line';
        line.innerHTML = `<span class="prompt">${promptStr}</span> <span style="color: #fff">${text}</span> <span style="color: ${colorDone}">✓</span>`;
        historyEl.appendChild(line);
        scrollToBottom(historyEl);

        // Keep more history to avoid whitespace (increased from 5 to 15)
        const lines = historyEl.children;
        if (lines.length > 15) {
            historyEl.removeChild(lines[0]);
        }

        // Apply fade only to very old lines if we want, or just keep them visible
        // Updating fade logic to be less aggressive or strictly for top lines
        for (let k = 0; k < lines.length; k++) {
            const reverseIndex = lines.length - 1 - k;
            lines[k].className = 'history-line'; // reset
            if (reverseIndex > 10) lines[k].classList.add('fade-3'); 
            else if (reverseIndex > 8) lines[k].classList.add('fade-2');
        }

        typerEl.textContent = '';

        // Handoff to second terminal logic mid-sequence if needed
        if (termId === 1 && i === 5 && nextCallback) {
            await new Promise(r => setTimeout(r, 500));
            nextCallback();
            return;
        }
    }

    // Final callback for term2
    if (termId === 2 && nextCallback) {
        nextCallback();
    }
}

async function startSequence() {
    scene.classList.remove('dual-mode');
    // term2.collapsed not needed with grid 0fr
    termInput.style.display = 'none';
    cursor1.style.display = 'inline';

    runTerminal(1, techSkills, async () => {
        scene.classList.add('dual-mode');
        // term2.collapsed remove not needed

        await new Promise(r => setTimeout(r, 1200));

        runTerminal(2, softSkills, async () => {
            // Enable interaction for both terminals
            enableTerminalInteraction(1);
            enableTerminalInteraction(2);
        });
    });
}

function enableTerminalInteraction(termId) {
    const typer = document.getElementById(`typewriter${termId}`);
    const cursor = document.getElementById(`cursor${termId}`);
    const input = document.getElementById(termId === 1 ? 'terminal-input' : 'terminal-input-2');
    const history = document.getElementById(`history${termId}`);
    const terminalWindow = document.getElementById(`term${termId}`);
    const promptStr = termId === 1 ? '➜ dev ~' : '➜ boss ~';

    // Clear typing artifacts
    typer.textContent = ''; 
    cursor.style.display = 'none'; 

    // Show input
    input.style.display = 'inline-block';
    
    // Initial focus on term1, but allow clicking both
    if (termId === 1) {
        input.focus();
        input.placeholder = "Wpisz 'kontakt'...";
    } else {
        input.placeholder = "Wpisz polecenie...";
    }

    // Click listener
    terminalWindow.onclick = function () {
        input.focus();
    };

    // Input handler
    input.onkeydown = function (e) {
        if (e.key === 'Enter') {
            const val = this.value.trim();
            if (!val) return;

            // Add user command to history
            const line = document.createElement('div');
            line.className = 'history-line';
            line.innerHTML = `<span class="prompt">${promptStr}</span> <span style="color: #fff">${val}</span>`;
            history.appendChild(line);
            scrollToBottom(history);

            this.value = '';

            // Response logic
            setTimeout(() => {
                const resp = document.createElement('div');
                resp.className = 'history-line';
                
                if (val.toLowerCase().includes('kontakt')) {
                    resp.innerHTML = `
                        <div style="color: #27c93f; margin-bottom: 5px;">Dane kontaktowe:</div>
                        <div>Email: <a href="mailto:kontakt@twojastronawww.pl" style="color: #fff; text-decoration: underline;">kontakt@twojastronawww.pl</a></div>
                        <div>Tel: <span style="color: #fff">+48 123 456 789</span></div>
                    `;
                    // Only scroll if it was requested from term1 specifically? Or just show info.
                    // Let's scroll if term1 for now as it's the main CTA
                    if (termId === 1) {
                         // Optional: Scroll logic removed as per previous request to keep it in terminal
                    }
                } else if (val.toLowerCase() === 'help' || val.toLowerCase() === 'pomoc') {
                    resp.innerHTML = `<span style="color: #3b82f6">Dostępne: kontakt, help, clear</span>`;
                    history.appendChild(resp);
                } else if (val.toLowerCase() === 'clear') {
                    history.innerHTML = '';
                    scrollToBottom(history); // Ensure cleared view is correct
                    return; // Return early so we don't append resp
                } else {
                    resp.innerHTML = `<span style="color: #ef4444">Nieznana komenda.</span>`;
                    history.appendChild(resp);
                }
                
                if (resp.innerHTML) { // Double check we have content
                     // history.appendChild(resp); // Already appended above
                }
                scrollToBottom(history);
            }, 50);
        }
    };
}

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
