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

document.addEventListener('DOMContentLoaded', () => {
    // Check if startSequence exists
    if (typeof startSequence === 'function') {
        startSequence();
    }
    // Small delay to ensure DOM is fully ready
    setTimeout(initTechStack, 100);
});
