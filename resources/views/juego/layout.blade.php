<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    {{-- El token CSRF: fetch() lo necesita para que Laravel acepte los POST --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Dónde está Reverb para los navegadores (ver config/juego.php y resources/js/echo.js) --}}
    <meta name="reverb" content="{{ json_encode(array_filter(config('juego.reverb_publico'))) }}">
    <title>@yield('titulo', 'Kernel Corrupto')</title>

    {{-- Fuentes pixel: Press Start 2P para títulos, VT323 (tipo terminal) para textos --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=VT323&display=swap" rel="stylesheet">

    @vite(['resources/js/app.js'])
    @yield('vite')

    <style>
        /* ===== Paleta (la misma del sprite del jefe) ===== */
        :root {
            --vacio:   #05050c;
            --fondo:   #0b0d1f;
            --contorno:#1a1033;
            --sombra:  #2a1a55;
            --violeta: #4b2f9a;
            --luz:     #7a5cd6;
            --cian:    #5ff3ff;
            --carmesi: #e0245e;
            --hielo:   #9ad8ff;
            --texto:   #e8e4ff;
            --pixel:   'Press Start 2P', monospace;
            --terminal:'VT323', monospace;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0; min-height: 100vh;
            font-family: var(--terminal); font-size: 22px; color: var(--texto);
            background:
                radial-gradient(ellipse at 50% 40%, #1d1446 0%, var(--fondo) 55%, var(--vacio) 100%);
            overflow-x: hidden;
        }

        /* Líneas de escaneo tipo monitor viejo (CRT) encima de todo */
        body::after {
            content: ''; position: fixed; inset: 0; pointer-events: none; z-index: 100;
            background: repeating-linear-gradient(to bottom, rgba(0,0,0,.18) 0 2px, transparent 2px 4px);
        }

        h1, h2, .pixel { font-family: var(--pixel); font-weight: normal; }

        /* Marco pixelado: esquinas "mordidas" con clip-path */
        .marco {
            background: var(--contorno);
            border: 4px solid var(--violeta);
            box-shadow: 6px 6px 0 var(--vacio);
            clip-path: polygon(0 6px, 6px 6px, 6px 0, calc(100% - 6px) 0, calc(100% - 6px) 6px, 100% 6px,
                               100% calc(100% - 6px), calc(100% - 6px) calc(100% - 6px), calc(100% - 6px) 100%,
                               6px 100%, 6px calc(100% - 6px), 0 calc(100% - 6px));
        }

        /* Barra de vida segmentada */
        .barra {
            width: 100%; height: 28px; padding: 4px;
            background: var(--vacio); border: 4px solid var(--luz);
            position: relative;
        }
        .barra-relleno {
            height: 100%; background: var(--carmesi);
            box-shadow: inset 0 -6px 0 rgba(0,0,0,.35), inset 0 4px 0 rgba(255,255,255,.25);
            transition: width .2s steps(4);
        }
        .barra::after { /* los "cortes" entre segmentos */
            content: ''; position: absolute; inset: 4px; pointer-events: none;
            background: repeating-linear-gradient(to right, transparent 0 18px, var(--vacio) 18px 22px);
        }
        .barra.baja .barra-relleno { animation: titilar .4s steps(2) infinite; }
        @keyframes titilar { 50% { background: #ff8fb0; } }
        @keyframes parpadeoTexto { 50% { opacity: .35; } }

        /* Botón pixel genérico */
        .btn-pixel {
            font-family: var(--pixel); font-size: 16px; color: var(--vacio);
            background: var(--cian); border: 0; padding: 16px 20px; cursor: pointer;
            box-shadow: inset -4px -4px 0 rgba(0,0,0,.35), inset 4px 4px 0 rgba(255,255,255,.35), 4px 4px 0 var(--vacio);
        }
        .btn-pixel:active { transform: translate(2px, 2px); box-shadow: inset -4px -4px 0 rgba(0,0,0,.35), 2px 2px 0 var(--vacio); }
        .btn-pixel:disabled { filter: grayscale(1) brightness(.6); cursor: not-allowed; }

        /* Números de daño flotantes */
        .numero {
            position: absolute; pointer-events: none; z-index: 20;
            font-family: var(--pixel); white-space: nowrap;
            text-shadow: 3px 3px 0 var(--vacio), -2px -2px 0 var(--vacio);
            animation: subir .9s steps(9) forwards;
        }
        @keyframes subir {
            0%   { transform: translateY(0) scale(.8); opacity: 1; }
            20%  { transform: translateY(-20px) scale(1.2); }
            100% { transform: translateY(-90px) scale(1); opacity: 0; }
        }

        /* Glitch: se lo agregamos a un elemento un instante */
        .glitch { animation: glitch .3s steps(3) 1; }
        @keyframes glitch {
            0%   { transform: translate(0); filter: none; }
            33%  { transform: translate(-8px, 3px) skewX(6deg); filter: hue-rotate(90deg) saturate(2); }
            66%  { transform: translate(6px, -3px) skewX(-4deg); filter: invert(.15); }
            100% { transform: translate(0); filter: none; }
        }

        @yield('estilos')
    </style>
</head>
<body>
    @yield('contenido')
    @yield('scripts')
</body>
</html>
