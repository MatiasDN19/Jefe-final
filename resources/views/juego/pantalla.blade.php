@extends('juego.layout')

@section('titulo', 'Kernel Corrupto')

@section('vite')
    @vite(['resources/js/pantalla.js', 'resources/js/campo-pantalla.js', 'resources/js/victoria-pantalla.js'])
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

    /* =========== VICTORIA (la secuencia está en resources/js/victoria-pantalla.js) =========== */
    .victoria {
        position: fixed; inset: 0; z-index: 60; display: none; overflow: hidden;
        flex-direction: column; align-items: center; justify-content: flex-start; gap: 18px; padding-top: 3vh;
        background: radial-gradient(ellipse at 50% 30%, rgba(214,64,159,.35), rgba(5,5,12,.95) 60%); text-align: center;
    }
    .victoria.visible { display: flex; animation: flashVictoria .7s steps(5); }
    @keyframes flashVictoria { 0% { background: #fff; } 100% { } }
    .victoria.sacudida { animation: sacudidaV .5s steps(8); }
    @keyframes sacudidaV {
        0%, 100% { transform: none; } 25% { transform: translate(-12px, 8px); }
        50% { transform: translate(10px, -8px); } 75% { transform: translate(-6px, 4px); }
    }
    #v-fuegos { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; image-rendering: pixelated; }

    /* Título 3D: sombras apiladas = extrusión; la línea se balancea en perspectiva */
    #v-titulo { perspective: 700px; position: relative; }
    #v-titulo .linea { display: block; font-family: var(--pixel); line-height: 1.2; color: #fff; white-space: nowrap;
        transform-style: preserve-3d; animation: balanceoV 3.4s ease-in-out infinite; }
    #v-titulo .chica { font-size: clamp(22px, 3vw, 44px); color: var(--cian); }
    #v-titulo .grande { font-size: clamp(40px, 6.5vw, 104px); }
    #v-titulo .letra { display: inline-block; opacity: 0;
        text-shadow: 2px 2px 0 #c01d52, 4px 4px 0 #c01d52, 6px 6px 0 #a3174a, 8px 8px 0 #a3174a,
            10px 10px 0 #7a1240, 12px 12px 0 #7a1240, 14px 14px 0 #4c0d33, 16px 16px 0 #4c0d33,
            20px 24px 26px rgba(0,0,0,.6), 0 0 40px rgba(255,197,61,.45); }
    .victoria.visible #v-titulo .letra { animation: caeLetraV .6s steps(6) forwards, olaV 1.8s ease-in-out infinite;
        animation-delay: calc(var(--i) * 80ms), calc(.8s + var(--i) * 100ms); }
    @keyframes caeLetraV { 0% { opacity: 0; transform: translateY(-260px) scale(2.6) rotate(-25deg); }
        70% { opacity: 1; transform: translateY(10px) scale(.9); } 100% { opacity: 1; transform: none; } }
    @keyframes olaV { 0%, 100% { translate: 0 0; } 50% { translate: 0 -10px; } }
    @keyframes balanceoV { 0%, 100% { transform: rotateX(16deg) rotateY(-10deg); } 50% { transform: rotateX(8deg) rotateY(10deg); } }

    #v-sub { font-family: var(--pixel); font-size: clamp(14px, 1.6vw, 24px); color: #ffc53d; opacity: 0; text-shadow: 3px 3px 0 var(--vacio); }
    #v-sub.ver { opacity: 1; animation: parpadeoTexto .3s steps(2) 3; }

    .v-stats { display: flex; gap: 16px; opacity: 0; position: relative; }
    .v-stats.ver { opacity: 1; animation: entraV .4s steps(4); }
    @keyframes entraV { 0% { transform: translateY(30px); opacity: 0; } 100% { transform: none; opacity: 1; } }
    .v-stats .marco { padding: 10px 20px; min-width: 200px; }
    .v-stats small { display: block; font-size: 20px; color: var(--luz); }
    .v-stats b { font-family: var(--pixel); font-size: 26px; color: #fff; font-weight: normal; }

    /* Podio: los escalones crecen desde el piso y el campeón cae arriba */
    .podio { display: flex; align-items: flex-end; gap: 22px; margin-top: auto; position: relative; }
    .podio .escalon { display: flex; flex-direction: column; align-items: center; width: 240px; }
    .podio .campeon-podio { image-rendering: pixelated; opacity: 0; margin-bottom: -8px; }
    .podio .p1 .campeon-podio { width: 234px; } .podio .p2 .campeon-podio, .podio .p3 .campeon-podio { width: 182px; }
    .podio .nombre { font-family: var(--pixel); font-size: 18px; opacity: 0; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-shadow: 3px 3px 0 var(--vacio); }
    .podio .p1 .nombre { font-size: 24px; }
    .podio .danio { font-size: 22px; color: var(--luz); opacity: 0; margin: 4px 0 8px; }
    .podio .bloque { width: 100%; height: 0; display: flex; justify-content: center; align-items: flex-start; padding-top: 0; overflow: hidden;
        font-family: var(--pixel); font-size: 44px; color: var(--vacio); background: var(--color);
        box-shadow: inset -8px -8px 0 rgba(0,0,0,.3), inset 8px 8px 0 rgba(255,255,255,.35); }
    .podio .sube .bloque { animation: subeBloque .5s steps(6) forwards; }
    .podio .sube .campeon-podio { animation: caeCampeon .5s steps(5) .35s forwards; }
    .podio .sube .nombre, .podio .sube .danio { animation: entraV .3s steps(3) .6s forwards; }
    @keyframes subeBloque { to { height: var(--alto); padding-top: 14px; } }
    @keyframes caeCampeon { 0% { opacity: 0; transform: translateY(-300px); } 80% { opacity: 1; transform: translateY(8px); } 100% { opacity: 1; transform: none; } }

    .v-resto { list-style: none; margin: 0 0 7vh; padding: 0; display: flex; flex-wrap: wrap; justify-content: center; gap: 8px 24px;
        max-width: 80vw; font-size: 22px; color: var(--luz); opacity: 0; position: relative; }
    .v-resto.ver { opacity: 1; animation: entraV .4s steps(4); }
    .v-resto b { font-family: var(--pixel); font-size: 12px; font-weight: normal; margin-right: 6px; }
    .v-resto i { display: inline-block; width: 10px; height: 10px; margin-right: 6px; }
    .v-resto li { white-space: nowrap; }

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
        <canvas id="v-fuegos"></canvas>
        <div id="v-titulo"></div>
        <div id="v-sub">¡MUY BIEN EQUIPO!</div>
        <div class="v-stats" id="v-stats">
            <div class="marco"><small>DAÑO TOTAL</small><b id="v-total">0</b></div>
            <div class="marco"><small>JUGADORES</small><b id="v-jugadores">0</b></div>
            <div class="marco"><small>GOLPE FINAL</small><b id="v-golpe"></b></div>
        </div>
        <div class="podio" id="v-podio"></div>
        <ol class="v-resto" id="v-resto"></ol>
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
            window.VictoriaPantalla?.ocultar();
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
            setTimeout(() => window.VictoriaPantalla?.mostrar(e), 1500); // primero que se vea la explosión
        })
        .listen('.JefeReiniciado', (e) => {
            window.VictoriaPantalla?.ocultar();
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
