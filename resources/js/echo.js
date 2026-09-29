import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Connection data comes from the server at runtime (App\Support\Realtime), so
// one release build fits every installation. Null means no Reverb server:
// the Livewire components then poll instead.
const realtime = window.custovisRealtime;

if (realtime) {
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: realtime.key,
        wsHost: realtime.host,
        wsPort: realtime.port,
        wssPort: realtime.port,
        forceTLS: realtime.scheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
