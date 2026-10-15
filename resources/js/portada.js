/**
 * La entrada al juego, en tres pasos:
 *
 *   1. INTRO:  "PRESS START" con el jefe gigante, brasas y relámpagos.
 *   2. CARGA:  arranque del sistema estilo terminal. Espera DE VERDAD a que
 *              Echo se conecte a Reverb (el WebSocket) antes de terminar.
 *   3. MENÚ:   crear jugador primero; después, a la batalla.
 *
 * La intro se ve una vez por sesión (sessionStorage). Si volvés al menú
 * desde el juego, vas directo al menú.
 */
import { FILAS, ANCHO, ALTO, PALETA } from './jefe-sprite';
import { dibujarCampeon } from './campeones';
import { crearEscena } from './pantalla'; // ¡la misma escena 3D de la pantalla grande!
import { sonido, estaMudo, alternarSilencio } from './sonidos';

const $ = (id) => document.getElementById(id);

// ---------------------------------------------------------------------------
// Ayudas
// ---------------------------------------------------------------------------

// sessionStorage puede fallar (modo incógnito estricto, etc.): siempre con try/catch
const recordar = {
    get: (k) => { try { return sessionStorage.getItem(k); } catch { return null; } },
    set: (k, v) => { try { sessionStorage.setItem(k, v); } catch { /* sin memoria, no pasa nada */ } },
};

// Dibuja al jefe (con tentáculos que ondulan) en cualquier canvas
function dibujarJefe(ctx, x0, y0, tam, t, { brillo = 1, ojoRojo = false } = {}) {
    const ojoX = Math.round(Math.sin(t * 0.8) * Math.sin(t * 2.3));
    const parpadea = t % 4 < 0.12;
    FILAS.forEach((fila, j) => {
        const onda = Math.max(0, j - 13) / 13;
        const corr = Math.round(Math.sin(t * 3 - j * 0.5) * onda * 2);
        [...fila].forEach((letra, i) => {
            if (letra === '.') return;
            const esOjo = (letra === 'C' || letra === 'W') && j <= 11;
            let x = x0 + (i + corr) * tam;
            const y = y0 + j * tam;
            if (esOjo) {
                ctx.fillStyle = PALETA.D;
                ctx.fillRect(x, y, tam, tam);
                if (parpadea) return;
                x += ojoX * tam;
                ctx.fillStyle = ojoRojo ? '#ff2a4a' : PALETA[letra];
            } else {
                ctx.fillStyle = PALETA[letra];
            }
            ctx.globalAlpha = brillo;
            ctx.fillRect(x, y, tam, tam);
            ctx.globalAlpha = 1;
        });
    });
}

// ---------------------------------------------------------------------------
// 1. INTRO: PRESS START
// ---------------------------------------------------------------------------

function intro() {
    return new Promise((listo) => {
        const capa = $('intro');
        capa.classList.add('visible');

        // 1) La escena 3D: arranca con el jefe desarmado y la cámara pegada al ojo
        const escena = crearEscena($('intro-escena'), { intro: true });

        // 2) Línea de tiempo de la intro
        setTimeout(() => escena.revivir(), 400);              // los cubos vuelan y se arman
        setTimeout(() => {                                     // ¡armado!: estallido + título
            escena.contraataque();
            capa.classList.add('con-titulo', 'sacudida');
            sonido('impacto');
            setTimeout(() => capa.classList.remove('sacudida'), 500);
        }, 2700);
        setTimeout(() => capa.classList.add('con-start'), 3700);

        // 3) Capa 2D encima: relámpagos que iluminan toda la pantalla cada tanto
        const lienzo = $('intro-lienzo');
        const ctx = lienzo.getContext('2d');
        const PIXEL = 3;
        let W = 0;
        let H = 0;
        const ajustar = () => {
            W = lienzo.width = Math.ceil(innerWidth / PIXEL);
            H = lienzo.height = Math.ceil(innerHeight / PIXEL);
        };
        addEventListener('resize', ajustar);
        ajustar();

        let relampago = 0;
        let xRayo = 0;
        let proximo = 4;
        let terminado = false;
        let anterior = performance.now();
        (function dibujar() {
            if (terminado) return;
            const ahora = performance.now();
            const dt = (ahora - anterior) / 1000;
            anterior = ahora;
            ctx.clearRect(0, 0, W, H);

            proximo -= dt;
            if (proximo <= 0) {
                relampago = 1;
                xRayo = W * (0.15 + Math.random() * 0.7);
                proximo = 2.5 + Math.random() * 3;
                escena.golpe({ color: '#9ad8ff', critico: false }); // el jefe "siente" el rayo
            }
            if (relampago > 0.02) {
                ctx.fillStyle = `rgba(200,220,255,${relampago * 0.3})`;
                ctx.fillRect(0, 0, W, H);
                ctx.fillStyle = `rgba(255,255,255,${relampago})`;
                let x = xRayo;
                for (let y = 0; y < H * 0.55; y += 2) {
                    x += (Math.random() - 0.5) * 5;
                    ctx.fillRect(Math.round(x), y, 1, 2);
                }
                relampago *= 0.86;
            }
            requestAnimationFrame(dibujar);
        })();

        function empezar(ev) {
            if (!capa.classList.contains('con-start')) return; // todavía no
            ev?.preventDefault();
            terminado = true;
            sonido('start');
            escena.contraataque();
            capa.classList.add('saliendo');
            document.removeEventListener('keydown', teclado);
            capa.removeEventListener('pointerdown', empezar);
            setTimeout(() => {
                capa.classList.remove('visible');
                escena.destruir(); // liberamos la placa de video: ya no hace falta
                listo();
            }, 700);
        }
        const teclado = (ev) => { if (['Enter', ' '].includes(ev.key)) empezar(ev); };
        document.addEventListener('keydown', teclado);
        capa.addEventListener('pointerdown', empezar);
    });
}

// ---------------------------------------------------------------------------
// 2. CARGA: arranque del sistema
// ---------------------------------------------------------------------------

// Promesa que se cumple cuando Echo está conectado a Reverb (o se rinde a los 8 s)
function esperarWebSocket() {
    return new Promise((listo) => {
        const conexion = window.Echo?.connector?.pusher?.connection;
        if (!conexion) return listo(false);
        if (conexion.state === 'connected') return listo(true);
        const alCambiar = (estado) => {
            if (estado.current === 'connected') { conexion.unbind('state_change', alCambiar); listo(true); }
        };
        conexion.bind('state_change', alCambiar);
        setTimeout(() => listo(conexion.state === 'connected'), 8000);
    });
}

async function carga() {
    const capa = $('carga');
    const log = $('carga-log');
    const barra = $('carga-barra');
    capa.classList.add('visible');

    // Los tres campeones corriendo abajo
    const lienzo = $('carga-campeones');
    const ctx = lienzo.getContext('2d');
    lienzo.width = 120;
    lienzo.height = 20;
    let corriendo = true;
    const inicio = performance.now();
    (function correr() {
        if (!corriendo) return;
        const t = (performance.now() - inicio) / 1000;
        ctx.clearRect(0, 0, lienzo.width, lienzo.height);
        [['firewall', '#ffc53d'], ['exploit', '#12a594'], ['debugger', '#d6409f']].forEach(([c, color], k) => {
            const x = ((t * 30 + k * 22) % (lienzo.width + 20)) - 16;
            const salto = Math.abs(Math.sin(t * 10 + k)) * 2;
            dibujarCampeon(ctx, c, color, Math.round(x), Math.round(3 - salto), 1, Math.sin(t * 10 + k) > 0 ? 'ataque' : 'quieto');
        });
        requestAnimationFrame(correr);
    })();

    let progreso = 0;
    const avanzar = (hasta) => {
        progreso = Math.max(progreso, hasta);
        barra.style.width = progreso + '%';
    };
    const pausa = (ms) => new Promise((r) => setTimeout(r, ms));
    async function linea(texto, clase = '', ms = 260) {
        const d = document.createElement('div');
        d.className = 'linea ' + clase;
        d.textContent = texto;
        log.append(d);
        log.scrollTop = log.scrollHeight;
        sonido('tick');
        await pausa(ms);
        return d;
    }

    await linea('> iniciando kernel_de_batalla v1.0');
    avanzar(12);
    await linea('> montando sistema de archivos........ OK', 'ok');
    avanzar(25);
    await linea(`> cargando campeones [FIREWALL] [EXPLOIT] [DEBUGGER]`, 'ok');
    avanzar(40);

    // La parte real: esperamos el WebSocket
    const esperando = await linea('> conectando con el servidor en tiempo real...', '', 100);
    let puntos = 0;
    const animarPuntos = setInterval(() => {
        esperando.textContent = '> conectando con el servidor en tiempo real' + '.'.repeat(1 + (puntos++ % 3));
        avanzar(Math.min(70, progreso + 2));
    }, 250);
    const conectado = await esperarWebSocket();
    clearInterval(animarPuntos);
    if (conectado) {
        esperando.textContent = '> conectando con el servidor en tiempo real... OK (websocket)';
        esperando.classList.add('ok');
    } else {
        esperando.textContent = '> conexión lenta: seguimos igual, se reconecta solo';
        esperando.classList.add('alerta');
        sonido('error');
    }
    avanzar(78);
    await linea('> rastreando proceso corrupto.......... ENCONTRADO', 'alerta', 380);
    avanzar(90);
    await linea('> nivel de amenaza: CRÍTICO', 'peligro', 420);
    avanzar(100);
    await linea('> SISTEMA LISTO. buena suerte.', 'listo', 500);
    sonido('listo');

    corriendo = false;
    capa.classList.add('saliendo');
    await pausa(450);
    capa.classList.remove('visible');
}

// ---------------------------------------------------------------------------
// 3. MENÚ
// ---------------------------------------------------------------------------

function menu() {
    document.body.classList.remove('en-intro');

    // El jefe animado de la portada
    const lienzo = $('jefe-portada');
    if (lienzo) {
        const TAM = 6;
        const MARGEN = 18;
        lienzo.width = ANCHO * TAM + MARGEN * 2;
        lienzo.height = ALTO * TAM + MARGEN;
        const ctx = lienzo.getContext('2d');
        const inicio = performance.now();
        (function dibujar() {
            const t = (performance.now() - inicio) / 1000;
            ctx.clearRect(0, 0, lienzo.width, lienzo.height);
            dibujarJefe(ctx, MARGEN, Math.round(Math.sin(t * 2) * 4) + 4, TAM, t);
            requestAnimationFrame(dibujar);
        })();
    }

    // Menú con teclado: ↑ ↓ para elegir, Enter para entrar
    const opciones = [...document.querySelectorAll('.menu .opcion')];
    let elegida = 0;
    const marcar = (i) => {
        elegida = (i + opciones.length) % opciones.length;
        opciones.forEach((o, k) => o.classList.toggle('elegida', k === elegida));
    };
    if (opciones.length) {
        marcar(0);
        opciones.forEach((o, k) => o.addEventListener('mouseenter', () => marcar(k)));
        document.addEventListener('keydown', (ev) => {
            if (['ArrowDown', 's', 'S'].includes(ev.key)) { ev.preventDefault(); marcar(elegida + 1); sonido('tick'); }
            if (['ArrowUp', 'w', 'W'].includes(ev.key)) { ev.preventDefault(); marcar(elegida - 1); sonido('tick'); }
            if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); opciones[elegida].click(); }
        });
    }

    // Botón de sonido (se recuerda para todo el juego)
    const botonSonido = $('btn-sonido');
    if (botonSonido) {
        const pintar = () => { botonSonido.textContent = estaMudo() ? '🔇 sonido apagado' : '🔊 sonido prendido'; };
        pintar();
        botonSonido.addEventListener('click', (ev) => { ev.preventDefault(); alternarSilencio(); pintar(); sonido('tick'); });
    }

    // "Cómo jugar" se abre y se cierra
    document.querySelectorAll('[data-ayuda]').forEach((b) => b.addEventListener('click', (ev) => {
        ev.preventDefault();
        $('como-jugar')?.classList.toggle('visible');
    }));
}

// ---------------------------------------------------------------------------
// Arranque
// ---------------------------------------------------------------------------

(async function () {
    if (!$('intro')) return menu();

    // ¿Ya vio la intro en esta sesión, o viene de otra pantalla del juego?
    const saltear = recordar.get('intro-vista') || new URLSearchParams(location.search).has('menu');
    if (!saltear) {
        await intro();
        await carga();
        recordar.set('intro-vista', '1');
    }
    menu();
})();
