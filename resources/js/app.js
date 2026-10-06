import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Kleine Hilfsfunktion für JSON-Anfragen an interne Endpunkte (mit CSRF-Token).
 */
window.api = async (url, options = {}) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const response = await fetch(url, {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        ...options,
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        const message = data.message || 'Es ist ein Fehler aufgetreten.';
        throw Object.assign(new Error(message), { status: response.status, data });
    }
    return data;
};

window.euro = (cent) => (cent / 100).toLocaleString('de-DE', { style: 'currency', currency: 'EUR' });

/**
 * Suchfeld mit Vorschlägen (Läufer, Sponsoren, ...).
 */
Alpine.data('suche', (url) => ({
    q: '',
    treffer: [],
    offen: false,
    laden: false,
    async suchen() {
        if (this.q.trim().length < 2) {
            this.treffer = [];
            this.offen = false;
            return;
        }
        this.laden = true;
        try {
            this.treffer = await window.api(url + '?q=' + encodeURIComponent(this.q));
            this.offen = true;
        } finally {
            this.laden = false;
        }
    },
}));

Alpine.start();
