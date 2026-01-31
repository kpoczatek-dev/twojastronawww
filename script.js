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
        await new Promise(r => setTimeout(r, 600));

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
    term2.classList.add('collapsed');
    termInput.style.display = 'none';
    cursor1.style.display = 'inline';

    runTerminal(1, techSkills, async () => {
        scene.classList.add('dual-mode');
        term2.classList.remove('collapsed');

        await new Promise(r => setTimeout(r, 1200));

        runTerminal(2, softSkills, async () => {
            // Enable interaction
            const typer1 = document.getElementById('typewriter1');
            typer1.textContent = ''; 
            cursor1.style.display = 'none'; 

            termInput.style.display = 'inline-block';
            
            // Add prompt method
            const history1 = document.getElementById('history1');
            const promptLine = document.createElement('div');
            promptLine.className = 'history-line';
            promptLine.innerHTML = `<br><span style="color: #06b6d4">➜ System:</span> <span style="color: #fff">Wpisz komendę </span><span style="color: #27c93f">'kontakt'</span><span style="color: #fff"> aby rozpocząć...</span>`;
            history1.appendChild(promptLine);
            scrollToBottom(history1);

            termInput.focus();
            termInput.placeholder = "Wpisz 'kontakt'...";

            // Click listener
            document.getElementById('term1').onclick = function () {
                termInput.focus();
            };

            // Input handler
            termInput.onkeydown = function (e) {
                if (e.key === 'Enter') {
                    const val = this.value.trim();
                    if (!val) return;

                    // Add user command to history
                    const line = document.createElement('div');
                    line.className = 'history-line';
                    line.innerHTML = `<span class="prompt">➜ dev ~</span> <span style="color: #fff">${val}</span>`;
                    history1.appendChild(line);
                    scrollToBottom(history1);

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
                                <div style="color: #94a3b8; font-size: 0.9em; margin-top: 5px;">(Formularz znajdziesz na dole strony)</div>
                            `;
                        } else if (val.toLowerCase() === 'help' || val.toLowerCase() === 'pomoc') {
                            resp.innerHTML = `<span style="color: #3b82f6">Dostępne komendy: kontakt, help, clear</span>`;
                            history1.appendChild(resp);
                        } else if (val.toLowerCase() === 'clear') {
                            history1.innerHTML = '';
                        } else {
                            resp.innerHTML = `<span style="color: #ef4444">Komenda nieznana. Wpisz 'help' lub 'kontakt'.</span>`;
                            history1.appendChild(resp);
                        }
                        scrollToBottom(history1);
                    }, 200);
                }
            };
        });
    });
}

document.addEventListener('DOMContentLoaded', startSequence);
