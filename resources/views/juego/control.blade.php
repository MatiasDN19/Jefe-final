@extends('juego.layout')

@section('titulo', 'Control')

@section('vite')
    @vite(['resources/js/control.js'])
@endsection

@section('estilos')
    body { overflow: hidden; touch-action: manipulation; }
    .control {
        max-width: 440px; margin: 0 auto; height: 100vh; height: 100dvh; padding: 10px 12px;
        display: flex; flex-direction: column; gap: 8px;
    }
    .yo { display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; }
    .yo .nombre { font-family: var(--pixel); font-size: 11px; }
    .yo .clase { color: var(--luz); font-size: 18px; }
    .punto { display: inline-block; width: 12px; height: 12px; margin-right: 6px; vertical-align: -1px; box-shadow: 2px 2px 0 var(--vacio); }
    .mi-danio { color: var(--luz); text-align: right; }
    .mi-danio strong { color: var(--cian); font-weight: normal; }
    .barra { height: 22px; }
    .vida-txt { font-size: 17px; color: var(--luz); text-align: center; margin-top: -4px; }

    /* El campo de batalla */
    .campo-caja { position: relative; flex: 1; min-height: 0; display: flex; justify-content: center; }
    #campo {
        height: 100%; max-width: 100%; aspect-ratio: 132 / 150;
        image-rendering: pixelated; border: 3px solid var(--violeta); background: var(--vacio);
    }
    #estado { min-height: 22px; text-align: center; color: var(--cian); font-size: 19px; line-height: 1; }

    /* Barra de timing */
    .timing { position: relative; height: 18px; background: var(--vacio); border: 3px solid var(--luz); }
    .timing-zona { position: absolute; top: 0; bottom: 0; background: #46a758; box-shadow: inset 0 -4px 0 rgba(0,0,0,.3); }
    .timing-marcador { position: absolute; top: -6px; bottom: -6px; width: 4px; margin-left: -2px; background: #fff; box-shadow: 2px 0 0 var(--vacio); }
    .timing-marcador.en-zona { background: #ffc53d; }

    /* Botones */
    .botones { display: grid; gap: 8px; }
    .fila-1 { grid-template-columns: 1fr 2.2fr 1fr; }
    .fila-2 { grid-template-columns: 1fr 1fr; }
    .botones .btn-pixel {
        position: relative; overflow: hidden; padding: 12px 6px;
        display: flex; flex-direction: column; align-items: center; gap: 6px;
        -webkit-tap-highlight-color: transparent; user-select: none;
    }
    .botones .btn-pixel small { font-family: var(--terminal); font-size: 16px; opacity: .65; }
    .btn-mover { background: var(--luz); font-size: 22px; }
    #btn-atacar { font-size: 20px; color: var(--vacio); min-height: 74px; }
    .btn-habilidad { font-size: 12px; }
    #btn-escudo { background: var(--cian); }
    #btn-especial { background: #d6409f; color: #fff; }
    .presionado { transform: translate(2px, 2px); filter: brightness(1.3); }

    /* Recarga: una sombra que baja a medida que se carga */
    .btn-habilidad::before {
        content: ''; position: absolute; left: 0; top: 0; width: 100%;
        height: calc((1 - var(--carga, 1)) * 100%);
        background: rgba(5,5,12,.7); pointer-events: none;
    }
    .btn-habilidad.lista { animation: lista .8s steps(2) infinite; }
    @keyframes lista { 50% { filter: brightness(1.25); } }
    .btn-habilidad.sumate { animation: sumate .3s steps(2) infinite; }
    @keyframes sumate { 50% { transform: scale(1.05); filter: brightness(1.6); } }
    .temblar { animation: temblar .25s steps(4); }
    @keyframes temblar { 25% { transform: translateX(-4px); } 75% { transform: translateX(4px); } }

    .arriba { display: flex; gap: 8px; }
    /* Retrato animado del campeón (lo dibuja control.js) */
    #retrato { image-rendering: pixelated; width: 64px; height: 36px; flex: none; border: 3px solid var(--color-jugador); box-shadow: 3px 3px 0 var(--vacio); }
    .yo { gap: 10px; }
    .arriba .yo { min-width: 0; } /* sin esto, flexbox no lo deja achicarse y empuja los botones afuera */
    .yo .datos { flex: 1; min-width: 0; }
    .yo .clase { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 17px; }
    .btns-arriba { display: flex; flex-direction: column; gap: 6px; }
    .btns-arriba .btn-pixel { width: 44px; flex: 1; font-size: 18px; padding: 0; display: flex; align-items: center; justify-content: center; text-decoration: none; background: var(--luz); }

    /* Contador de combo */
    .combo {
        position: absolute; top: 6px; left: 10px; z-index: 20; pointer-events: none; display: none;
        font-family: var(--pixel); color: #fff; text-shadow: 3px 3px 0 var(--vacio);
    }
    .combo.visible { display: block; }
    .combo .num { font-size: 26px; color: var(--color-jugador); }
    .combo small { display: block; font-size: 9px; color: var(--cian); }
    .combo.pop .num { animation: popCombo .18s steps(3); }
    @keyframes popCombo { 50% { transform: scale(1.5); } }
    .combo.grande .num { color: #ffc53d; animation: parpadeoTexto .2s steps(2) infinite; }

    /* Textos grandes en el medio del campo: ¡PERFECTO!, x10 COMBO, etc. */
    .grito {
        position: absolute; left: 50%; top: 42%; z-index: 25; pointer-events: none; white-space: nowrap;
        font-family: var(--pixel); font-size: 22px; color: #fff; text-align: center;
        text-shadow: 3px 3px 0 var(--carmesi), -2px -2px 0 var(--violeta);
        animation: grito .8s steps(8) forwards;
    }
    .grito.perfecto { font-size: 24px; animation: grito .8s steps(8) forwards, arcoiris .2s steps(4) infinite; }
    @keyframes grito {
        0% { transform: translate(-50%, 0) scale(2.5); opacity: 0; }
        20% { transform: translate(-50%, 0) scale(1); opacity: 1; }
        80% { transform: translate(-50%, -10px) scale(1); opacity: 1; }
        100% { transform: translate(-50%, -24px) scale(.9); opacity: 0; }
    }
    @keyframes arcoiris { 0% { color: #ffc53d; } 25% { color: #5ff3ff; } 50% { color: #ff5ad6; } 75% { color: #46ff8a; } }
    .arriba .yo { flex: 1; }
    .btn-menu { display: flex; align-items: center; justify-content: center; width: 52px; font-size: 22px; text-decoration: none; background: var(--luz); }
    .corazones { font-size: 22px; letter-spacing: 2px; line-height: 1; }
    .corazones .lleno { color: var(--carmesi); text-shadow: 2px 2px 0 var(--vacio); }
    .corazones .vacio { color: var(--sombra); }
    .corazones.latido { animation: latido .4s steps(2) 2; }
    @keyframes latido { 50% { transform: scale(1.3); } }
    .fase-txt { text-align: center; font-family: var(--pixel); font-size: 9px; color: var(--luz); min-height: 10px; }
    body.fase-2 { background: radial-gradient(ellipse at 50% 30%, #3a1240 0%, var(--fondo) 60%, var(--vacio) 100%); }
    body.fase-2 .fase-txt { color: #ff8a3d; }
    body.fase-3 { background: radial-gradient(ellipse at 50% 30%, #5a0a24 0%, #14060f 60%, var(--vacio) 100%); }
    body.fase-3 .fase-txt { color: var(--carmesi); animation: parpadeoTexto .5s steps(2) infinite; }
    .hielo.caido { background: rgba(224, 36, 94, .35); }
    .ayuda { text-align: center; font-size: 16px; color: var(--violeta); }
    @media (pointer: coarse) { .ayuda { display: none; } .botones .btn-pixel small { display: none; } }

    /* Hielo encima del campo cuando te congelan */
    .hielo {
        position: absolute; inset: 0; z-index: 30; display: none;
        flex-direction: column; align-items: center; justify-content: center; gap: 10px; text-align: center;
        background: rgba(154, 216, 255, .35);
    }
    .hielo.visible { display: flex; }
    .hielo .msg { font-family: var(--pixel); font-size: 12px; line-height: 1.8; color: #fff; text-shadow: 2px 2px 0 var(--vacio); }
    .hielo .cuenta { font-family: var(--pixel); font-size: 64px; color: #fff; text-shadow: 5px 5px 0 var(--violeta); }
@endsection

@section('contenido')
    <div class="control">
        <div class="arriba" style="--color-jugador: {{ $jugador['color'] }}">
            <div class="yo marco">
                <canvas id="retrato"></canvas>
                <div class="datos">
                    <div class="nombre">{{ $jugador['nombre'] }}</div>
                    <div class="clase">{{ $stats['nombre'] }} · {{ $stats['rol'] }}</div>
                    <div class="corazones" id="corazones"></div>
                </div>
                <div class="mi-danio">daño<br><strong id="mi-danio">{{ $jugador['danio'] }}</strong></div>
            </div>
            <div class="btns-arriba">
                {{-- Volver al menú (también con la tecla Esc) --}}
                <a href="{{ route('portada') }}" class="btn-pixel" title="Menú (Esc)">☰</a>
                <button class="btn-pixel" id="btn-sonido" title="Sonido (M)">🔊</button>
            </div>
        </div>

        <div class="barra @if($vida / $vidaMax < .25) baja @endif" id="barra-caja">
            <div class="barra-relleno" id="barra" style="width: {{ $vida / $vidaMax * 100 }}%"></div>
        </div>
        <div class="vida-txt"><span id="vida">{{ $vida }}</span> / <span id="vida-max">{{ $vidaMax }}</span></div>
        <div class="fase-txt" id="fase-txt"></div>

        <div class="campo-caja" id="campo-caja" style="--color-jugador: {{ $jugador['color'] }}">
            <div class="combo" id="combo"><span class="num" id="combo-num">0</span><small>COMBO</small></div>
            <canvas id="campo"
                    data-campeon="{{ $jugador['campeon'] }}"
                    data-color="{{ $jugador['color'] }}"
                    data-carril="{{ $jugador['carril'] }}"
                    data-muerto="{{ $vida <= 0 ? 1 : 0 }}"
                    data-fase="{{ $fase }}"
                    data-id="{{ $jugador['id'] }}"></canvas>
            <div class="hielo" id="hielo">
                <div class="msg" id="hielo-msg">PROCESO<br>CONGELADO</div>
                <div class="cuenta" id="cuenta">4</div>
            </div>
        </div>

        <div id="estado"></div>

        <div class="timing" id="timing" data-zona="{{ $stats['zona_critico'] }}">
            <div class="timing-zona" id="timing-zona"></div>
            <div class="timing-marcador" id="timing-marcador"></div>
        </div>

        <div class="botones fila-1">
            <button class="btn-pixel btn-mover" id="btn-izq">◀<small>A</small></button>
            <button class="btn-pixel" id="btn-atacar" style="background: {{ $jugador['color'] }}">ATACAR<small>ESPACIO</small></button>
            <button class="btn-pixel btn-mover" id="btn-der">▶<small>D</small></button>
        </div>
        <div class="botones fila-2">
            <button class="btn-pixel btn-habilidad" id="btn-escudo">ESCUDO<small>S</small></button>
            <button class="btn-pixel btn-habilidad" id="btn-especial">ESPECIAL<small>W</small></button>
        </div>
        <div class="ayuda">A/D moverse · ESPACIO atacar · S escudo · W especial</div>
    </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const $ = (id) => document.getElementById(id);
    const csrf = document.querySelector('meta[name=csrf-token]').content;

    const miId     = @json($jugador['id']);
    const miNombre = @json($jugador['nombre']);
    const miColor  = @json($jugador['color']);
    const CARRILES = {{ \App\Services\Juego::CARRILES }};

    const VIDAS    = {{ \App\Services\Juego::VIDAS_JUGADOR }};
    const FASES    = @json(array_map(fn ($f) => $f['nombre'], \App\Services\Juego::FASES));
    let carril      = {{ $jugador['carril'] }};
    let misVidas    = {{ $jugador['vida'] }};
    let jefeMuerto  = {{ $vida <= 0 ? 'true' : 'false' }};
    let congeladoHasta = 0;
    let ultimoAtaque = 0;
    let timerHielo = null;

    // =================================================================
    // Red: todas las acciones son un POST con fetch() (sin recargar)
    // =================================================================
    async function enviar(url, datos = {}) {
        try {
            const resp = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(datos),
            });
            const data = await resp.json();
            // Respuestas comunes a todas las acciones
            if (data.motivo === 'congelado') congelar(data.segundos);
            if (data.motivo === 'jugador_inexistente') window.location = '{{ route('unirse') }}';
            return data;
        } catch (err) {
            mensaje('[ERROR] se cortó la conexión');
            return { ok: false };
        }
    }

    const puedoActuar = () => !jefeMuerto && congeladoHasta < performance.now();

    // =================================================================
    // Acciones
    // =================================================================

    // MOVER: el campeón se mueve YA en pantalla, y el aviso al servidor
    // viaja por detrás. Si esperáramos la respuesta, con wifi lento
    // se movería a los tirones.
    function mover(dir) {
        if (!puedoActuar()) return;
        const nuevo = Math.max(0, Math.min(CARRILES - 1, carril + dir));
        if (nuevo === carril) return;
        carril = nuevo;
        window.Campo?.setCarril(carril);
        window.Sonido?.sonido('mover');
        avisarMovimiento();
    }

    // Un solo "me moví" viajando a la vez. Si te movés de nuevo mientras
    // tanto, cuando vuelve la respuesta mandamos tu carril ACTUAL (el último
    // gana). Así nunca se acumula una fila de movimientos viejos.
    let moviendo = false;
    let movimientoPendiente = false;
    async function avisarMovimiento() {
        if (moviendo) { movimientoPendiente = true; return; }
        moviendo = true;
        do {
            movimientoPendiente = false;
            await enviar('{{ route('mover') }}', { carril });
        } while (movimientoPendiente);
        moviendo = false;
    }

    // ATACAR: el golpe se ve y se escucha YA; el aviso al servidor se junta
    // en una ráfaga (ver despacharGolpes)
    function atacar() {
        const t = performance.now();
        if (!puedoActuar() || t - ultimoAtaque < 160) return; // mismo freno que el servidor
        ultimoAtaque = t;
        navigator.vibrate?.(15);

        const critico = window.Campo?.enZonaCritica() ?? false;
        // ¿Le pegaste justo en el centro de la zona verde? ¡PERFECTO!
        const perfecto = critico && (window.Campo?.precision() ?? 1) < 0.3;
        window.Campo?.atacar(critico);

        // El "feel" del golpe es instantáneo: no esperamos al servidor
        if (perfecto) {
            window.Campo?.hitstop(110, 3);
            window.Sonido?.sonido('perfecto');
            grito('¡PERFECTO!', 'perfecto');
            navigator.vibrate?.([30, 30, 60]);
        } else if (critico) {
            window.Campo?.hitstop(70, 2);
            window.Sonido?.sonido('critico');
        } else {
            window.Sonido?.sonido('golpe');
        }

        golpesPendientes.push(critico);
        despacharGolpes();
    }

    // RÁFAGAS: como mucho UN pedido de ataque viajando a la vez.
    // Los golpes que das mientras tanto se juntan y salen todos en el
    // próximo pedido. Spameando a full son ~3-4 pedidos por segundo en vez
    // de 6+, y el servidor no se atora (tus movimientos pasan rápido).
    let golpesPendientes = [];
    let atacando = false;
    let ultimoEnvio = 0;
    async function despacharGolpes() {
        if (atacando || !golpesPendientes.length) return;
        atacando = true;
        // Respetamos el freno del servidor: no mandamos dos ráfagas a menos de 170 ms
        const espera = 170 - (performance.now() - ultimoEnvio);
        if (espera > 0) await new Promise(r => setTimeout(r, espera));
        ultimoEnvio = performance.now();
        const golpes = golpesPendientes.splice(0); // nos llevamos todos
        const data = await enviar('{{ route('atacar') }}', { golpes });
        if (data.ok) {
            $('mi-danio').textContent = data.miDanio;
            // Un número por cada golpe que contó, apenas escalonados
            data.danios.forEach((d, i) => setTimeout(() => {
                const crit = data.criticos[i];
                numero(crit ? `CRIT -${d}` : `-${d}`, crit ? '#ffc53d' : miColor, crit);
                sumarCombo();
            }, i * 70));
        }
        atacando = false;
        despacharGolpes(); // ¿se juntaron más mientras esperábamos? van ahora
    }

    // =================================================================
    // Combo: golpes seguidos sin que te peguen (se corta si dejás de atacar)
    // =================================================================
    let combo = 0;
    let ultimoAcierto = 0;
    let timerCombo = null;
    function sumarCombo() {
        const ahora = performance.now();
        combo = ahora - ultimoAcierto < 2500 ? combo + 1 : 1;
        ultimoAcierto = ahora;
        const caja = $('combo');
        $('combo-num').textContent = 'x' + combo;
        caja.classList.toggle('visible', combo >= 3);
        caja.classList.toggle('grande', combo >= 25);
        caja.classList.remove('pop');
        void caja.offsetWidth;
        caja.classList.add('pop');
        // Cada 10 golpes, festejo
        if (combo % 10 === 0) {
            grito(combo >= 30 ? '¡IMPARABLE!' : combo >= 20 ? '¡BRUTAL!' : `x${combo} COMBO`);
            window.Sonido?.sonido('combo');
            window.Campo?.hitstop(60, 1.5);
        }
        clearTimeout(timerCombo);
        timerCombo = setTimeout(cortarCombo, 2500);
    }
    function cortarCombo() {
        combo = 0;
        $('combo').classList.remove('visible', 'grande');
    }

    // Texto grande que aparece y se va (¡PERFECTO!, x10 COMBO...)
    function grito(texto, clase = '') {
        const g = document.createElement('div');
        g.className = 'grito ' + clase;
        g.textContent = texto;
        $('campo-caja').appendChild(g);
        setTimeout(() => g.remove(), 800);
    }

    async function escudo() {
        const b = $('btn-escudo');
        if (!puedoActuar()) return;
        if (!estaLista(b)) return temblar(b);

        const data = await enviar('{{ route('escudo') }}');
        if (data.ok) {
            window.Campo?.escudo(data.segundos);
            window.Sonido?.sonido('escudo');
            recargar(b, data.recarga);
            mensaje('> escudo activado');
        } else if (data.motivo === 'recargando') {
            recargar(b, data.faltan, true);
        }
    }

    async function especial() {
        const b = $('btn-especial');
        if (!puedoActuar()) return;
        if (!estaLista(b)) return temblar(b);

        b.classList.remove('sumate');
        const data = await enviar('{{ route('especial') }}');
        if (data.ok) {
            window.Sonido?.sonido('especial');
            window.Campo?.hitstop(80, 2);
            recargar(b, data.recarga);
        } else if (data.motivo === 'recargando') {
            recargar(b, data.faltan, true);
        }
    }

    // =================================================================
    // Recargas de habilidades (barra que se llena sobre el botón)
    // =================================================================
    function recargar(boton, segundos, parcial = false) {
        const total = (parcial ? Number(boton.dataset.total || segundos) : segundos) * 1000;
        const fin = performance.now() + segundos * 1000;
        boton.dataset.listoEn = fin;
        if (!parcial) boton.dataset.total = segundos;
        boton.classList.remove('lista');
        (function paso() {
            const falta = fin - performance.now();
            boton.style.setProperty('--carga', Math.min(1, 1 - Math.max(0, falta) / total));
            if (falta > 0) requestAnimationFrame(paso);
            else boton.classList.add('lista');
        })();
    }
    const estaLista = (b) => !(Number(b.dataset.listoEn || 0) > performance.now());
    function temblar(el) {
        el.classList.remove('temblar');
        void el.offsetWidth;
        el.classList.add('temblar');
    }

    // Recargas pendientes al entrar (por si recargaste la página)
    const ahoraServidor = {{ time() }};
    const faltaEscudo = {{ $jugador['escudo_listo'] }} - ahoraServidor;
    const faltaEspecial = {{ $jugador['especial_listo'] }} - ahoraServidor;
    faltaEscudo > 0 ? recargar($('btn-escudo'), faltaEscudo) : $('btn-escudo').classList.add('lista');
    faltaEspecial > 0 ? recargar($('btn-especial'), faltaEspecial) : $('btn-especial').classList.add('lista');

    // =================================================================
    // Corazones y fases
    // =================================================================
    function pintarCorazones(n, animar = false) {
        misVidas = n;
        const c = $('corazones');
        c.replaceChildren(...Array.from({ length: VIDAS }, (_, i) => {
            const s = document.createElement('span');
            s.className = i < n ? 'lleno' : 'vacio';
            s.textContent = '♥';
            return s;
        }));
        if (animar) { c.classList.remove('latido'); void c.offsetWidth; c.classList.add('latido'); }
    }
    pintarCorazones(misVidas);

    function ponerFase(f) {
        document.body.classList.remove('fase-1', 'fase-2', 'fase-3');
        document.body.classList.add('fase-' + f);
        $('fase-txt').textContent = f > 1 ? `FASE ${f} · ${FASES[f]}` : '';
    }
    ponerFase({{ $fase }});

    // =================================================================
    // Aturdido / caído
    // =================================================================
    function congelar(segundos, texto = 'PROCESO<br>CONGELADO', caido = false) {
        $('hielo-msg').innerHTML = texto; // texto fijo nuestro, no viene de jugadores
        $('hielo').classList.toggle('caido', caido);
        clearInterval(timerHielo);
        congeladoHasta = performance.now() + segundos * 1000;
        window.Campo?.congelar(segundos);
        let restantes = segundos;
        $('cuenta').textContent = restantes;
        $('hielo').classList.add('visible');
        navigator.vibrate?.([200, 100, 200]);
        timerHielo = setInterval(() => {
            restantes--;
            $('cuenta').textContent = restantes;
            if (restantes <= 0) descongelar();
        }, 1000);
    }
    function descongelar() {
        clearInterval(timerHielo);
        if (misVidas === 0) pintarCorazones(VIDAS); // volviste del reinicio con todo
        congeladoHasta = 0;
        window.Campo?.descongelar();
        $('hielo').classList.remove('visible');
    }

    // =================================================================
    // Pequeñas ayudas visuales
    // =================================================================
    function mensaje(texto) { $('estado').textContent = texto; }

    function numero(texto, color, grande) {
        const n = document.createElement('div');
        n.className = 'numero';
        n.textContent = texto;
        n.style.color = color;
        n.style.fontSize = grande ? '22px' : '16px';
        n.style.left = (30 + Math.random() * 30) + '%';
        n.style.top = '25%';
        $('campo-caja').appendChild(n);
        setTimeout(() => n.remove(), 900);
    }

    function actualizarVida(vida, vidaMax) {
        $('barra').style.width = (vida / vidaMax * 100) + '%';
        $('barra-caja').classList.toggle('baja', vida / vidaMax < .25);
        $('vida').textContent = vida;
        $('vida-max').textContent = vidaMax;
    }

    // =================================================================
    // Controles: teclado (compu) y botones (celu)
    // =================================================================
    const teclas = {
        a: ['btn-izq', () => mover(-1)], arrowleft: ['btn-izq', () => mover(-1)],
        d: ['btn-der', () => mover(1)], arrowright: ['btn-der', () => mover(1)],
        ' ': ['btn-atacar', atacar],
        s: ['btn-escudo', escudo], arrowdown: ['btn-escudo', escudo],
        w: ['btn-especial', especial], arrowup: ['btn-especial', especial],
    };

    document.addEventListener('keydown', (ev) => {
        if (ev.key === 'Escape') { window.location = '{{ route('portada') }}'; return; }
        const accion = teclas[ev.key.toLowerCase()];
        if (!accion) return;
        ev.preventDefault();      // que el espacio no haga scroll
        if (ev.repeat) return;    // mantener apretado no cuenta: ¡hay que tocar!
        const [idBoton, fn] = accion;
        $(idBoton).classList.add('presionado');
        setTimeout(() => $(idBoton).classList.remove('presionado'), 100);
        fn();
    });

    // pointerdown en vez de click: en el celu responde más rápido
    [['btn-izq', () => mover(-1)], ['btn-der', () => mover(1)], ['btn-atacar', atacar],
     ['btn-escudo', escudo], ['btn-especial', especial]].forEach(([id, fn]) => {
        $(id).addEventListener('pointerdown', (ev) => { ev.preventDefault(); fn(); });
    });

    // =================================================================
    // ESCUCHAR: lo que manda el servidor por Reverb
    // =================================================================
    // =================================================================
    // Sonido: botón y tecla M
    // =================================================================
    const pintarSonido = () => { $('btn-sonido').textContent = window.Sonido?.estaMudo() ? '🔇' : '🔊'; };
    function alternarSonido() { window.Sonido?.alternarSilencio(); pintarSonido(); window.Sonido?.sonido('tick'); }
    $('btn-sonido').addEventListener('click', alternarSonido);
    document.addEventListener('keydown', (ev) => { if (ev.key.toLowerCase() === 'm') alternarSonido(); });
    pintarSonido();

    // Tus compañeros, que ya estaban jugando cuando entraste
    window.Campo?.fantasmas.cargar(@json($otros));

    window.Echo.channel('jefe')
        .listen('.JugadorUnido', (e) => {
            actualizarVida(e.vida, e.vidaMax);
            window.Campo?.fantasmas.agregar(e.jugador);
            if (e.jugador.id !== miId) mensaje(`> ${e.jugador.nombre} se unió. ¡el jefe se fortaleció!`);
        })
        .listen('.JugadorMovido', (e) => {
            window.Campo?.fantasmas.mover(e.jugadorId, e.carril);
        })
        .listen('.JugadorActualizado', (e) => {
            window.Campo?.fantasmas.actualizar(e.jugador);
        })
        .listen('.JefeGolpeado', (e) => {
            actualizarVida(e.vida, e.vidaMax);
            window.Campo?.golpeJefe(e.color);
            if (e.jugadorId !== miId) window.Campo?.fantasmas.ataca(e.jugadorId, e.critico);
        })
        .listen('.EspecialLanzado', (e) => {
            actualizarVida(e.vida, e.vidaMax);
            window.Campo?.golpeJefe(e.color);
            if (e.jugadorId !== miId) window.Campo?.fantasmas.ataca(e.jugadorId, true);
            if (e.combo) {
                window.Campo?.combo();
                window.Campo?.hitstop(150, 4);
                window.Sonido?.sonido('perfecto');
                grito('¡ATAQUE COMBINADO!', 'perfecto');
                navigator.vibrate?.([80, 40, 80, 40, 200]);
                mensaje(`>> ¡ATAQUE COMBINADO! -${e.combo.danio}`);
                $('btn-especial').classList.remove('sumate');
            } else if (e.jugadorId !== miId) {
                mensaje(`> ${e.nombre} lanzó su especial (${e.listos}/${e.necesarios}) ¡sumate!`);
                const b = $('btn-especial');
                if (estaLista(b)) {
                    b.classList.add('sumate');
                    setTimeout(() => b.classList.remove('sumate'), 3000);
                }
            } else {
                mensaje(`> especial lanzado (${e.listos}/${e.necesarios})`);
            }
        })
        .listen('.AtaquePreparado', (e) => {
            window.Campo?.preparar(e.carriles, e.segundos);
            if (e.carriles.includes(carril)) {
                navigator.vibrate?.([60, 60, 60]);
                window.Sonido?.sonido('alerta');
                mensaje('⚠ ¡el jefe apunta a TU carril! movete o usá el escudo');
            } else {
                mensaje('⚠ el jefe está apuntando...');
            }
        })
        .listen('.ImpactoJefe', (e) => {
            // Re-sincronizamos a los compañeros (a vos no: tu posición la manejás vos)
            Object.entries(e.posiciones || {}).forEach(([id, carril]) => {
                if (id !== miId) window.Campo?.fantasmas.mover(id, carril);
            });
            window.Campo?.impacto(e.carriles);
            e.golpeados.forEach(j => window.Campo?.fantasmas.golpeado(j.id));
            const yo = e.golpeados.find(j => j.id === miId);
            if (yo) {
                window.Campo?.golpeado(yo.caido, yo.segundos);
                window.Sonido?.sonido(yo.caido ? 'caer' : 'danio');
                cortarCombo(); // ¡te pegaron! se corta el combo
                pintarCorazones(yo.vida, true);
                navigator.vibrate?.(yo.caido ? [300, 100, 300] : [150]);
                if (yo.caido) {
                    congelar(yo.segundos, 'TE QUEDASTE<br>SIN VIDA<br><small>reiniciando...</small>', true);
                    mensaje('> caíste. volvés con todos tus corazones');
                } else {
                    congelar(yo.segundos, '-1 ♥<br>ATURDIDO');
                    mensaje(`> te pegó el rayo. te quedan ${yo.vida} ♥`);
                }
            } else if (e.bloqueados.some(j => j.id === miId)) {
                window.Sonido?.sonido('bloqueo');
                grito('¡BLOQUEADO!');
                mensaje('> ¡tu escudo bloqueó el ataque!');
            } else if (e.golpeados.length) {
                mensaje(`> el rayo alcanzó a: ${e.golpeados.map(j => j.nombre).join(', ')}`);
            } else {
                mensaje('> ¡nadie fue alcanzado!');
            }
        })
        .listen('.FaseCambiada', (e) => {
            ponerFase(e.fase);
            window.Campo?.setFase(e.fase);
            window.Sonido?.sonido('fase');
            grito(`FASE ${e.fase}`);
            navigator.vibrate?.([100, 50, 100, 50, 300]);
            mensaje(`>> FASE ${e.fase}: el jefe está ${e.nombre.toLowerCase()}`);
        })
        .listen('.JugadorSalio', (e) => {
            actualizarVida(e.vida, e.vidaMax);
            window.Campo?.fantasmas.quitar(e.jugadorId);
        })
        .listen('.NuevaPartida', () => {
            // Se borraron todos los perfiles: volvemos al menú
            window.location = '{{ route('portada') }}';
        })
        .listen('.JefeDerrotado', (e) => {
            actualizarVida(0, 1);
            jefeMuerto = true;
            descongelar();
            window.Campo?.jefeMuere();
            window.Sonido?.sonido('victoria');
            grito('¡VICTORIA!', 'perfecto');
            cortarCombo();
            const puesto = e.ranking.findIndex(j => j.nombre === miNombre) + 1;
            mensaje(`> proceso eliminado. quedaste #${puesto}`);
        })
        .listen('.JefeReiniciado', (e) => {
            actualizarVida(e.vida, e.vidaMax);
            jefeMuerto = false;
            descongelar();
            window.Campo?.jefeRevive();
            window.Campo?.setFase(1);
            ponerFase(1);
            pintarCorazones(VIDAS);
            $('mi-danio').textContent = 0;
            ['btn-escudo', 'btn-especial'].forEach(id => { $(id).dataset.listoEn = 0; $(id).style.setProperty('--carga', 1); $(id).classList.add('lista'); });
            mensaje('> el jefe se reinició...');
        });
});
</script>
@endsection
