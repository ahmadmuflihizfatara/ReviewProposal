// Wizard langkah 2–4 penilaian. Tanpa JS semua langkah tetap tampil (form tetap jalan).
const wizard = document.querySelector('[data-wizard]');

if (wizard && !wizard.hasAttribute('data-show-all')) {
    const steps = [...wizard.querySelectorAll('[data-step]')];
    const indicators = document.querySelectorAll('[data-step-indicator]');
    let current = 0;

    const show = (index) => {
        current = index;
        steps.forEach((step, i) => (step.hidden = i !== index));
        // Langkah 1 sudah selesai, jadi section ke-i = langkah ke-(i + 2).
        indicators.forEach((el) => {
            const number = Number(el.dataset.stepIndicator);
            el.dataset.state = number < index + 2 ? 'done' : number === index + 2 ? 'active' : 'todo';
        });
        wizard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    wizard.addEventListener('click', (event) => {
        if (event.target.closest('[data-next]')) {
            const fields = steps[current].querySelectorAll('input, textarea, select');
            if ([...fields].every((field) => field.reportValidity())) {
                show(current + 1);
            }
        }
        if (event.target.closest('[data-prev]')) {
            show(current - 1);
        }
    });

    steps.forEach((step, i) => (step.hidden = i !== 0));

    // Penanda langkah bisa diklik: lompat ke langkah mana pun tanpa harus berurutan.
    indicators.forEach((el) => {
        const number = Number(el.dataset.stepIndicator);
        el.setAttribute('role', 'button');
        el.setAttribute('tabindex', '0');
        el.classList.add('cursor-pointer');
        const go = () => (number === 1 ? document.getElementById('registration_number').scrollIntoView({ behavior: 'smooth', block: 'center' }) : show(number - 2));
        el.addEventListener('click', go);
        el.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                go();
            }
        });
    });
}
