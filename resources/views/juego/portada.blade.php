@extends('juego.layout')

@section('titulo', 'Kernel Corrupto')

@section('vite')
    @vite(['resources/js/portada.js'])
@endsection

@section('estilos')
    .portada {
        max-width: 520px; margin: 0 auto; min-height: 100vh; padding: 24px 16px;
        display: flex; flex-direction: column; align-items: center; gap: 18px; text-align: center;
    }
    .portada h1 {
        margin: 0; font-size: 30px; line-height: 1.4; color: var(--texto); letter-spacing: 2px;
        text-shadow: 4px 4px 0 var(--carmesi), -2px -2px 0 var(--violeta);
        animation: glitch 4s steps(3) infinite;
    }
    .bajada { color: var(--luz); margin-top: -8px; }
    #jefe-portada { image-rendering: pixelated; width: 220px; max-width: 70vw; }
    .estado { color: var(--cian); font-size: 20px; }
    .estado .fase-3 { color: var(--carmesi); }

    .menu { width: 100%; display: flex; flex-direction: column; gap: 10px; }
    .menu form { margin: 0; }
    .opcion {
        display: block; width: 100%; padding: 14px 16px; text-align: left; text-decoration: none; cursor: pointer;
        font-family: var(--pixel); font-size: 13px; color: var(--texto);
        background: var(--contorno); border: 3px solid var(--sombra);
    }
    .opcion::before { content: '  '; white-space: pre; }
    .opcion.elegida { border-color: var(--cian); color: var(--cian); background: var(--sombra); }
    .opcion.elegida::before { content: '▶ '; }
    .opcion small { display: block; margin-top: 6px; font-family: var(--terminal); font-size: 18px; color: var(--luz); }
    .opcion.peligro.elegida { border-color: var(--carmesi); color: var(--carmesi); }

    .como-jugar { display: none; text-align: left; padding: 14px 16px; font-size: 20px; line-height: 1.25; }
    .como-jugar.visible { display: block; }
    .como-jugar h2 { margin: 0 0 10px; font-size: 12px; color: var(--cian); }
    .como-jugar p { margin: 0 0 8px; }
    .como-jugar kbd { font-family: var(--pixel); font-size: 10px; padding: 2px 5px; background: var(--sombra); color: var(--cian); }
    .pie { font-size: 17px; color: var(--violeta); }

    /* Mientras corre la intro, el menú queda escondido */
    body.en-intro .portada { visibility: hidden; }

    /* ===== 1. INTRO: PRESS START ===== */
    .intro, .carga {
        position: fixed; inset: 0; z-index: 200; display: none; background: var(--vacio);
        user-select: none; -webkit-tap-highlight-color: transparent;
    }
    .intro.visible, .carga.visible { display: block; }
    #intro-escena { position: absolute; inset: 0; }
    #intro-lienzo { position: absolute; inset: 0; width: 100%; height: 100%; image-rendering: pixelated; pointer-events: none; }
    .intro .vineta { position: absolute; inset: 0; pointer-events: none;
        background: radial-gradient(ellipse at center, transparent 45%, rgba(5,5,12,.85) 100%); }
    .intro.sacudida { animation: sacudida .5s steps(8); }
    @keyframes sacudida {
        0%, 100% { transform: translate(0, 0); }
        20% { transform: translate(-14px, 8px); }
        40% { transform: translate(12px, -10px); }
        60% { transform: translate(-8px, 6px); }
        80% { transform: translate(6px, -4px); }
    }
    .intro .titulo {
        position: absolute; left: 0; right: 0; bottom: 26%; text-align: center; opacity: 0; white-space: nowrap;
        font-family: var(--pixel); font-size: clamp(20px, 5vw, 56px); line-height: 1.3; color: #fff;
        text-shadow: 5px 5px 0 var(--carmesi), -3px -3px 0 var(--violeta), 0 0 30px rgba(224,36,94,.6);
    }
    .intro.con-titulo .titulo { opacity: 1; animation: entradaTitulo .9s steps(6) 1, glitch 3s steps(3) 1s infinite; }
    /* El título "cae" desde gigante y rebota */
    @keyframes entradaTitulo {
        0% { opacity: 0; transform: scale(4); filter: blur(6px) brightness(3); }
        50% { opacity: 1; transform: scale(.9); filter: brightness(2); }
        75% { transform: scale(1.06); }
        100% { transform: scale(1); filter: none; }
    }
    .intro .bajada {
        position: absolute; left: 0; right: 0; bottom: 19%; text-align: center; opacity: 0;
        font-size: clamp(18px, 3vw, 28px); color: var(--luz); letter-spacing: 2px;
    }
    .intro.con-titulo .bajada { opacity: 1; transition: opacity 1s .6s; }
    .intro .start {
        position: absolute; left: 0; right: 0; bottom: 8%; text-align: center; opacity: 0;
        font-family: var(--pixel); font-size: clamp(14px, 2.6vw, 24px); color: var(--cian);
        text-shadow: 3px 3px 0 var(--vacio);
    }
    .intro .start small { display: block; margin-top: 10px; font-family: var(--terminal); font-size: 18px; color: var(--violeta); }
    .intro.con-start .start { opacity: 1; animation: parpadeoStart 1s steps(2) infinite; }
    .intro.con-start .start small { animation: none; }
    @keyframes parpadeoStart { 50% { opacity: .15; } }
    .intro.saliendo { animation: flashSalida .7s steps(5) forwards; }
    @keyframes flashSalida {
        0% { filter: none; }
        30% { filter: brightness(3) saturate(0); }
        100% { opacity: 0; filter: brightness(3); }
    }

    /* ===== 2. CARGA: arranque del sistema ===== */
    .carga .caja-carga {
        position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);
        width: min(560px, 92vw); display: flex; flex-direction: column; gap: 14px;
    }
    .carga h2 { margin: 0; font-size: 14px; color: var(--carmesi); text-align: center; }
    #carga-log { height: 220px; overflow: hidden; padding: 14px; font-size: 20px; line-height: 1.3; }
    #carga-log .linea { color: var(--luz); }
    #carga-log .ok { color: var(--cian); }
    #carga-log .alerta { color: #ff8a3d; }
    #carga-log .peligro { color: var(--carmesi); animation: parpadeoTexto .4s steps(2) 3; }
    #carga-log .listo { color: #46a758; }
    .carga .barra { height: 26px; }
    .carga .barra-relleno { background: var(--cian); width: 0; transition: width .25s steps(3); }
    #carga-campeones { width: 100%; image-rendering: pixelated; }
    .carga.saliendo { animation: flashSalida .45s steps(4) forwards; }
    .pie a { color: var(--luz); }
@endsection

@section('contenido')
    {{-- Decidimos YA (antes de que se vea nada) si mostramos la intro --}}
    <script>
        try {
            if (!sessionStorage.getItem('intro-vista') && !location.search.includes('menu')) document.body.classList.add('en-intro');
        } catch (e) {}
    </script>

    {{-- 1. Intro --}}
    <div class="intro" id="intro">
        <div id="intro-escena"></div> {{-- acá se monta la escena 3D del jefe --}}
        <div class="vineta"></div>
        <canvas id="intro-lienzo"></canvas>
        <div class="titulo">KERNEL CORRUPTO</div>
        <div class="bajada">un proceso corrupto tomó el sistema</div>
        <div class="start">PRESS START<small>tocá la pantalla · ENTER</small></div>
    </div>

    {{-- 2. Carga --}}
    <div class="carga" id="carga">
        <div class="caja-carga">
            <h2>BOOT // KERNEL_DE_BATALLA</h2>
            <div class="marco" id="carga-log"></div>
            <div class="barra"><div class="barra-relleno" id="carga-barra"></div></div>
            <canvas id="carga-campeones"></canvas>
        </div>
    </div>

    {{-- 3. Menú --}}
    <div class="portada">
        <h1>KERNEL<br>CORRUPTO</h1>
        <div class="bajada">un jefe · toda la clase · un solo sistema</div>

        <canvas id="jefe-portada"></canvas>

        <div class="estado">
            {{ $cantidad }} {{ $cantidad === 1 ? 'proceso conectado' : 'procesos conectados' }}
            @if ($fase > 1)
                · <span class="fase-{{ $fase }}">fase {{ $fase }}: {{ strtolower(\App\Services\Juego::FASES[$fase]['nombre']) }}</span>
            @endif
        </div>

        <nav class="menu">
            @if ($jugador)
                <a class="opcion" href="{{ route('jugar') }}">
                    ¡A LA BATALLA!
                    <small>{{ $jugador['nombre'] }} · {{ $stats['nombre'] }}</small>
                </a>
                <a class="opcion" href="{{ route('perfil') }}">EDITAR PERFIL<small>nombre, color y campeón</small></a>
            @else
                <a class="opcion" href="{{ route('unirse') }}">CREAR JUGADOR<small>elegí nombre, color y campeón</small></a>
            @endif

            <a class="opcion" href="#" data-ayuda>CÓMO JUGAR<small>controles y reglas</small></a>

            @if ($jugador)
                <form method="POST" action="{{ route('salir') }}">
                    @csrf
                    <button class="opcion peligro" type="submit">ABANDONAR PARTIDA<small>borra tu campeón</small></button>
                </form>
            @endif
        </nav>

        <div class="como-jugar marco" id="como-jugar">
            <h2>CÓMO JUGAR</h2>
            <p><kbd>A</kbd> <kbd>D</kbd> moverte entre los 3 carriles.</p>
            <p><kbd>ESPACIO</kbd> atacar. Si pegás cuando el marcador está en la zona verde: ¡crítico!</p>
            <p><kbd>S</kbd> escudo: te protege del rayo del jefe.</p>
            <p><kbd>W</kbd> especial. Si 3 jugadores lo lanzan juntos: ¡ataque combinado!</p>
            <p>Cuando un carril titila en rojo, el jefe va a disparar ahí. Si te pega perdés un ♥, y sin corazones caés unos segundos.</p>
            <p>Cuanto más herido está el jefe, más se enoja: ataca más rápido y en más carriles.</p>
        </div>

        <div class="pie">
            ↑ ↓ para elegir · ENTER para entrar<br>
            <a href="#" id="btn-sonido">🔊 sonido prendido</a> · <a href="{{ route('pantalla') }}">pantalla grande</a>
        </div>
    </div>
@endsection
