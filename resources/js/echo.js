import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    encrypted: import.meta.env.VITE_REVERB_SCHEME === "https",
    forceTLS: import.meta.env.VITE_REVERB_SCHEME === "https",
    disableStats: true,
    enableTransports: ["ws", "wss"],
    authEndpoint: "/broadcasting/auth",
});
