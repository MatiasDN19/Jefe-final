import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/pantalla.js', // escena 3D de la pantalla grande (Three.js)
                'resources/js/control.js',  // campo de batalla del jugador
                'resources/js/unirse.js',   // vista previa de los campeones
                'resources/js/portada.js',  // portada con el menú
                'resources/js/campo-pantalla.js', // campeones en la pantalla grande
                'resources/js/victoria-pantalla.js', // podio final de la pantalla grande
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
