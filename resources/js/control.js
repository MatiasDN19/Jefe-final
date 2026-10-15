/**
 * El campo de batalla del celu/compu: TODO lo visual del jugador.
 *
 *  - Arriba, el jefe chiquito (el mismo sprite de la pantalla grande).
 *  - Abajo, 3 carriles y tu campeón parado en uno.
 *  - La barra de timing para los críticos.
 *
 * Importante: acá NO hay red. Este archivo solo dibuja. El teclado, los
 * botones y los fetch() al servidor están en control.blade.php, que le
 * avisa a este archivo qué mostrar usando window.Campo.
 *
 * Por eso el movimiento se siente instantáneo aunque el wifi sea malo:
 * tu campeón se mueve YA, y el aviso al servidor viaja por detrás.
 */
import { FILAS, ANCHO, ALTO, PALETA } from './jefe-sprite';
import { dibujarCampeon, dibujarProyectil, dibujarAura, respirando, CAMPEONES, ANCHO_CAMPEON, ALTO_CAMPEON } from './campeones';
import { sonido, estaMudo, alternarSilencio } from './sonidos';

// La vista (control.blade.php) es un script común y no puede importar módulos:
// le dejamos los sonidos a mano en window
window.Sonido = { sonido, estaMudo, alternarSilencio };

const lienzo = document.getElementById('campo');
if (lienzo) iniciar(lienzo);

function iniciar(lienzo) {
    // ---------- Medidas (en "píxeles de juego"; el CSS lo agranda) ----------
    const W = 132;
    const H = 150;
    lienzo.width = W;
    lienzo.height = H;
    const ctx = lienzo.getContext('2d');

    const CARRILES = 3;
    const ANCHO_CARRIL = W / CARRILES;
    const TAM_JEFE = 2;
    const TAM_CAMPEON = 2;
    const JEFE_X = Math.round(W / 2 - (ANCHO * TAM_JEFE) / 2);
    const JEFE_Y = 4;
    const CENTRO_JEFE = { x: W / 2, y: JEFE_Y + 22 };
    const PISO = H - 6;
    const Y_CAMPEON = PISO - ALTO_CAMPEON * TAM_CAMPEON;
    const xCarril = (c) => c * ANCHO_CARRIL + ANCHO_CARRIL / 2;

    // ---------- Perspectiva del piso (estilo synthwave) ----------
    // Todas las líneas del piso apuntan a un "punto de fuga" detrás del jefe.
    // escala(y): 0 en el punto de fuga, 1 abajo de todo. Un x de abajo (xBase)
    // se ve en la fila y en: fuga.x + (xBase - fuga.x) * escala(y)
    const HORIZONTE = 62;
    const FUGA = { x: W / 2, y: 40 };
    const escala = (y) => (y - FUGA.y) / (PISO - FUGA.y);
    const enPerspectiva = (xBase, y) => FUGA.x + (xBase - FUGA.x) * escala(y);

    const miCampeon = lienzo.dataset.campeon;
    const miColor = lienzo.dataset.color;
    const tipoProyectil = CAMPEONES[miCampeon]?.proyectil ?? 'bloque';

    // Canvas "de trabajo" para teñir al jefe y al campeón sin manchar el resto
    const capa = document.createElement('canvas');
    const ctxCapa = capa.getContext('2d');

    const ahora = () => performance.now() / 1000;

    // ---------- Estado visual ----------
    const e = {
        carril: Number(lienzo.dataset.carril),
        x: 0,
        ataque: 0,
        proyectiles: [],
        particulas: [],
        destelloJefe: 0,
        colorDestello: '#ffffff',
        temblorJefe: 0,
        aviso: null,   // { carriles, hasta }
        impacto: null, // { carriles, desde }
        caidoHasta: 0,
        dolor: 0,      // flash rojo cuando te pega el jefe
        fase: Number(lienzo.dataset.fase || 1),
        escudoHasta: 0,
        congeladoHasta: 0,
        jefeMuerto: lienzo.dataset.muerto === '1',
        flash: 0,
        pausaHasta: 0,   // hit-stop: el juego se congela un instante en los críticos
        temblor: 0,      // temblor de toda la pantalla
        fantasmas: new Map(), // tus compañeros, transparentes en sus carriles
    };
    e.x = xCarril(e.carril);

    // ---------- Barra de timing ----------
    const marcador = document.getElementById('timing-marcador');
    const zona = document.getElementById('timing-zona');
    const ANCHO_ZONA = Number(document.getElementById('timing')?.dataset.zona ?? 0.14);
    if (zona) {
        zona.style.width = ANCHO_ZONA * 100 + '%';
        zona.style.left = (0.5 - ANCHO_ZONA / 2) * 100 + '%';
    }
    let posTiming = 0;

    // ---------- API: lo que llama control.blade.php ----------
    window.Campo = {
        setCarril(c) { e.carril = c; },

        enZonaCritica() { return Math.abs(posTiming - 0.5) <= ANCHO_ZONA / 2; },

        // 0 = justo en el centro de la zona verde, 1 = en el borde
        precision() { return Math.abs(posTiming - 0.5) / (ANCHO_ZONA / 2); },

        // Hit-stop: congelar todo unos milisegundos + temblor (como en los juegos de pelea)
        hitstop(ms, fuerza = 1) {
            e.pausaHasta = ahora() + ms / 1000;
            e.temblor = Math.max(e.temblor, fuerza);
        },

        // ---- Compañeros fantasma ----
        fantasmas: {
            cargar(lista) { lista.forEach((j) => window.Campo.fantasmas.agregar(j)); },
            agregar(j) { if (j.id !== lienzo.dataset.id) e.fantasmas.set(j.id, { ...j, ataque: 0, dolor: 0, x: null }); },
            mover(id, carril) { const f = e.fantasmas.get(id); if (f) f.carril = carril; },
            actualizar(j) { const f = e.fantasmas.get(j.id); if (f) Object.assign(f, { nombre: j.nombre, color: j.color, campeon: j.campeon }); },
            quitar(id) { e.fantasmas.delete(id); },
            ataca(id, critico) {
                const f = e.fantasmas.get(id);
                if (!f || f.y === undefined) return;
                f.ataque = 0.18;
                e.proyectiles.push({ x: f.x, y: f.y, critico, color: critico ? '#ffc53d' : f.color, chico: true });
            },
            golpeado(id) { const f = e.fantasmas.get(id); if (f) f.dolor = 1; },
        },

        atacar(critico) {
            e.ataque = 0.18;
            e.proyectiles.push({
                x: e.x, y: Y_CAMPEON, critico,
                color: critico ? '#ffc53d' : miColor,
            });
        },

        golpeJefe(color) {
            e.destelloJefe = 1;
            e.colorDestello = color;
            e.temblorJefe = 2;
        },

        preparar(carriles, segundos) { e.aviso = { carriles, hasta: ahora() + segundos }; },

        impacto(carriles) {
            e.aviso = null;
            e.impacto = { carriles, desde: ahora() };
            carriles.forEach((c) => {
                for (let n = 0; n < 20; n++) particula(xCarril(c) + (Math.random() - 0.5) * ANCHO_CARRIL, PISO - Math.random() * 10, '#9ad8ff');
            });
        },

        // Te pegó el rayo: flash rojo. Si caíste, te quedás tirado unos segundos.
        golpeado(caido, segundos) {
            e.dolor = 1;
            e.temblor = Math.max(e.temblor, caido ? 3 : 2);
            if (caido) e.caidoHasta = ahora() + segundos;
            else e.congeladoHasta = ahora() + segundos;
            for (let n = 0; n < 16; n++) particula(e.x, Y_CAMPEON + 10, caido ? '#e0245e' : '#9ad8ff');
        },

        setFase(f) { e.fase = f; e.temblorJefe = 8; e.flash = 0.6; },

        escudo(segundos) { e.escudoHasta = ahora() + segundos; },
        congelar(segundos) { e.congeladoHasta = ahora() + segundos; },
        descongelar() { e.congeladoHasta = 0; e.caidoHasta = 0; },

        combo() {
            e.flash = 1;
            e.temblorJefe = 6;
            for (let n = 0; n < 40; n++) particula(CENTRO_JEFE.x, CENTRO_JEFE.y, n % 2 ? '#5ff3ff' : '#ffffff');
        },

        jefeMuere() {
            e.jefeMuerto = true;
            for (let n = 0; n < 60; n++) particula(CENTRO_JEFE.x, CENTRO_JEFE.y, PALETA.V);
        },
        jefeRevive() { e.jefeMuerto = false; },
    };

    function particula(x, y, color) {
        e.particulas.push({
            x, y, color, vida: 1,
            vx: (Math.random() - 0.5) * 80, vy: -Math.random() * 70,
        });
    }

    // ---------- Dibujo ----------

    // Colores del piso según la fase: [línea neón, piso, sol]
    const COLORES_FASE = {
        1: ['#d6409f', '20,10,50', ['#ff8a3d', '#d6409f', '#7a5cd6']],
        2: ['#ff5a1f', '45,10,40', ['#ffc53d', '#ff5a1f', '#d6409f']],
        3: ['#ff2a4a', '60,5,20', ['#ffffff', '#ff2a4a', '#8a0f2e']],
    };

    function dibujarCarriles(t) {
        const [neon, piso, sol] = COLORES_FASE[e.fase] ?? COLORES_FASE[1];

        // Sol retro en el horizonte, cortado en franjas (detrás del jefe)
        for (let y = HORIZONTE - 24; y < HORIZONTE; y++) {
            const dy = HORIZONTE - y;
            const franja = dy < 8 ? (y % 3 === 0) : dy < 14 ? (y % 4 === 0) : false; // franjas cada vez más finas
            if (franja) continue;
            const ancho = Math.sqrt(Math.max(0, 26 * 26 - (dy + 2) * (dy + 2)));
            ctx.fillStyle = sol[dy < 8 ? 2 : dy < 16 ? 1 : 0];
            ctx.globalAlpha = 0.55;
            ctx.fillRect(Math.round(W / 2 - ancho), y, Math.round(ancho * 2), 1);
        }
        ctx.globalAlpha = 1;

        // El piso: cada fila es una línea, más clara cuanto más cerca
        for (let y = HORIZONTE; y < H; y++) {
            const f = (y - HORIZONTE) / (H - HORIZONTE);
            ctx.fillStyle = `rgba(${piso},${0.6 + f * 0.4})`;
            const x0 = enPerspectiva(0, y);
            ctx.fillRect(Math.floor(x0), y, Math.ceil(enPerspectiva(W, y) - x0), 1);
        }

        // Líneas horizontales de la grilla que vienen hacia vos
        const N = 7;
        for (let k = 0; k < N; k++) {
            const u = ((k + t * 1.4) % N) / N;          // 0 = lejos, 1 = cerca
            const y = Math.round(HORIZONTE + (PISO - HORIZONTE) * u * u);
            ctx.fillStyle = neon;
            ctx.globalAlpha = 0.15 + u * 0.6;
            const x0 = enPerspectiva(0, y);
            ctx.fillRect(Math.floor(x0), y, Math.ceil(enPerspectiva(W, y) - x0), 1);
        }

        // Líneas de los carriles (y los bordes), todas hacia el punto de fuga
        for (let y = HORIZONTE; y < H; y++) {
            ctx.globalAlpha = 0.35 + escala(y) * 0.65;
            for (let c = 0; c <= CARRILES; c++) {
                ctx.fillStyle = c === 0 || c === CARRILES ? '#7a5cd6' : neon;
                ctx.fillRect(Math.round(enPerspectiva(c * ANCHO_CARRIL, y)), y, 1, 1);
            }
        }
        ctx.globalAlpha = 1;

        // Resplandor en el horizonte
        ctx.fillStyle = neon;
        ctx.globalAlpha = 0.8;
        ctx.fillRect(Math.floor(enPerspectiva(0, HORIZONTE)), HORIZONTE, Math.ceil(enPerspectiva(W, HORIZONTE) - enPerspectiva(0, HORIZONTE)), 1);
        ctx.globalAlpha = 1;
    }

    // Pinta un carril entero (en perspectiva) de un color
    function pintarCarril(c, color) {
        ctx.fillStyle = color;
        for (let y = HORIZONTE; y < H; y++) {
            const x0 = enPerspectiva(c * ANCHO_CARRIL, y);
            ctx.fillRect(Math.floor(x0), y, Math.ceil(enPerspectiva((c + 1) * ANCHO_CARRIL, y) - x0), 1);
        }
    }

    // Compañeros: transparentes, más chicos y "más lejos" en el piso
    function dibujarFantasmas(t) {
        const porCarril = [[], [], []];
        e.fantasmas.forEach((f) => porCarril[f.carril]?.push(f));
        const lista = [];
        porCarril.forEach((grupo, c) => grupo.forEach((f, k) => {
            const u = Math.max(0.25, 0.62 - k * 0.11);              // cada uno un poco más atrás
            const y = Math.round(HORIZONTE + (PISO - HORIZONTE) * u);
            const lado = (k % 2 ? 1 : -1) * 4 * escala(y);          // se acomodan a los costados
            const x = enPerspectiva(xCarril(c), y) + lado;
            f.x = f.x === null ? x : f.x + (x - f.x) * 0.2;
            f.y = y;
            lista.push(f);
        }));
        lista.sort((a, b) => a.y - b.y).forEach((f) => {
            f.ataque = Math.max(0, f.ataque - 1 / 60);
            f.dolor = Math.max(0, f.dolor - 0.03);
            ctx.globalAlpha = 0.45;
            dibujarCampeon(ctx, f.campeon, f.color, Math.round(f.x - 7), f.y - 14 - (f.ataque > 0 ? 2 : 0), 1, f.ataque > 0 ? 'ataque' : 'quieto', false, respirando(t, f.id.charCodeAt(0)));
            ctx.globalAlpha = 1;
            // Una marquita de su color arriba (o roja si le acaban de pegar)
            ctx.fillStyle = f.dolor > 0 ? '#ff2a4a' : f.color;
            ctx.fillRect(Math.round(f.x) - 1, f.y - 18, 2, 1);
        });
    }

    function dibujarAviso(t) {
        if (!e.aviso) return;
        const falta = e.aviso.hasta - t;
        if (falta <= 0) return;
        // Titila cada vez más rápido a medida que se acerca el golpe
        const vel = falta < 0.8 ? 16 : 7;
        const on = Math.sin(t * vel) > 0;
        e.aviso.carriles.forEach((carril) => {
            pintarCarril(carril, on ? 'rgba(224,36,94,.45)' : 'rgba(224,36,94,.18)');
            // Signo de alerta pixel, en medio del carril
            ctx.fillStyle = '#ffffff';
            const cx = Math.round(enPerspectiva(xCarril(carril), 100));
            ctx.fillRect(cx - 1, 92, 3, 9);
            ctx.fillRect(cx - 1, 103, 3, 3);
        });
    }

    // El ojo del jefe chiquito (de ahí salen los rayos)
    const OJO = { x: JEFE_X + 11 * TAM_JEFE + 1, y: JEFE_Y + 9 * TAM_JEFE };

    function dibujarImpacto(t) {
        if (!e.impacto) return;
        const p = (t - e.impacto.desde) / 0.5;
        if (p >= 1) { e.impacto = null; return; }
        e.impacto.carriles.forEach((c) => {
            const piso = { x: xCarril(c), y: PISO - 2 };
            const grosor = Math.max(1, Math.round(4 * (1 - p)));
            rayoPixel(OJO, piso, grosor + 2, 'rgba(154,216,255,.4)', 8);
            rayoPixel(OJO, piso, grosor, '#9ad8ff', 6);
            rayoPixel(OJO, piso, 1, '#ffffff', 3);
            // Estallido en el piso
            pintarCarril(c, `rgba(154,216,255,${0.45 * (1 - p)})`);
        });
    }

    // Un rayo pixelado en zigzag entre dos puntos
    function rayoPixel(desde, hasta, grosor, color, zigzag) {
        const pasos = Math.max(8, Math.round(Math.hypot(hasta.x - desde.x, hasta.y - desde.y) / 3));
        ctx.fillStyle = color;
        let desvio = 0;
        for (let k = 0; k <= pasos; k++) {
            const f = k / pasos;
            if (k % 3 === 0) desvio = (Math.random() - 0.5) * zigzag * Math.sin(f * Math.PI);
            const x = desde.x + (hasta.x - desde.x) * f + desvio;
            const y = desde.y + (hasta.y - desde.y) * f;
            ctx.fillRect(Math.round(x - grosor / 2), Math.round(y - grosor / 2), grosor, grosor);
        }
    }

    function dibujarJefe(t, dt) {
        capa.width = ANCHO * TAM_JEFE + 8;
        capa.height = ALTO * TAM_JEFE + 4;
        ctxCapa.clearRect(0, 0, capa.width, capa.height);
        ctxCapa.filter = e.jefeMuerto ? 'grayscale(1) brightness(.4)' : 'none';

        const apuntando = (e.aviso && e.aviso.hasta > t) || e.fase === 3; // en furia, ojo rojo siempre
        const ojoX = e.jefeMuerto ? 0 : Math.round(Math.sin(t * 1.2) * Math.sin(t * 3.1));
        const parpadea = !e.jefeMuerto && (t % 4) < 0.12;

        FILAS.forEach((fila, j) => {
            // Más enojado = tentáculos más inquietos
            const onda = e.jefeMuerto ? 0 : (Math.max(0, j - 13) / 13) * (1 + (e.fase - 1) * 0.8);
            const corr = Math.round(Math.sin(t * (4 + e.fase * 2) - j * 0.5) * onda * 2);
            [...fila].forEach((letra, i) => {
                if (letra === '.') return;
                const esOjo = (letra === 'C' || letra === 'W') && j <= 11;
                let x = 4 + (i + corr) * TAM_JEFE;
                const y = j * TAM_JEFE;
                if (esOjo) {
                    ctxCapa.fillStyle = PALETA.D;
                    ctxCapa.fillRect(x, y, TAM_JEFE, TAM_JEFE);
                    if (parpadea) return;
                    x += ojoX * TAM_JEFE;
                    // Cuando apunta, el ojo se pone rojo
                    ctxCapa.fillStyle = apuntando ? '#e0245e' : PALETA[letra];
                } else if (letra === 'M' && e.fase > 1) {
                    // Los cuernos "arden" en las fases 2 y 3
                    ctxCapa.fillStyle = Math.sin(t * 20 + i + j) > 0 ? '#ff8a3d' : PALETA.M;
                } else {
                    ctxCapa.fillStyle = PALETA[letra];
                }
                ctxCapa.fillRect(x, y, TAM_JEFE, TAM_JEFE);
            });
        });

        // Destello del color de quien pegó (solo encima del jefe)
        e.destelloJefe *= Math.pow(0.02, dt);
        if (e.destelloJefe > 0.03) {
            ctxCapa.globalCompositeOperation = 'source-atop';
            ctxCapa.globalAlpha = e.destelloJefe * 0.8;
            ctxCapa.fillStyle = e.colorDestello;
            ctxCapa.fillRect(0, 0, capa.width, capa.height);
            ctxCapa.globalAlpha = 1;
            ctxCapa.globalCompositeOperation = 'source-over';
        }

        e.temblorJefe *= Math.pow(0.01, dt);
        const dx = Math.round((Math.random() - 0.5) * e.temblorJefe);
        const dy = Math.round(Math.sin(t * 2) * 1.5);
        ctx.drawImage(capa, JEFE_X - 4 + dx, JEFE_Y + dy);
    }

    function dibujarCampeonPropio(t) {
        if (e.caidoHasta > t) return dibujarCaido(t);
        const congelado = e.congeladoHasta > t;
        const salto = e.ataque > 0 ? -4 : 0;
        const ancho = ANCHO_CAMPEON * TAM_CAMPEON;
        const x = Math.round(e.x - ancho / 2);
        const y = Y_CAMPEON + salto;
        const T = TAM_CAMPEON;

        // Lo armamos en la capa de trabajo (con 1 píxel de margen para el contorno)
        capa.width = (ANCHO_CAMPEON + 2) * T;
        capa.height = (ALTO_CAMPEON + 2) * T;
        ctxCapa.clearRect(0, 0, capa.width, capa.height);
        dibujarCampeon(ctxCapa, miCampeon, miColor, T, T, T, e.ataque > 0 ? 'ataque' : 'quieto', false, !congelado && respirando(t));
        if (congelado) {
            ctxCapa.globalCompositeOperation = 'source-atop';
            ctxCapa.fillStyle = 'rgba(154,216,255,.65)';
            ctxCapa.fillRect(0, 0, capa.width, capa.height);
            ctxCapa.globalCompositeOperation = 'source-over';
        }

        // Sombrita
        ctx.fillStyle = 'rgba(0,0,0,.5)';
        ctx.fillRect(x + 4, PISO - 1, ancho - 8, 2);

        // Estela: mientras se mueve de carril, dejamos un "fantasma" detrás
        const falta = xCarril(e.carril) - e.x;
        if (Math.abs(falta) > 2) {
            ctx.globalAlpha = 0.35;
            ctx.drawImage(capa, x - T - Math.sign(falta) * 8, y - T);
            ctx.globalAlpha = 0.15;
            ctx.drawImage(capa, x - T - Math.sign(falta) * 16, y - T);
            ctx.globalAlpha = 1;
        }

        // Efectos de la clase: los de atrás antes, el campeón, y encima el resto
        if (!congelado) dibujarAura(ctx, miCampeon, miColor, e.x, y + (ALTO_CAMPEON * T) / 2, T, t, e.ataque);
        ctx.drawImage(capa, x - T, y - T);

        // Bloque de hielo
        if (congelado) {
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 1;
            ctx.strokeRect(x - 2.5, y - 2.5, ancho + 5, capa.height + 5);
            ctx.fillStyle = 'rgba(154,216,255,.25)';
            ctx.fillRect(x - 2, y - 2, ancho + 4, capa.height + 4);
        }

        // Escudo: una burbuja pixelada que late
        if (e.escudoHasta > t) {
            const r = 18 + Math.round(Math.sin(t * 12));
            const cx = e.x;
            const cy = y + capa.height / 2;
            ctx.fillStyle = 'rgba(95,243,255,.9)';
            for (let a = 0; a < Math.PI * 2; a += Math.PI / 14) {
                ctx.fillRect(Math.round(cx + Math.cos(a) * r), Math.round(cy + Math.sin(a) * r), 2, 2);
            }
            ctx.fillStyle = 'rgba(95,243,255,.12)';
            ctx.fillRect(cx - r + 3, cy - r + 3, r * 2 - 6, r * 2 - 6);
        }
    }

    // Caído: el campeón queda acostado, gris, con una cuenta regresiva arriba
    function dibujarCaido(t) {
        const T = TAM_CAMPEON;
        capa.width = (ANCHO_CAMPEON + 2) * T;
        capa.height = (ALTO_CAMPEON + 2) * T;
        ctxCapa.clearRect(0, 0, capa.width, capa.height);
        dibujarCampeon(ctxCapa, miCampeon, miColor, T, T, T, 'quieto');
        ctx.save();
        ctx.filter = 'grayscale(1) brightness(.6)';
        ctx.translate(Math.round(e.x), PISO - 7);
        ctx.rotate(-Math.PI / 2);
        ctx.drawImage(capa, -capa.width / 2, -capa.height / 2);
        ctx.restore();
        // Barrita de "reiniciando" que se llena
        const falta = Math.max(0, e.caidoHasta - t);
        const total = 8;
        ctx.fillStyle = '#2a1a55';
        ctx.fillRect(Math.round(e.x - 14), PISO - 26, 28, 4);
        ctx.fillStyle = '#5ff3ff';
        ctx.fillRect(Math.round(e.x - 14), PISO - 26, Math.round(28 * (1 - falta / total)), 4);
    }

    function actualizarProyectiles(dt) {
        e.proyectiles = e.proyectiles.filter((p) => {
            p.y -= 260 * dt;
            p.x += (CENTRO_JEFE.x - p.x) * Math.min(1, dt * 6);
            if (p.y <= CENTRO_JEFE.y) {
                for (let n = 0; n < (p.critico ? 14 : 5); n++) particula(p.x, p.y, p.color);
                return false;
            }
            dibujarProyectil(ctx, tipoProyectil, p.color, p.x, p.y, p.chico ? 1 : p.critico ? 2 : 1);
            return true;
        });
    }

    function actualizarParticulas(dt) {
        e.particulas = e.particulas.filter((p) => {
            p.vida -= dt * 1.6;
            p.vy += 140 * dt;
            p.x += p.vx * dt;
            p.y += p.vy * dt;
            if (p.vida <= 0) return false;
            ctx.fillStyle = p.color;
            ctx.globalAlpha = Math.min(1, p.vida * 2);
            ctx.fillRect(Math.round(p.x), Math.round(p.y), 2, 2);
            ctx.globalAlpha = 1;
            return true;
        });
    }

    // ---------- Retrato del HUD (estilo juego de pelea) ----------
    // Tu campeón en primer plano: dibujamos el sprite grande y el canvas,
    // al ser chiquito, "recorta" y deja ver solo la cabeza y los hombros.
    const retrato = document.getElementById('retrato');
    const ctxRetrato = retrato?.getContext('2d');
    const TAM_RETRATO = 4;
    if (retrato) {
        retrato.width = (ANCHO_CAMPEON + 2) * TAM_RETRATO;
        retrato.height = 9 * TAM_RETRATO;
    }

    function dibujarRetrato(t) {
        if (!ctxRetrato) return;
        const c = ctxRetrato;
        c.clearRect(0, 0, retrato.width, retrato.height);
        // Fondo con rayas de "velocidad" de su color
        c.fillStyle = '#0b0d1f';
        c.fillRect(0, 0, retrato.width, retrato.height);
        c.fillStyle = miColor;
        c.globalAlpha = 0.25;
        for (let k = 0; k < 6; k++) {
            const x = ((t * 40 + k * 13) % (retrato.width + 20)) - 10;
            c.fillRect(Math.round(x), 0, 2, retrato.height);
        }
        c.globalAlpha = 1;

        const caido = e.caidoHasta > t;
        const congelado = e.congeladoHasta > t;
        const temblor = e.dolor > 0.1 ? Math.round((Math.random() - 0.5) * 4 * e.dolor) : 0;
        c.filter = caido ? 'grayscale(1) brightness(.5)' : 'none';
        dibujarCampeon(c, miCampeon, miColor, TAM_RETRATO + temblor, TAM_RETRATO + (e.ataque > 0 ? -2 : 0), TAM_RETRATO,
            e.ataque > 0 ? 'ataque' : 'quieto', false, !caido && respirando(t));
        c.filter = 'none';

        // Tintes según lo que te pasa
        const tinte = e.dolor > 0.05 ? `rgba(255,42,74,${e.dolor * 0.6})`
            : congelado ? 'rgba(154,216,255,.45)'
            : e.ataque > 0 ? 'rgba(255,255,255,.35)' : null;
        if (tinte) {
            c.globalCompositeOperation = 'source-atop';
            c.fillStyle = tinte;
            c.fillRect(0, 0, retrato.width, retrato.height);
            c.globalCompositeOperation = 'source-over';
        }
    }

    // ---------- Loop ----------
    let anterior = ahora();
    function animar() {
        const t = ahora();
        const dt = Math.min(0.05, t - anterior);
        anterior = t;

        // El campeón se desliza hacia su carril (rápido, pero se nota el movimiento)
        e.x += (xCarril(e.carril) - e.x) * Math.min(1, dt * 18);
        e.ataque = Math.max(0, e.ataque - dt);

        // Hit-stop: mientras dura, NO actualizamos nada (queda el último cuadro)
        if (t < e.pausaHasta) {
            requestAnimationFrame(animar);
            return;
        }

        // Temblor de pantalla: corremos todo el dibujo unos píxeles al azar
        e.temblor *= Math.pow(0.002, dt);
        ctx.save();
        if (e.temblor > 0.1) ctx.translate(Math.round((Math.random() - 0.5) * e.temblor * 2), Math.round((Math.random() - 0.5) * e.temblor * 2));

        ctx.fillStyle = '#05050c';
        ctx.fillRect(-4, -4, W + 8, H + 8);
        dibujarCarriles(t);
        dibujarAviso(t);
        dibujarImpacto(t);
        dibujarJefe(t, dt);
        dibujarFantasmas(t);
        actualizarProyectiles(dt);
        dibujarCampeonPropio(t);
        actualizarParticulas(dt);
        ctx.restore();
        dibujarRetrato(t);

        // Flash rojo cuando te pega el jefe
        e.dolor *= Math.pow(0.03, dt);
        if (e.dolor > 0.02) {
            ctx.fillStyle = `rgba(224,36,94,${e.dolor * 0.5})`;
            ctx.fillRect(0, 0, W, H);
        }

        // Flash del combo
        e.flash *= Math.pow(0.05, dt);
        if (e.flash > 0.02) {
            ctx.fillStyle = `rgba(255,255,255,${e.flash * 0.8})`;
            ctx.fillRect(0, 0, W, H);
        }

        // Barra de timing: un marcador que va y viene
        posTiming = (Math.sin(t * 3.2) + 1) / 2;
        if (marcador) {
            marcador.style.left = posTiming * 100 + '%';
            marcador.classList.toggle('en-zona', window.Campo.enZonaCritica());
        }

        requestAnimationFrame(animar);
    }
    animar();
}
