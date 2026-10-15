<?php

/*
 * Configuración del juego.
 *
 * "reverb_publico" es la dirección por la que los NAVEGADORES se conectan
 * a Reverb. No es la misma que usa Laravel (REVERB_HOST = localhost),
 * porque con un túnel los jugadores entran desde afuera.
 *
 * Si se deja vacío, el navegador usa la misma dirección de la página
 * con el puerto 8080 (sirve para jugar en la misma red wifi).
 *
 * Buena práctica de Laravel: env() se usa SOLO en los archivos de config;
 * en el resto del código se lee con config('juego....').
 */
return [
    'reverb_publico' => [
        'host' => env('REVERB_PUBLIC_HOST'),
        'port' => env('REVERB_PUBLIC_PORT'),
        'scheme' => env('REVERB_PUBLIC_SCHEME'),
    ],
];
