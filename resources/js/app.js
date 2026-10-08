import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// ---------------------------------------------------------------------------
// Modo oscuro (preferencia guardada en el navegador)
// ---------------------------------------------------------------------------
Alpine.store('theme', {
    dark: document.documentElement.classList.contains('dark'),
    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { dark: this.dark } }));
    },
});

// ---------------------------------------------------------------------------
// Indicadores de chat y notificaciones (sondeo periódico)
// ---------------------------------------------------------------------------
Alpine.store('counters', {
    chat: window.__counters?.chat ?? 0,
    notifications: window.__counters?.notifications ?? 0,
    init() {
        if (!window.__counters) return; // páginas públicas (login)
        setInterval(() => this.refresh(), 20000);
    },
    async refresh() {
        if (document.hidden) return;
        try {
            const { data } = await window.axios.get('/chat/unread');
            this.chat = data.total;
            this.notifications = data.notifications;
        } catch (e) { /* sin conexión: se ignora */ }
    },
});

// ---------------------------------------------------------------------------
// Verificación en vivo de número de caso duplicado
// ---------------------------------------------------------------------------
Alpine.data('caseNumberCheck', (url, ignore = null, initial = '') => ({
    value: initial,
    status: null,
    message: '',
    link: null,
    timer: null,
    check() {
        clearTimeout(this.timer);
        if (!this.value.trim()) { this.status = null; return; }
        this.status = 'checking';
        this.timer = setTimeout(async () => {
            try {
                const { data } = await window.axios.get(url, { params: { number: this.value, ignore } });
                this.status = data.exists ? 'duplicate' : 'ok';
                this.message = data.message || '';
                this.link = data.url || null;
            } catch (e) {
                this.status = null;
            }
        }, 400);
    },
}));

// ---------------------------------------------------------------------------
// Confirmación antes de enviar formularios peligrosos: <form data-confirm="...">
// ---------------------------------------------------------------------------
document.addEventListener('submit', (event) => {
    const message = event.target?.dataset?.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
        event.stopImmediatePropagation();
    }
}, true);

Alpine.start();
