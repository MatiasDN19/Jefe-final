/**
 * Los campeones de TODOS los jugadores en la pantalla grande.
 *
 * Es un canvas 2D transparente que va ENCIMA de la escena 3D:
 * Three.js dibuja al jefe y esta capa dibuja a los jugadores, sus
 * disparos, los carriles y los ataques del jefe sobre los carriles.
 *
 * pantalla.blade.php le avisa qué pasó usando window.CampoPantalla.
 */
import { dibujarCampeon, dibujarProyectil, dibujarAura, respirando, CAMPEONES, ANCHO_CAMPEON, ALTO_CAMPEON } from './campeones';

const lienzo = document.getElementById('campo-pantalla');
if (lienzo) iniciar(lienzo);

function iniciar(lienzo) {
    const PIXEL = 4; // un poco más grueso que la escena 3D, así se ven bien en el proyector
    const ctx = lienzo.getContext('2d');
    let W = 1;
    let H = 1;

    function ajustar() {
        W = Math.ceil(lienzo.clientWidth / PIXEL);
        H = Math.ceil(lienzo.clientHeight / PIXEL);
        lienzo.width = W;
        lienzo.height = H;
    }
    new ResizeObserver(ajustar).observe(lienzo);
    ajustar();

    const CARRILES = 3;
    const capa = document.createElement('canvas');
    const ctxCapa = capa.getContext('2d');
    const ahora = () => performance.now() / 1000;

    // Geometría (se recalcula siempre porque la ventana puede cambiar de tamaño)
    const zonaCarriles = () => ({ y: H * 0.74, alto: H * 0.26 });
    const anchoCarril = () => W / CARRILES;
    // Dónde está el jefe en la pantalla: lo calcula la escena 3D en cada
    // frame (window.Jefe3D.centro / .ojo, de 0 a 1). Si todavía no está, estimamos.
    const centroJefe = () => {
        const c = window.Jefe3D?.centro;
        return c ? { x: c.x * W, y: c.y * H } : { x: W / 2, y: H * 0.38 };
    };
    const ojoJefe = () => {
        const o = window.Jefe3D?.ojo;
        return o ? { x: o.x * W, y: o.y * H } : centroJefe();
    };

    const jugadores = new Map(); // id -> { datos + estado visual }
    let proyectiles = [];
    let particulas = [];
    let aviso = null;   // { carriles, hasta }
    let impacto = null; // { carril, desde }
    let rayosCombo = []; // { desde: jugador, color, hasta }

    function agregar(j) {
        if (jugadores.has(j.id)) return;
        jugadores.set(j.id, { vida: 3, ...j, x: null, ataque: 0, congeladoHasta: 0, caidoHasta: 0, escudoHasta: 0, aparecer: 1, dolor: 0 });
    }

    // Posición de cada jugador: se acomodan en filas dentro de su carril
    function posiciones() {
        const { y, alto } = zonaCarriles();
        const porCarril = [[], [], []];
        jugadores.forEach((j) => porCarril[j.carril]?.push(j));
        const pos = new Map();
        porCarril.forEach((lista, c) => {
            const POR_FILA = Math.max(1, Math.floor(anchoCarril() / (ANCHO_CAMPEON + 4)));
            lista.forEach((j, k) => {
                const fila = Math.floor(k / POR_FILA);
                const col = k % POR_FILA;
                const enFila = Math.min(POR_FILA, lista.length - fila * POR_FILA);
                const xInicio = c * anchoCarril() + anchoCarril() / 2 - (enFila * (ANCHO_CAMPEON + 4)) / 2;
                pos.set(j.id, {
                    x: xInicio + col * (ANCHO_CAMPEON + 4) + ANCHO_CAMPEON / 2,
                    y: y + alto - 4 - ALTO_CAMPEON - fila * 9, // las filas de atrás un poco más arriba
                });
            });
        });
        return pos;
    }

    function particula(x, y, color, fuerza = 40) {
        particulas.push({ x, y, color, vida: 1, vx: (Math.random() - 0.5) * fuerza * 2, vy: -Math.random() * fuerza * 1.5 });
    }

    // ---------- API ----------
    window.CampoPantalla = {
        cargar(lista) { lista.forEach(agregar); },
        agregar(j) {
            agregar(j);
            const p = posiciones().get(j.id);
            if (p) for (let n = 0; n < 16; n++) particula(p.x, p.y + 6, j.color);
        },
        mover(id, carril) { const j = jugadores.get(id); if (j) j.carril = carril; },

        // Editó su perfil: cambiamos nombre, color y campeón (sin moverlo)
        actualizar(datos) { const j = jugadores.get(datos.id); if (j) Object.assign(j, { nombre: datos.nombre, color: datos.color, campeon: datos.campeon }); },

        // Abandonó: sale con un estallido de su color
        quitar(id) {
            const j = jugadores.get(id);
            const p = posiciones().get(id);
            if (j && p) for (let n = 0; n < 20; n++) particula(p.x, p.y + 6, j.color, 60);
            jugadores.delete(id);
        },

        limpiar() { jugadores.clear(); },

        atacar(id, critico, color) {
            const j = jugadores.get(id);
            const p = posiciones().get(id);
            if (!j || !p) return;
            j.ataque = 0.2;
            proyectiles.push({
                x: p.x, y: p.y, critico, color: critico ? '#ffc53d' : color,
                tipo: CAMPEONES[j.campeon]?.proyectil ?? 'bloque',
            });
        },

        escudo(id, segundos) { const j = jugadores.get(id); if (j) j.escudoHasta = ahora() + segundos; },

        preparar(carriles, segundos) { aviso = { carriles, hasta: ahora() + segundos }; },

        impacto(carriles, golpeados, bloqueados) {
            aviso = null;
            impacto = { carriles, desde: ahora() };
            const pos = posiciones();
            golpeados.forEach((g) => {
                const j = jugadores.get(g.id);
                if (j) {
                    j.vida = g.vida;
                    j.dolor = 1;
                    if (g.caido) j.caidoHasta = ahora() + g.segundos;
                    else j.congeladoHasta = ahora() + g.segundos;
                }
                const p = pos.get(g.id);
                if (p) for (let n = 0; n < 12; n++) particula(p.x, p.y, g.caido ? '#e0245e' : '#9ad8ff', 60);
            });
            bloqueados.forEach((b) => {
                const p = pos.get(b.id);
                if (p) for (let n = 0; n < 14; n++) particula(p.x, p.y, '#5ff3ff', 80);
            });
        },

        // Rayos desde cada participante del combo hacia el jefe
        combo(nombres) {
            jugadores.forEach((j) => {
                if (nombres.includes(j.nombre)) rayosCombo.push({ id: j.id, color: j.color, hasta: ahora() + 1.2 });
            });
            const c = centroJefe();
            for (let n = 0; n < 60; n++) particula(c.x, c.y, n % 2 ? '#ffffff' : '#d6409f', 120);
        },

        reiniciar() {
            jugadores.forEach((j) => { j.congeladoHasta = 0; j.caidoHasta = 0; j.escudoHasta = 0; j.vida = 3; });
        },
    };

    // ---------- Dibujo ----------
    function dibujarCarriles(t) {
        const { y, alto } = zonaCarriles();
        const ac = anchoCarril();
        for (let c = 0; c < CARRILES; c++) {
            ctx.fillStyle = c % 2 ? 'rgba(75,47,154,.12)' : 'rgba(75,47,154,.2)';
            ctx.fillRect(c * ac, y, ac, alto);
        }
        ctx.fillStyle = 'rgba(122,92,214,.55)';
        for (let c = 1; c < CARRILES; c++) {
            for (let yy = y + ((t * 15) % 6); yy < y + alto; yy += 6) ctx.fillRect(Math.round(c * ac), Math.round(yy), 1, 3);
        }
        ctx.fillStyle = '#7a5cd6';
        ctx.fillRect(0, Math.round(y), W, 1);

        // Aviso del jefe: los carriles apuntados titilan en rojo
        if (aviso && aviso.hasta > t) {
            const falta = aviso.hasta - t;
            const on = Math.sin(t * (falta < 0.8 ? 18 : 8)) > 0;
            aviso.carriles.forEach((carril) => {
                ctx.fillStyle = on ? 'rgba(224,36,94,.45)' : 'rgba(224,36,94,.15)';
                ctx.fillRect(carril * ac, y, ac, alto);
                // Mira del jefe: corchetes en las esquinas
                ctx.fillStyle = '#ffffff';
                const x0 = carril * ac + 3;
                const x1 = (carril + 1) * ac - 9;
                [[x0, y + 3], [x1, y + 3], [x0, y + alto - 5], [x1, y + alto - 5]].forEach(([cx, cy]) => ctx.fillRect(Math.round(cx), Math.round(cy), 6, 2));
            });
        }

        // Impacto: un rayo en zigzag que SALE DEL OJO y cae sobre cada carril
        if (impacto) {
            const p = (t - impacto.desde) / 0.55;
            if (p >= 1) { impacto = null; return; }
            const ojo = ojoJefe();
            impacto.carriles.forEach((carril) => {
                const piso = { x: carril * ac + ac / 2, y: y + alto - 6 };
                const grosor = Math.max(1, Math.round(5 * (1 - p)));
                // Tres capas: un halo celeste ancho, el rayo y un núcleo blanco
                rayoPixel(ojo, piso, grosor + 4, 'rgba(154,216,255,.35)', 10);
                rayoPixel(ojo, piso, grosor, '#9ad8ff', 8);
                rayoPixel(ojo, piso, Math.max(1, grosor - 2), '#ffffff', 4);
                // Estallido en el piso del carril
                const r = Math.round(ac * 0.45 * Math.min(1, p * 3));
                ctx.fillStyle = `rgba(154,216,255,${0.5 * (1 - p)})`;
                ctx.fillRect(Math.round(piso.x - r), Math.round(y), r * 2, Math.round(alto));
            });
        }
    }

    // Dibuja un rayo pixelado en zigzag entre dos puntos
    function rayoPixel(desde, hasta, grosor, color, zigzag) {
        const pasos = Math.max(8, Math.round(Math.hypot(hasta.x - desde.x, hasta.y - desde.y) / 4));
        ctx.fillStyle = color;
        let desvio = 0;
        for (let k = 0; k <= pasos; k++) {
            const f = k / pasos;
            if (k % 3 === 0) desvio = (Math.random() - 0.5) * zigzag * Math.sin(f * Math.PI); // 0 en las puntas
            const x = desde.x + (hasta.x - desde.x) * f + desvio;
            const yy = desde.y + (hasta.y - desde.y) * f;
            ctx.fillRect(Math.round(x - grosor / 2), Math.round(yy - grosor / 2), grosor, grosor);
        }
    }

    function dibujarJugadores(t, dt) {
        const pos = posiciones();
        jugadores.forEach((j) => {
            const p = pos.get(j.id);
            if (!p) return;
            // Se deslizan suavemente cuando cambian de carril
            j.x = j.x === null ? p.x : j.x + (p.x - j.x) * Math.min(1, dt * 10);
            j.ataque = Math.max(0, j.ataque - dt);
            j.aparecer = Math.max(0, j.aparecer - dt * 2);

            if (j.caidoHasta > t) return dibujarCaido(j, p, t);
            const congelado = j.congeladoHasta > t;
            const fase = j.id.charCodeAt(0) + j.id.charCodeAt(1);
            const salto = j.ataque > 0 ? -4 : 0;
            const x = Math.round(j.x - ANCHO_CAMPEON / 2);
            const y = Math.round(p.y + salto - j.aparecer * 20);

            // Capa de trabajo con 1 píxel de margen para el contorno
            capa.width = ANCHO_CAMPEON + 2;
            capa.height = ALTO_CAMPEON + 2;
            ctxCapa.clearRect(0, 0, capa.width, capa.height);
            dibujarCampeon(ctxCapa, j.campeon, j.color, 1, 1, 1, j.ataque > 0 ? 'ataque' : 'quieto', false, !congelado && respirando(t, fase));
            if (congelado) {
                ctxCapa.globalCompositeOperation = 'source-atop';
                ctxCapa.fillStyle = 'rgba(154,216,255,.7)';
                ctxCapa.fillRect(0, 0, capa.width, capa.height);
                ctxCapa.globalCompositeOperation = 'source-over';
            }

            ctx.fillStyle = 'rgba(0,0,0,.5)';
            ctx.fillRect(x + 2, Math.round(p.y) + ALTO_CAMPEON, ANCHO_CAMPEON - 4, 1);

            // Estela mientras cambia de carril
            const falta = p.x - j.x;
            if (Math.abs(falta) > 1.5) {
                ctx.globalAlpha = 0.3;
                ctx.drawImage(capa, x - 1 - Math.sign(falta) * 5, y - 1);
                ctx.globalAlpha = 1;
            }
            if (!congelado) dibujarAura(ctx, j.campeon, j.color, j.x, y + ALTO_CAMPEON / 2, 1, t + fase, j.ataque);
            ctx.drawImage(capa, x - 1, y - 1);

            if (congelado) {
                ctx.fillStyle = 'rgba(154,216,255,.3)';
                ctx.fillRect(x - 1, y - 1, ANCHO_CAMPEON + 2, ALTO_CAMPEON + 2);
            }
            if (j.escudoHasta > t) {
                ctx.fillStyle = 'rgba(95,243,255,.9)';
                const r = 9 + Math.round(Math.sin(t * 12));
                for (let a = 0; a < Math.PI * 2; a += Math.PI / 8) {
                    ctx.fillRect(Math.round(j.x + Math.cos(a) * r), Math.round(y + 7 + Math.sin(a) * r), 1, 1);
                }
            }
            // Arriba de la cabeza: sus corazones (de su color si le quedan, oscuros si no)
            dibujarCorazones(j, Math.round(j.x), y - 5, t);
        });
    }

    // Corazones pixel de 3x3: uno por vida
    function dibujarCorazones(j, cx, y, t) {
        j.dolor = Math.max(0, (j.dolor ?? 0) - 0.03);
        const temblor = j.dolor > 0 ? Math.round((Math.random() - 0.5) * 2) : 0;
        for (let k = 0; k < 3; k++) {
            const x = cx - 6 + k * 4 + temblor;
            ctx.fillStyle = k < j.vida ? (j.dolor > 0.5 ? '#ffffff' : '#e0245e') : '#2a1a55';
            ctx.fillRect(x, y, 1, 1);
            ctx.fillRect(x + 2, y, 1, 1);
            ctx.fillRect(x, y + 1, 3, 1);
            ctx.fillRect(x + 1, y + 2, 1, 1);
        }
    }

    // Caído: acostado, gris, con una barrita de "reiniciando"
    function dibujarCaido(j, p, t) {
        capa.width = ANCHO_CAMPEON + 2;
        capa.height = ALTO_CAMPEON + 2;
        ctxCapa.clearRect(0, 0, capa.width, capa.height);
        dibujarCampeon(ctxCapa, j.campeon, j.color, 1, 1, 1, 'quieto');
        j.x = j.x ?? p.x;
        ctx.save();
        ctx.filter = 'grayscale(1) brightness(.55)';
        ctx.translate(Math.round(j.x), Math.round(p.y) + ALTO_CAMPEON - 4);
        ctx.rotate(-Math.PI / 2);
        ctx.drawImage(capa, -capa.width / 2, -capa.height / 2);
        ctx.restore();
        const falta = Math.max(0, j.caidoHasta - t) / 8;
        ctx.fillStyle = '#2a1a55';
        ctx.fillRect(Math.round(j.x) - 7, Math.round(p.y) + 1, 14, 2);
        ctx.fillStyle = '#5ff3ff';
        ctx.fillRect(Math.round(j.x) - 7, Math.round(p.y) + 1, Math.round(14 * (1 - falta)), 2);
    }

    function dibujarProyectiles(dt) {
        const c = centroJefe();
        proyectiles = proyectiles.filter((p) => {
            p.y -= 220 * dt;
            p.x += (c.x - p.x) * Math.min(1, dt * 4);
            if (p.y <= c.y + (Math.random() - 0.5) * 20) {
                for (let n = 0; n < (p.critico ? 12 : 4); n++) particula(p.x, p.y, p.color);
                return false;
            }
            dibujarProyectil(ctx, p.tipo, p.color, p.x, p.y, p.critico ? 2 : 1);
            return true;
        });
    }

    function dibujarCombo(t) {
        const c = centroJefe();
        const pos = posiciones();
        rayosCombo = rayosCombo.filter((r) => {
            if (r.hasta < t) return false;
            const p = pos.get(r.id);
            if (!p) return false;
            // Un rayo grueso en zigzag desde el campeón hasta el jefe
            ctx.fillStyle = r.color;
            const pasos = 24;
            for (let k = 0; k <= pasos; k++) {
                const f = k / pasos;
                const x = p.x + (c.x - p.x) * f + (k % pasos ? (Math.random() - 0.5) * 6 : 0);
                const y = p.y + (c.y - p.y) * f;
                ctx.fillRect(Math.round(x) - 1, Math.round(y) - 1, 3, 3);
                ctx.fillStyle = k % 3 ? r.color : '#ffffff';
            }
            return true;
        });
    }

    function dibujarParticulas(dt) {
        particulas = particulas.filter((p) => {
            p.vida -= dt * 1.4;
            p.vy += 90 * dt;
            p.x += p.vx * dt;
            p.y += p.vy * dt;
            if (p.vida <= 0) return false;
            ctx.globalAlpha = Math.min(1, p.vida * 2);
            ctx.fillStyle = p.color;
            ctx.fillRect(Math.round(p.x), Math.round(p.y), 1, 1);
            ctx.globalAlpha = 1;
            return true;
        });
    }

    let anterior = ahora();
    function animar() {
        const t = ahora();
        const dt = Math.min(0.05, t - anterior);
        anterior = t;
        ctx.clearRect(0, 0, W, H);
        dibujarCarriles(t);
        dibujarCombo(t);
        dibujarJugadores(t, dt);
        dibujarProyectiles(dt);
        dibujarParticulas(dt);
        requestAnimationFrame(animar);
    }
    animar();
}
