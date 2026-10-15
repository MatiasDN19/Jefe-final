/*
 * La pantalla de VICTORIA de la pantalla grande.
 *
 * Es una secuencia, como en los juegos de arcade:
 *   1. Título 3D "JEFE DERROTADO" que cae letra por letra
 *   2. Estadísticas del equipo contando (daño total, jugadores, golpe final)
 *   3. El podio: suben los escalones 3º → 2º → (redoble) → 1º,
 *      y cada campeón "cae" parado arriba de su escalón
 *   4. Fuegos artificiales y corona para el MVP
 *   5. El resto del ranking
 *
 * pantalla.blade.php la usa con window.VictoriaPantalla.mostrar(e) / .ocultar()
 */
import { dibujarCampeon, dibujarAura, respirando } from './campeones';
import { sonido } from './sonidos'; // (el navegador recién deja sonar después de un clic en la página)

const $ = (id) => document.getElementById(id);
const esperar = (ms) => new Promise((r) => setTimeout(r, ms));

// Cada vez que se muestra la victoria sube este número. Si mientras corre
// la secuencia alguien reinicia al jefe, la secuencia vieja se da cuenta
// (su número ya no es el actual) y deja de hacer cosas.
let secuencia = 0;
let animando = false;
const sprites = []; // { ctx, campeon, color, semilla, corona }
const particulas = [];
let fuegosHasta = 0;
let coloresFiesta = ['#5ff3ff', '#ffc53d', '#d6409f'];

// ---------------------------------------------------------------------
// Utilidades
// ---------------------------------------------------------------------
function armarTitulo(caja, lineas) {
    caja.replaceChildren();
    let i = 0;
    lineas.forEach(([texto, clase]) => {
        const linea = document.createElement('span');
        linea.className = 'linea ' + clase;
        [...texto].forEach((ch) => {
            const l = document.createElement('span');
            l.className = 'letra';
            l.textContent = ch === ' ' ? ' ' : ch;
            l.style.setProperty('--i', i++);
            linea.appendChild(l);
        });
        caja.appendChild(linea);
    });
}

async function contar(el, hasta, ms, id) {
    const inicio = performance.now();
    let ultimoTic = 0;
    await new Promise((fin) => {
        (function paso(t) {
            if (id !== secuencia) return fin();
            const p = Math.min(1, (t - inicio) / ms);
            el.textContent = Math.round(hasta * (1 - Math.pow(1 - p, 3)));
            if (t - ultimoTic > 70 && p < 1) { sonido('tick'); ultimoTic = t; }
            p < 1 ? requestAnimationFrame(paso) : fin();
        })(performance.now());
    });
}

// Corona pixelada (cada letra de la grilla es un color)
const CORONA = [
    'Y..Y..Y',
    'YY.Y.YY',
    'YYYYYYY',
    'YRYWYRY',
    'YYYYYYY',
];
const COLOR_CORONA = { Y: '#ffc53d', R: '#e0245e', W: '#ffffff' };
function dibujarCorona(ctx, cx, y, tam, t) {
    const sube = Math.round(Math.sin(t * 3) * tam); // flota un poquito
    CORONA.forEach((fila, f) => [...fila].forEach((c, k) => {
        if (c === '.') return;
        ctx.fillStyle = COLOR_CORONA[c];
        ctx.fillRect(cx + (k - 3.5) * tam, y + f * tam + sube, tam, tam);
    }));
    // brillito que titila
    if (Math.sin(t * 5) > 0.7) {
        ctx.fillStyle = '#fff';
        ctx.fillRect(cx + 2.5 * tam, y - tam + sube, tam, tam);
    }
}

// ---------------------------------------------------------------------
// Animación: campeones del podio + fuegos artificiales
// ---------------------------------------------------------------------
function animar(t) {
    if (!animando) return;
    const s = t / 1000;

    // Campeones del podio
    sprites.forEach((sp) => {
        const { ctx } = sp;
        const W = ctx.canvas.width, H = ctx.canvas.height, tam = sp.tam;
        ctx.clearRect(0, 0, W, H);
        const x = (W - 14 * tam) / 2;
        const y = H - 15 * tam;
        dibujarAura(ctx, sp.campeon, sp.color, W / 2, y + 7 * tam, tam, s + sp.semilla);
        ctx.globalAlpha = 1;
        // El 1º festeja: alterna entre quieto y "ataque" como si saltara
        const festeja = sp.corona && Math.sin(s * 6) > 0.3;
        dibujarCampeon(ctx, sp.campeon, sp.color, x, y - (festeja ? tam * 2 : 0), tam,
            festeja ? 'ataque' : 'quieto', false, respirando(s, sp.semilla));
        if (sp.corona) dibujarCorona(ctx, W / 2, y - 8 * tam - (festeja ? tam * 2 : 0), tam, s);
    });

    // Fuegos artificiales (canvas en baja resolución = pixel art)
    const fc = $('v-fuegos');
    const fx = fc.getContext('2d');
    fx.clearRect(0, 0, fc.width, fc.height);
    if (t < fuegosHasta && Math.random() < 0.05) explotar(fc);
    for (let i = particulas.length - 1; i >= 0; i--) {
        const p = particulas[i];
        p.x += p.vx; p.y += p.vy; p.vy += 0.03; p.vx *= 0.985; p.vida -= 0.012;
        if (p.vida <= 0) { particulas.splice(i, 1); continue; }
        fx.globalAlpha = Math.min(1, p.vida * 1.5);
        fx.fillStyle = p.vida > 0.75 ? '#ffffff' : p.color;
        fx.fillRect(Math.round(p.x), Math.round(p.y), p.tam, p.tam);
    }
    fx.globalAlpha = 1;

    requestAnimationFrame(animar);
}

function explotar(fc, x, y) {
    x ??= fc.width * (0.15 + Math.random() * 0.7);
    y ??= fc.height * (0.1 + Math.random() * 0.4);
    const color = coloresFiesta[Math.floor(Math.random() * coloresFiesta.length)];
    const n = 40 + Math.floor(Math.random() * 20);
    for (let k = 0; k < n; k++) {
        const a = (k / n) * Math.PI * 2;
        const v = 0.8 + Math.random() * 1.4;
        particulas.push({ x, y, vx: Math.cos(a) * v, vy: Math.sin(a) * v, vida: 1, color, tam: Math.random() < 0.3 ? 2 : 1 });
    }
    sonido('bloqueo');
}

// ---------------------------------------------------------------------
// Armado del podio
// ---------------------------------------------------------------------
const ALTURAS = { 1: 230, 2: 160, 3: 110 };

function armarEscalon(j, puesto) {
    const escalon = document.createElement('div');
    escalon.className = `escalon p${puesto}`;

    const lienzo = document.createElement('canvas');
    const tam = puesto === 1 ? 9 : 7;
    lienzo.width = 26 * tam;
    lienzo.height = 26 * tam;
    lienzo.className = 'campeon-podio';
    sprites.push({ ctx: lienzo.getContext('2d'), campeon: j.campeon, color: j.color, tam, semilla: puesto * 1.7, corona: puesto === 1 });

    const nombre = document.createElement('div');
    nombre.className = 'nombre';
    nombre.textContent = j.nombre; // textContent: el nombre nunca se interpreta como HTML
    nombre.style.color = j.color;

    const danio = document.createElement('div');
    danio.className = 'danio';
    danio.innerHTML = '<span class="cuenta">0</span> de daño';

    const bloque = document.createElement('div');
    bloque.className = 'bloque';
    bloque.style.setProperty('--alto', ALTURAS[puesto] + 'px');
    bloque.style.setProperty('--color', j.color);
    bloque.textContent = puesto;

    escalon.append(lienzo, nombre, danio, bloque);
    return escalon;
}

// ---------------------------------------------------------------------
// La secuencia completa
// ---------------------------------------------------------------------
async function mostrar(e) {
    const id = ++secuencia;
    const ranking = e.ranking;
    const total = ranking.reduce((s, j) => s + j.danio, 0);
    coloresFiesta = [...new Set(ranking.map((j) => j.color)), '#ffc53d', '#ffffff'];

    // Preparar todo vacío
    const pantalla = $('victoria');
    armarTitulo($('v-titulo'), [['JEFE', 'chica'], ['DERROTADO', 'grande']]);
    $('v-sub').classList.remove('ver');
    ['v-stats', 'v-resto'].forEach((x) => $(x).classList.remove('ver'));
    $('v-total').textContent = '0';
    $('v-jugadores').textContent = '0';
    $('v-golpe').textContent = e.golpeFinal;
    const quien = ranking.find((j) => j.nombre === e.golpeFinal);
    $('v-golpe').style.color = quien?.color ?? 'var(--cian)';

    sprites.length = 0;
    particulas.length = 0;
    const lugares = { 2: null, 1: null, 3: null };
    [1, 2, 3].forEach((p) => { if (ranking[p - 1]) lugares[p] = armarEscalon(ranking[p - 1], p); });
    $('v-podio').replaceChildren(...[2, 1, 3].filter((p) => lugares[p]).map((p) => lugares[p])); // 2º - 1º - 3º

    $('v-resto').replaceChildren(...ranking.slice(3, 10).map((j, i) => {
        const li = document.createElement('li');
        const pos = document.createElement('b'); pos.textContent = `${i + 4}.`;
        const punto = document.createElement('i'); punto.style.background = j.color;
        const nom = document.createElement('span'); nom.textContent = `${j.nombre} · ${j.danio}`;
        li.append(pos, punto, nom);
        return li;
    }));

    const fc = $('v-fuegos');
    fc.width = Math.ceil(innerWidth / 4);
    fc.height = Math.ceil(innerHeight / 4);

    pantalla.classList.add('visible');
    if (!animando) { animando = true; requestAnimationFrame(animar); }
    sonido('victoria');

    // 1) Título
    await esperar(1500); if (id !== secuencia) return;
    $('v-sub').classList.add('ver');

    // 2) Estadísticas del equipo
    await esperar(600); if (id !== secuencia) return;
    $('v-stats').classList.add('ver');
    await Promise.all([
        contar($('v-total'), total, 1600, id),
        contar($('v-jugadores'), ranking.length, 800, id),
    ]);

    // 3) Podio: 3º, 2º, redoble... ¡1º!
    for (const p of [3, 2]) {
        await esperar(500); if (id !== secuencia) return;
        if (!lugares[p]) continue;
        lugares[p].classList.add('sube');
        sonido('impacto');
        await esperar(450);
        contar(lugares[p].querySelector('.cuenta'), ranking[p - 1].danio, 900, id);
    }

    await esperar(700); if (id !== secuencia) return;
    if (lugares[1]) {
        // redoble: tics cada vez más rápidos
        for (let k = 0; k < 10; k++) { sonido('tick'); await esperar(150 - k * 11); }
        if (id !== secuencia) return;
        lugares[1].classList.add('sube');
        pantalla.classList.add('sacudida');
        setTimeout(() => pantalla.classList.remove('sacudida'), 600);
        sonido('perfecto');
        fuegosHasta = performance.now() + 9000;
        for (let k = 0; k < 4; k++) explotar(fc);
        await esperar(450);
        contar(lugares[1].querySelector('.cuenta'), ranking[0].danio, 1200, id);
    }

    // 4) El resto de la tabla
    await esperar(1400); if (id !== secuencia) return;
    if (ranking.length > 3) $('v-resto').classList.add('ver');
}

function ocultar() {
    secuencia++; // corta cualquier secuencia que siga corriendo
    $('victoria')?.classList.remove('visible');
    animando = false;
    fuegosHasta = 0;
}

window.VictoriaPantalla = { mostrar, ocultar };
