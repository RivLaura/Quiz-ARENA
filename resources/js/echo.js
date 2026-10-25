import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const host = import.meta.env.VITE_REVERB_HOST;
const secure = (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: host,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
    wssPort: 443,
    forceTLS: secure,
    enabledTransports: secure ? ['wss'] : ['ws'],
});