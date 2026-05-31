import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'koperasikonsumenincoe2026@',
    wsHost: '127.0.0.1',             // Tulis IP langsung
    wsPort: 8080,                    // Tulis port langsung
    wssPort: 8080,
    forceTLS: false,                 // Wajib false di lokal
    enabledTransports: ['ws'],       // Paksa hanya gunakan ws
    path: 'app'
});
