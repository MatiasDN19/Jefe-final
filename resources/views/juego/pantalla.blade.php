@extends('juego.layout')

@section('titulo', 'Kernel Corrupto')

@section('vite')
    @vite(['resources/js/pantalla.js', 'resources/js/campo-pantalla.js'])
@endsection

@section('estilos')
    body { overflow: hidden; }
    .pantalla {
        height: 100vh; padding: 20px; display: grid; gap: 20px;
        grid-template-columns: 1fr 320px; grid-template-rows: auto 1fr;
    }

    /* HUD de arriba: nombre del jefe + barra */
    .hud { grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; gap: 10px; }
    .hud h1 { margin: 0; font-size: 26px; color: var(--texto); text-shadow: 4px 4px 0 var(--carmesi), -2px -2px 0 var(--violeta); letter-spacing: 3px; }
    .hud .barra { max-width: 900px; height: 40px; }
    .hud .barra::after { background: repeating-linear-gradient(to right, transparent 0 34px, var(--vacio) 34px 40px); }
    .vida-txt { font-family: var(--pixel); font-size: 14px; color: var(--luz); }

    /* La escena 3D */
    .arena { position: relative; min-height: 0; }
    #escena { position: absolute; inset: 0; }
    #campo-pantalla { position: absolute; inset: 0; width: 100%; height: 100%; z-index: 2; image-rendering: pixelated; pointer-events: none; }

    /* Panel lateral */
    .lateral { display: flex; flex-direction: column; gap: 18px; min-height: 0; }
    .panel { padding: 16px; }
    .panel h2 { margin: 0 0 14px; font-size: 12px; color: var(--cian); }
    ol { margin: 0; padding: 0; list-style: none; counter-reset: pos; display: flex; flex-direction: column; gap: 8px; }
    ol li { counter-increment: pos; display: flex; align-items: center; font-size: 24px; }
    ol li::before { content: counter(pos); font-family: var(--pixel); font-size: 12px; color: var(--luz); width: 26px; }
    .punto { display: inline-block; width: 14px; height: 14px; margin-right: 10px; flex: none; box-shadow: 2px 2px 0 var(--vacio); }
    .panel-feed { flex: 1; min-height: 0; overflow: hidden; }
    #feed { display: flex; flex-direction: column; gap: 4px; font-size: 21px; color: var(--luz); }
    #feed div::before { content: '> '; color: var(--carmesi); }

    /* Aviso del contraataque */
    .aviso-hielo {
        position: absolute; top: 8%; left: 50%; z-index: 10;
        font-family: var(--pixel); font-size: 22px; color: var(--hielo); text-align: center; white-space: nowrap;
        text-shadow: 4px 4px 0 var(--vacio); pointer-events: none;
        animation: aparecer 2.5s steps(10) forwards;
    }
    @keyframes aparecer {
        0% { opacity: 0; transform: translateX(-50%) scale(.5); }
        15%, 80% { opacity: 1; transform: translateX(-50%) scale(1); }
        100% { opacity: 0; transform: translateX(-50%) scale(1); }
    }

    /* Cartel del ataque combinado */
    .aviso-combo {
        position: absolute; top: 30%; left: 50%; z-index: 10; text-align: center; pointer-events: none;
        font-family: var(--pixel); font-size: 34px; line-height: 1.5; color: #fff; white-space: nowrap;
        text-shadow: 5px 5px 0 #d6409f, -3px -3px 0 var(--vacio);
        animation: aparecer 2.8s steps(12) forwards;
    }
    .aviso-combo small { display: block; font-family: var(--terminal); font-size: 30px; color: var(--cian); text-shadow: 3px 3px 0 var(--vacio); }

    /* Destello de pantalla completa en los críticos */
    .flash { position: fixed; inset: 0; pointer-events: none; z-index: 50; opacity: 0; }
    .flash.activo { animation: flash .25s steps(3); }
    @keyframes flash { 0% { opacity: .5; } 100% { opacity: 0; } }

    /* Victoria */
    .victoria {
        position: fixed; inset: 0; z-index: 60; display: none;
        flex-direction: column; align-items: center; justify-content: center; gap: 26px;
        background: rgba(5, 5, 12, .88); text-align: center;
    }
    .victoria.visible { display: flex; animation: glitch .4s steps(3) 1; }
    .victoria h1 { margin: 0; font-size: 44px; line-height: 1.4; color: var(--cian); text-shadow: 5px 5px 0 var(--carmesi); }
    .victoria .sub { font-family: var(--pixel); font-size: 18px; color: var(--texto); }
    #golpe-final { font-size: 28px; color: var(--luz); }
    .podio { display: flex; align-items: flex-end; gap: 16px; }
    .podio .escalon { display: flex; flex-direction: column; align-items: center; gap: 10px; width: 200px; }
    .podio .bloque { width: 100%; display: flex; align-items: flex-start; justify-content: center; padding-top: 12px;
        font-family: var(--pixel); font-size: 28px; color: var(--vacio);
        box-shadow: inset -6px -6px 0 rgba(0,0,0,.3), inset 6px 6px 0 rgba(255,255,255,.3); }
    .podio .nombre { font-size: 28px; }
    .podio .danio { font-size: 20px; color: var(--luz); }

    .admin { position: fixed; right: 16px; bottom: 16px; z-index: 70; display: flex; gap: 8px; opacity: .45; }
    .admin:hover { opacity: 1; }
    .admin .btn-pixel { font-size: 10px; padding: 10px 12px; }
    .admin .peligro { background: var(--carmesi); color: #fff; }

    /* Fases: el fondo y el título cambian a medida que el jefe se enoja */
    .fase-hud { font-family: var(--pixel); font-size: 11px; color: var(--luz); min-height: 12px; }
    body { transition: background 1s; }
    body.fase-2 { background: radial-gradient(ellipse at 50% 40%, #3a1240 0%, var(--fondo) 55%, var(--vacio) 100%); }
    body.fase-2 .fase-hud { color: #ff8a3d; }
    body.fase-2 .hud h1 { text-shadow: 4px 4px 0 #ff5a1f, -2px -2px 0 var(--carmesi); }
    body.fase-3 { background: radial-gradient(ellipse at 50% 40%, #5a0a24 0%, #14060f 55%, var(--vacio) 100%); }
    body.fase-3 .fase-hud { color: var(--carmesi); animation: parpadeoTexto .5s steps(2) infinite; }
    body.fase-3 .hud h1 { color: #ffd0d8; text-shadow: 4px 4px 0 var(--carmesi), -3px -3px 0 #ff2a2a; animation: glitch 1.2s steps(3) infinite; }
    .aviso-fase {
        position: fixed; inset: 0; z-index: 55; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 18px;
        background: rgba(90, 10, 36, .55); pointer-events: none; text-align: center;
        animation: aparecerFase 3s steps(12) forwards;
    }
    .aviso-fase .num { font-family: var(--pixel); font-size: 64px; color: #fff; text-shadow: 6px 6px 0 var(--carmesi); }
    .aviso-fase .nom { font-family: var(--pixel); font-size: 22px; color: #ff8a3d; text-shadow: 3px 3px 0 var(--vacio); }
    .aviso-fase .sub { font-size: 30px; color: var(--texto); }
    @keyframes aparecerFase { 0% { opacity: 0; } 10%, 80% { opacity: 1; } 100% { opacity: 0; } }
@endsection

@section('contenido')
    <div class="pantalla">
        <div class="hud">
            <h1>{{ \App\Services\Juego::NOMBRE_JEFE }}</h1>
            <div class="barra @if($vida / $vidaMax < .25) baja @endif" id="barra-caja">
                <div class="barra-relleno" id="barra" style="width: {{ $vida / $vidaMax * 100 }}%"></div>
            </div>
            <div class="vida-txt"><span id="vida">{{ $vida }}</span> / <span id="vida-max">{{ $vidaMax }}</span></div>
            <div class="fase-hud" id="fase-hud"></div>
        </div>

        <div class="arena" id="arena">
            {{-- Acá adentro pantalla.js crea el canvas de Three.js --}}
            <div id="escena" data-vida="{{ $vida }}" data-vida-max="{{ $vidaMax }}" data-fase="{{ $fase }}"></div>
            {{-- Y encima, campo-pantalla.js dibuja a los campeones de todos --}}
            <canvas id="campo-pantalla"></canvas>
        </div>

        <div class="lateral">
            <div class="panel marco">
                <h2>TOP DAÑO</h2>
                <ol id="ranking">
                    @foreach ($ranking as $j)
                        <li><span class="punto" style="background: {{ $j['color'] }}"></span>{{ $j['nombre'] }} · {{ $j['danio'] }}</li>
                    @endforeach
                </ol>
            </div>
            <div class="panel panel-feed marco">
                <h2>LOG DEL SISTEMA</h2>
                <div id="feed"><div>{{ count($jugadores) }} procesos conectados</div></div>
            </div>
        </div>
    </div>

    <div class="flash" id="flash"></div>

    <div class="victoria" id="victoria">
        <h1>PROCESO<br>ELIMINADO</h1>
        <div class="sub">¡Muy bien equipo!</div>
        <div id="golpe-final"></div>
        <div class="podio" id="podio"></div>
    </div>

    {{-- Botones para quien presenta --}}
    <div class="admin">
        <button id="btn-reiniciar" class="btn-pixel">REINICIAR JEFE</button>
        <button id="btn-nueva" class="btn-pixel peligro">NUEVA PARTIDA</button>
    </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const $ = (id) => document.getElementById(id);
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const arena = $('arena');

    // Los jugadores que ya estaban conectados cuando se abrió la pantalla
    window.CampoPantalla?.cargar(@json($jugadores));

    // Ojo: los nombres los escribe cada jugador, por eso SIEMPRE usamos
    // textContent y nunca innerHTML con ellos. Si no, alguien podría poner
    // "<script>..." como nombre y ejecutar código en la pantalla (XSS).
    function itemJugador(j, texto) {
        const li = document.createElement('li');
        const punto = document.createElement('span');
        punto.className = 'punto';
        punto.style.background = j.color;
        li.append(punto, document.createTextNode(texto));
        return li;
    }

    function pintarRanking(lista) {
        $('ranking').replaceChildren(...lista.map(j => itemJugador(j, `${j.nombre} · ${j.danio}`)));
    }

    function agregarAlFeed(texto, color) {
        const d = document.createElement('div');
        d.textContent = texto;
        if (color) d.style.color = color;
        $('feed').prepend(d);
        while ($('feed').children.length > 14) $('feed').lastChild.remove();
    }

    function actualizarVida(vida, vidaMax) {
        $('barra').style.width = (vida / vidaMax * 100) + '%';
        $('barra-caja').classList.toggle('baja', vida / vidaMax < .25);
        $('vida').textContent = vida;
        $('vida-max').textContent = vidaMax;
        window.Jefe3D?.setVida(vida / vidaMax);
    }

    function destello(color) {
        $('flash').style.background = color;
        $('flash').classList.remove('activo');
        void $('flash').offsetWidth; // truco para reiniciar la animación
        $('flash').classList.add('activo');
    }

    function cartel(clase, titulo, subtitulo, ms = 2600) {
        const d = document.createElement('div');
        d.className = clase;
        d.textContent = titulo;
        if (subtitulo) {
            const s = document.createElement('small');
            s.textContent = subtitulo;
            d.append(s);
        }
        arena.appendChild(d);
        setTimeout(() => d.remove(), ms);
    }

    function mostrarNumero(texto, color, grande) {
        const n = document.createElement('div');
        n.className = 'numero';
        n.textContent = texto;
        n.style.color = color;
        n.style.fontSize = grande ? '28px' : '16px';
        n.style.left = (30 + Math.random() * 35) + '%';
        n.style.top  = (12 + Math.random() * 35) + '%';
        arena.appendChild(n);
        setTimeout(() => n.remove(), 900);
    }

    function mostrarVictoria(e) {
        $('golpe-final').textContent = `golpe final: ${e.golpeFinal}`;
        const alturas = [180, 130, 90];
        const orden = [1, 0, 2].filter(i => e.ranking[i]); // podio: 2º - 1º - 3º
        $('podio').replaceChildren(...orden.map(i => {
            const j = e.ranking[i];
            const escalon = document.createElement('div');
            escalon.className = 'escalon';
            const nombre = document.createElement('div');
            nombre.className = 'nombre';
            nombre.textContent = j.nombre;
            const danio = document.createElement('div');
            danio.className = 'danio';
            danio.textContent = `${j.danio} de daño`;
            const bloque = document.createElement('div');
            bloque.className = 'bloque';
            bloque.style.height = alturas[i] + 'px';
            bloque.style.background = j.color;
            bloque.textContent = i + 1;
            escalon.append(nombre, danio, bloque);
            return escalon;
        }));
        $('victoria').classList.add('visible');
    }

    const CARRIL_TXT = ['A LA IZQUIERDA', 'AL CENTRO', 'A LA DERECHA'];
    const FASES = @json(array_map(fn ($f) => $f['nombre'], \App\Services\Juego::FASES));
    const DESCRIPCION_FASE = { 2: 'ataca más rápido', 3: 'ataca dos carriles a la vez' };

    function ponerFase(f) {
        document.body.classList.remove('fase-1', 'fase-2', 'fase-3');
        document.body.classList.add('fase-' + f);
        $('fase-hud').textContent = f > 1 ? `FASE ${f} · ${FASES[f]}` : '';
    }
    ponerFase({{ $fase }});

    function textoCarriles(carriles) {
        return carriles.length > 1 ? '¡EL JEFE APUNTA A DOS CARRILES!' : `¡EL JEFE APUNTA ${CARRIL_TXT[carriles[0]]}!`;
    }

    window.Echo.channel('jefe')
        .listen('.JugadorUnido', (e) => {
            window.CampoPantalla?.agregar(e.jugador);
            actualizarVida(e.vida, e.vidaMax);
            agregarAlFeed(`${e.jugador.nombre} se conectó. el jefe se fortalece`, e.jugador.color);
        })
        .listen('.JugadorMovido', (e) => {
            window.CampoPantalla?.mover(e.jugadorId, e.carril);
        })
        .listen('.JefeGolpeado', (e) => {
            actualizarVida(e.vida, e.vidaMax);
            pintarRanking(e.ranking);
            window.CampoPantalla?.atacar(e.jugadorId, e.critico, e.color);
            window.Jefe3D?.golpe({ color: e.color, critico: e.critico });
            const veces = e.golpes > 1 ? `x${e.golpes} ` : '';
            mostrarNumero(e.critico ? `${e.nombre} CRIT ${veces}-${e.danio}` : `${veces}-${e.danio}`, e.color, e.critico);
            if (e.critico) destello(e.color);
        })
        .listen('.EscudoActivado', (e) => {
            window.CampoPantalla?.escudo(e.jugadorId, e.segundos);
        })
        .listen('.EspecialLanzado', (e) => {
            actualizarVida(e.vida, e.vidaMax);
            pintarRanking(e.ranking);
            window.CampoPantalla?.atacar(e.jugadorId, true, e.color);
            window.Jefe3D?.golpe({ color: e.color, critico: true });
            if (e.combo) {
                window.CampoPantalla?.combo(e.combo.nombres);
                window.Jefe3D?.combo();
                destello('#d6409f');
                cartel('aviso-combo', '¡ATAQUE COMBINADO!', `${e.combo.nombres.join(' + ')}  -${e.combo.danio}`, 2800);
                agregarAlFeed(`COMBO de ${e.combo.nombres.length}: -${e.combo.danio}`, '#d6409f');
            } else {
                agregarAlFeed(`${e.nombre} lanzó su especial (${e.listos}/${e.necesarios})`, e.color);
            }
        })
        .listen('.AtaquePreparado', (e) => {
            window.CampoPantalla?.preparar(e.carriles, e.segundos);
            cartel('aviso-hielo', textoCarriles(e.carriles), null, e.segundos * 1000);
        })
        .listen('.ImpactoJefe', (e) => {
            // Primero acomodamos a cada uno donde el servidor dice que está
            // (por si algún "se movió" llegó tarde o se perdió)
            Object.entries(e.posiciones || {}).forEach(([id, carril]) => window.CampoPantalla?.mover(id, carril));
            window.CampoPantalla?.impacto(e.carriles, e.golpeados, e.bloqueados);
            window.Jefe3D?.ataqueCarril();
            destello('#9ad8ff');
            const caidos = e.golpeados.filter(j => j.caido);
            const heridos = e.golpeados.filter(j => !j.caido);
            if (heridos.length) agregarAlFeed(`-1 ♥: ${heridos.map(j => j.nombre).join(', ')}`, '#9ad8ff');
            if (caidos.length) agregarAlFeed(`cayeron: ${caidos.map(j => j.nombre).join(', ')}`, '#e0245e');
            if (e.bloqueados.length) agregarAlFeed(`bloquearon con escudo: ${e.bloqueados.map(j => j.nombre).join(', ')}`, '#5ff3ff');
            if (!e.golpeados.length && !e.bloqueados.length) agregarAlFeed('¡todos esquivaron!', '#46a758');
        })
        .listen('.FaseCambiada', (e) => {
            ponerFase(e.fase);
            window.Jefe3D?.setFase(e.fase);
            destello('#e0245e');
            agregarAlFeed(`FASE ${e.fase}: ${e.nombre}`, '#ff8a3d');
            // Cartel grande de pantalla completa
            const d = document.createElement('div');
            d.className = 'aviso-fase';
            [['num', `FASE ${e.fase}`], ['nom', e.nombre], ['sub', `el jefe ${DESCRIPCION_FASE[e.fase] ?? 'se enoja'}`]].forEach(([c, txt]) => {
                const el = document.createElement('div');
                el.className = c;
                el.textContent = txt;
                d.append(el);
            });
            document.body.append(d);
            setTimeout(() => d.remove(), 3000);
        })
        .listen('.JugadorSalio', (e) => {
            window.CampoPantalla?.quitar(e.jugadorId);
            actualizarVida(e.vida, e.vidaMax);
            agregarAlFeed(`${e.nombre} abandonó la batalla`);
        })
        .listen('.JugadorActualizado', (e) => {
            window.CampoPantalla?.actualizar(e.jugador);
            agregarAlFeed(`${e.jugador.nombre} cambió su perfil`, e.jugador.color);
        })
        .listen('.NuevaPartida', (e) => {
            $('victoria').classList.remove('visible');
            window.CampoPantalla?.limpiar();
            window.Jefe3D?.revivir();
            window.Jefe3D?.setFase(1, false);
            ponerFase(1);
            actualizarVida(e.vida, e.vidaMax);
            $('ranking').replaceChildren();
            $('feed').replaceChildren();
            agregarAlFeed('nueva partida: esperando jugadores...', '#5ff3ff');
        })
        .listen('.JefeDerrotado', (e) => {
            actualizarVida(0, 1);
            window.Jefe3D?.morir();
            destello('#ffffff');
            agregarAlFeed(`proceso eliminado por ${e.golpeFinal}`, '#5ff3ff');
            setTimeout(() => mostrarVictoria(e), 1500); // primero que se vea la explosión
        })
        .listen('.JefeReiniciado', (e) => {
            $('victoria').classList.remove('visible');
            window.Jefe3D?.revivir();
            window.Jefe3D?.setFase(1, false);
            ponerFase(1);
            window.CampoPantalla?.reiniciar();
            actualizarVida(e.vida, e.vidaMax);
            $('ranking').replaceChildren();
            agregarAlFeed('el jefe se está reiniciando...', '#e0245e');
        });

    const enviar = (url) => fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
    $('btn-reiniciar').addEventListener('click', () => enviar('{{ route('reiniciar') }}'));
    $('btn-nueva').addEventListener('click', () => {
        // confirm() bloquea la página, pero acá está bien: es para quien presenta
        if (confirm('¿Borrar a TODOS los jugadores y empezar de cero?')) enviar('{{ route('nueva-partida') }}');
    });
});
</script>
@endsection
