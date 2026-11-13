// Validasi sisi klien. Aturan sama dengan validasi server; lihat halaman /docs.
const validationMessage = (field) => {
    const { validity } = field;
    const value = field.value.trim();
    if (validity.badInput) return 'Harus berupa angka.';
    if (validity.valueMissing || (field.required && field.type !== 'file' && field.type !== 'radio' && value === '')) return 'Wajib diisi.';
    if (field.type !== 'file' && field.type !== 'radio' && value === '') return '';
    if (field.minLength > 0 && value.length < field.minLength) return `Minimal ${field.minLength} karakter.`;
    if (field.maxLength > 0 && field.value.length > field.maxLength) return `Maksimal ${field.maxLength} karakter.`;
    if (validity.typeMismatch && field.type === 'email') return 'Harus berupa alamat email yang valid.';
    if (validity.patternMismatch) return field.dataset.patternMessage || 'Format tidak sesuai.';
    if (validity.rangeUnderflow) return `Minimal ${Number(field.min).toLocaleString('id-ID')}.`;
    if (validity.rangeOverflow) return `Maksimal ${Number(field.max).toLocaleString('id-ID')}.`;
    if (validity.stepMismatch) return 'Harus berupa angka bulat.';
    if (field.dataset.match && field.value !== document.getElementById(field.dataset.match)?.value) return 'Konfirmasi kata sandi tidak cocok.';
    if (field.type === 'file' && field.files.length) {
        const file = field.files[0];
        if (file.type !== 'application/pdf') return 'Berkas harus berformat PDF.';
        if (file.size > Number(field.dataset.maxSize || Infinity)) return 'Ukuran berkas melebihi 10 MB.';
    }
    return '';
};

// Nomor registrasi selalu huruf kapital.
document.querySelectorAll('[data-uppercase]').forEach((field) => {
    field.addEventListener('input', () => (field.value = field.value.toUpperCase()));
});

// Nilai = skor × bobot, terisi otomatis. Server menghitung ulang; nilai tidak dikirim dari form.
const scoreFields = [...document.querySelectorAll('[data-weight]')];
const formatValue = (number) => number.toLocaleString('id-ID', { maximumFractionDigits: 2 });
const updateValues = () => {
    let total = 0;
    let complete = true;
    scoreFields.forEach((field) => {
        const score = field.value === '' ? NaN : Number(field.value);
        const valid = Number.isInteger(score) && score >= 0 && score <= 100;
        const value = valid ? Math.round(score * Number(field.dataset.weight)) / 100 : null;
        document.getElementById(field.dataset.valueTarget).textContent = value === null ? '–' : formatValue(value);
        total += value ?? 0;
        complete &&= value !== null;
    });
    const totalOutput = document.getElementById('value-total');
    if (totalOutput) totalOutput.textContent = complete ? formatValue(Math.round(total * 100) / 100) : '–';
};
scoreFields.forEach((field) => field.addEventListener('input', updateValues));
if (scoreFields.length) updateValues();

const validateFields = (fields) => {
    let firstInvalid = null;
    const seen = new Set();
    fields.forEach((field) => {
        if (field.type === 'radio') {
            if (seen.has(field.name)) return;
            seen.add(field.name);
        }
        field.jsError?.remove();
        const message = validationMessage(field);
        if (!message) return;
        const el = document.createElement('p');
        el.className = 'error';
        el.dataset.jsError = '';
        el.textContent = message;
        field.jsError = el;
        (field.type === 'radio' ? field.closest('fieldset') : field).after(el);
        firstInvalid ??= field;
    });
    firstInvalid?.focus?.();
    return !firstInvalid;
};

document.querySelectorAll('form[data-validate]').forEach((form) => {
    form.noValidate = true;
    form.addEventListener('submit', (event) => {
        const fields = [...form.querySelectorAll('input:not([type=hidden]), textarea, select')];
        if (!validateFields(fields)) event.preventDefault();
    });
});

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
            const fields = [...steps[current].querySelectorAll('input, textarea, select')];
            if (validateFields(fields)) {
                show(current + 1);
            }
        }
        if (event.target.closest('[data-prev]')) {
            show(current - 1);
        }
    });

    // Langkah hanya bisa dilalui berurutan lewat tombol Lanjut/Kembali; penanda langkah tidak bisa diklik.
    steps.forEach((step, i) => (step.hidden = i !== 0));
}
