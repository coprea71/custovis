import Alpine from 'alpinejs';
import { store } from './field-store';

// Technician PWA (14.md): works offline. Every action is applied locally
// first and queued in IndexedDB; the queue is sent to /field/sync whenever
// the device is online. The server is authoritative — after each sync the
// day data is reloaded, so conflicting local changes are overwritten.

const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

Alpine.data('fieldApp', () => ({
    appointments: [],
    queue: [],
    selectedId: null,
    online: navigator.onLine,
    locationConsent: false,
    syncing: false,
    lastSync: null,
    notices: [],

    async init() {
        this.queue = (await store.get('queue')) ?? [];
        window.addEventListener('online', () => { this.online = true; this.flush(); });
        window.addEventListener('offline', () => { this.online = false; });
        await this.load();
        this.flush();
    },

    get selected() {
        return this.appointments.find((a) => a.id === this.selectedId) ?? null;
    },

    async load() {
        try {
            const response = await fetch('/field/today', { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) throw new Error(response.status);
            const data = await response.json();
            await store.set('today', data);
            this.apply(data);
        } catch {
            const cached = await store.get('today');
            if (cached) this.apply(cached);
        }
    },

    apply(data) {
        this.appointments = data.appointments;
        this.locationConsent = data.location_consent;
        this.lastSync = data.generated_at;
        this.notices = data.recent_results ?? [];
    },

    async enqueue(type, appointmentId, payload) {
        this.queue.push({ id: crypto.randomUUID(), type, appointment_id: appointmentId, payload });
        await store.set('queue', JSON.parse(JSON.stringify(this.queue)));
        await store.set('today', { appointments: this.appointments, location_consent: this.locationConsent, generated_at: this.lastSync });
        this.flush();
    },

    async flush() {
        if (!this.online || this.syncing || this.queue.length === 0) return;
        this.syncing = true;
        try {
            const response = await fetch('/field/sync', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ operations: this.queue.slice(0, 100) }),
            });
            if (response.status === 202) {
                const { accepted } = await response.json();
                this.queue = this.queue.filter((op) => !accepted.includes(op.id));
                await store.set('queue', JSON.parse(JSON.stringify(this.queue)));
                await this.load();
            }
        } catch {
            // still offline or server unreachable: keep the queue for the next attempt
        } finally {
            this.syncing = false;
        }
    },

    changeState(appointment, next) {
        this.enqueue('status', appointment.id, { from: appointment.state, to: next.key });
        appointment.state = next.key;
        appointment.state_label = next.label;
        appointment.next_states = [];
        if (this.locationConsent && ['en_route', 'on_site'].includes(next.key)) this.sendLocation();
    },

    toggleItem(appointment, item) {
        item.checked = !item.checked;
        this.enqueue('checklist_item', appointment.id, { item_id: item.id, checked: item.checked });
    },

    addPart(appointment, form) {
        const data = Object.fromEntries(new FormData(form));
        if (!data.description) return;
        this.enqueue('part', appointment.id, { description: data.description, quantity: Number(data.quantity || 1), unit: data.unit || 'Stk' });
        form.reset();
    },

    sign(appointment, form, canvas) {
        const data = Object.fromEntries(new FormData(form));
        if (!data.signer_name || canvas.dataset.empty !== 'false') return alert('Bitte Name und Unterschrift erfassen.');
        this.enqueue('signature', appointment.id, { signer_name: data.signer_name, png: canvas.toDataURL('image/png') });
        form.reset();
        clearPad(canvas);
    },

    deliver(appointment, form, canvas) {
        const data = Object.fromEntries(new FormData(form));
        if (!data.delivery_note_number || !data.recipient_name || canvas.dataset.empty !== 'false') {
            return alert('Lieferscheinnummer, Name des Empfängers und Unterschrift sind Pflicht.');
        }
        this.enqueue('delivery', appointment.id, { ...data, signature: canvas.toDataURL('image/png') });
        appointment.delivered = true;
        form.reset();
        clearPad(canvas);
    },

    sendLocation() {
        navigator.geolocation?.getCurrentPosition(
            (pos) => this.enqueue('location', null, { lat: pos.coords.latitude, lng: pos.coords.longitude }),
            () => {},
            { enableHighAccuracy: false, timeout: 10000 },
        );
    },

    formatTime(iso) {
        return new Date(iso).toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
    },
}));

// Signature pad: plain canvas + pointer events, no dependency.
Alpine.directive('signature-pad', (canvas) => {
    const ctx = canvas.getContext('2d');
    let drawing = false;
    clearPad(canvas);
    const point = (e) => {
        const rect = canvas.getBoundingClientRect();
        return [(e.clientX - rect.left) * (canvas.width / rect.width), (e.clientY - rect.top) * (canvas.height / rect.height)];
    };
    canvas.addEventListener('pointerdown', (e) => { drawing = true; ctx.beginPath(); ctx.moveTo(...point(e)); canvas.setPointerCapture(e.pointerId); });
    canvas.addEventListener('pointermove', (e) => { if (!drawing) return; ctx.lineTo(...point(e)); ctx.stroke(); canvas.dataset.empty = 'false'; });
    canvas.addEventListener('pointerup', () => { drawing = false; });
});

function clearPad(canvas) {
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.lineWidth = 2;
    ctx.strokeStyle = '#0f172a';
    canvas.dataset.empty = 'true';
}

window.clearSignaturePad = clearPad;

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/field-sw.js', { scope: '/field' });
}

Alpine.start();
