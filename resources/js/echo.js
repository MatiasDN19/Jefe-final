import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

/*
 * ¿A dónde se conecta el navegador para el WebSocket?
 *
 * Antes salía de las variables VITE_REVERB_*, pero esas quedan "grabadas"
 * en el JS al compilar. Con un túnel, la dirección cambia cada vez que lo
 * abrís, y habría que recompilar todo.
 *
 * Ahora la página la trae en un <meta name="reverb"> que arma Laravel
 * leyendo el .env en cada pedido. Cambiás el .env, recargás y listo.
 * Si no hay nada configurado, usamos la misma dirección de la página
 * con el puerto 8080 (sirve para localhost y para la red del wifi).
 */
const publico = JSON.parse(document.querySelector('meta[name=reverb]')?.content || '{}');
const hayTunel = Boolean(publico.host); // el puerto y el esquema solo valen si hay túnel
const scheme = hayTunel ? (publico.scheme || 'https') : (location.protocol === 'https:' ? 'https' : 'http');
const port = Number(hayTunel ? (publico.port || 443) : (import.meta.env.VITE_REVERB_PORT || 8080));

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: publico.host || location.hostname,
    wsPort: port,
    wssPort: port,
    forceTLS: scheme === 'https',
    enabledTransports: ['ws', 'wss'],
});
