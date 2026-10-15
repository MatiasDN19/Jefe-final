@extends('juego.layout')

@section('titulo', 'Conectarse')

@section('vite')
    @vite(['resources/js/unirse.js'])
@endsection

@section('estilos')
    .caja { max-width: 720px; margin: 0 auto; padding: 28px 16px; display: flex; flex-direction: column; gap: 22px; }
    .titulo { text-align: center; }
    .titulo h1 { margin: 0 0 12px; font-size: 20px; line-height: 1.5; color: var(--cian); text-shadow: 3px 3px 0 var(--carmesi); }
    .titulo p { margin: 0; color: var(--luz); }
    .terminal { padding: 18px; display: flex; flex-direction: column; gap: 18px; }
    .linea { color: var(--cian); }
    .linea::before { content: '> '; color: var(--carmesi); }
    .etiqueta { display: block; margin-bottom: 8px; color: var(--luz); }
    input[type=text] {
        width: 100%; padding: 12px; font-family: var(--terminal); font-size: 28px;
        color: var(--cian); background: var(--vacio); border: 3px solid var(--violeta); outline: none;
        caret-color: var(--carmesi);
    }
    input[type=text]:focus { border-color: var(--cian); }
    .colores { display: grid; grid-template-columns: repeat(8, 1fr); gap: 8px; }
    .colores input, .campeones input { display: none; }
    .colores span {
        display: block; aspect-ratio: 1; cursor: pointer;
        border: 4px solid var(--vacio); box-shadow: 3px 3px 0 var(--vacio);
    }
    .colores input:checked + span { border-color: #fff; transform: translate(-2px, -2px); box-shadow: 5px 5px 0 var(--vacio); }

    /* Tarjetas de campeón */
    .campeones { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    .tarjeta {
        display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%;
        padding: 12px 8px; cursor: pointer; text-align: center;
        background: var(--vacio); border: 3px solid var(--sombra);
    }
    .tarjeta canvas { image-rendering: pixelated; width: 100%; max-width: 96px; }
    .tarjeta .nombre { font-family: var(--pixel); font-size: 11px; color: var(--texto); }
    .tarjeta .rol { font-size: 18px; color: var(--carmesi); }
    .tarjeta .pasiva { font-size: 17px; line-height: 1.1; color: var(--luz); }
    .campeones input:checked + .tarjeta { border-color: var(--cian); background: var(--contorno); box-shadow: inset 0 0 0 3px var(--violeta); }
    .campeones input:checked + .tarjeta .nombre { color: var(--cian); }

    .error { color: var(--carmesi); }
    .error::before { content: '[ERROR] '; }
    .btn-pixel { width: 100%; font-size: 18px; }
    .volver { text-align: center; color: var(--luz); }
    .cursor { animation: parpadeo 1s steps(2) infinite; }
    @keyframes parpadeo { 50% { opacity: 0; } }

    @media (max-width: 560px) {
        .colores { grid-template-columns: repeat(4, 1fr); }
        .campeones { gap: 6px; }
        .tarjeta .pasiva { display: none; } /* en el celu, solo nombre y rol */
    }
@endsection

@section('contenido')
    {{-- El mismo formulario sirve para unirse y para editar el perfil --}}
    @php
        $editando = (bool) $jugador;
        $nombreActual = old('nombre', $jugador['nombre'] ?? '');
        $colorActual = old('color', $jugador['color'] ?? '#ffc53d');
        $campeonActual = old('campeon', $jugador['campeon'] ?? 'firewall');
    @endphp
    <form class="caja" method="POST" action="{{ $editando ? route('perfil') : route('unirse') }}">
        @csrf

        <div class="titulo">
            <h1>{{ \App\Services\Juego::NOMBRE_JEFE }}</h1>
            <p>{{ $editando ? 'Cambiá tu nombre, color o campeón.' : 'Un proceso corrupto tomó el sistema. Elegí tu campeón.' }}</p>
        </div>

        <div class="terminal marco">
            <div class="linea">{{ $editando ? 'editar_perfil' : 'iniciar_sesion --batalla' }}<span class="cursor">_</span></div>

            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif

            <div>
                <label class="etiqueta" for="nombre">usuario:</label>
                <input type="text" id="nombre" name="nombre" maxlength="16"
                       value="{{ $nombreActual }}" placeholder="mati" required autofocus autocomplete="off">
            </div>

            <div>
                <span class="etiqueta">campeón:</span>
                <div class="campeones">
                    @foreach ($campeones as $clave => $c)
                        <label>
                            <input type="radio" name="campeon" value="{{ $clave }}" @checked($campeonActual === $clave)>
                            <div class="tarjeta">
                                <canvas data-campeon="{{ $clave }}"></canvas>
                                <div class="nombre">{{ $c['nombre'] }}</div>
                                <div class="rol">{{ $c['rol'] }}</div>
                                <div class="pasiva">{{ $c['pasiva'] }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <span class="etiqueta">color:</span>
                <div class="colores">
                    @foreach (['#e5484d', '#f76b15', '#ffc53d', '#46a758', '#12a594', '#0090ff', '#d6409f', '#ffffff'] as $c)
                        <label>
                            <input type="radio" name="color" value="{{ $c }}" @checked($colorActual === $c)>
                            <span style="background: {{ $c }}"></span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <button type="submit" class="btn-pixel">{{ $editando ? 'GUARDAR' : 'CONECTARSE' }}</button>
        <a href="{{ route('portada') }}" class="volver">← volver al menú</a>
    </form>
@endsection
