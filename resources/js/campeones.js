/**
 * Los 3 campeones: sprites pixel art y cómo dibujarlos.
 *
 * Igual que el jefe, cada letra es un color y "." es vacío.
 * La letra X es especial: se pinta con el COLOR QUE ELIGIÓ EL JUGADOR,
 * así cada campeón queda personalizado.
 *
 * Cada campeón tiene 2 cuadros (frames):
 *   - quieto: la pose normal
 *   - ataque: la pose cuando pega
 * La animación de "respirar", saltar, etc. la hacemos moviendo el dibujo.
 */

export const PALETA_CAMPEONES = {
    K: '#1a1033', // contorno
    G: '#a9adc6', // metal claro
    g: '#5b5f7a', // metal oscuro
    P: '#2a1a55', // tela oscura
    V: '#4b2f9a', // tela violeta
    L: '#7a5cd6', // tela clara
    D: '#05050c', // sombra
    W: '#e8e4ff', // filo / brillo
    S: '#8a5a3c', // madera
    s: '#5c3a24', // madera oscura
};

export const CAMPEONES = {
    // ---------- FIREWALL: caballero con un escudo gigante ----------
    firewall: {
        proyectil: 'bloque',
        quieto: [
            '....KKKK......',
            '...KggggK.....',
            '..KgGGGGgK....',
            '..KgXXXXgK....',
            '..KgGGGGgK....',
            '...KggggK.....',
            '.KKgGGGGgKKK..',
            'KGGKgGGgKXXXK.',
            'KgGKgGGgKXWXXK',
            '.KKKgggKKXWWXK',
            '...KgGGgKXXWXK',
            '...KgKKgKKXXK.',
            '...KgK.KgKKK..',
            '..KKK...KKK...',
        ],
        ataque: [
            '....KKKK......',
            '...KggggK.....',
            '..KgGGGGgK....',
            '..KgXXXXgK....',
            '..KgGGGGgK....',
            '...KggggK..KK.',
            '.KKgGGGGgKKXXK',
            'KGGKgGGgKKXWXK',
            'KgGKgGGgKXWWXK',
            '.KKKgggKKXXWXK',
            '...KgGGgKKXXK.',
            '...KgKKgK.KK..',
            '..KgK..KgK....',
            '.KKK....KKK...',
        ],
    },

    // ---------- EXPLOIT: encapuchado con dos dagas ----------
    exploit: {
        proyectil: 'corte',
        quieto: [
            '.....KKK......',
            '....KPPPK.....',
            '...KPVVVPK....',
            '..KPVDDDVPK...',
            '..KPDXDXDPK...',
            '..KPVDDDVPK...',
            '...KPVVVPK....',
            '.WKKPVLVPKKW..',
            '.KWKPVLVPKWK..',
            '..KKPVVVPKK...',
            '...KPPVPPK....',
            '...KPK.KPK....',
            '...KPK.KPK....',
            '..KKK...KKK...',
        ],
        ataque: [
            '.....KKK...W..',
            '....KPPPK.WK..',
            '...KPVVVPKWK..',
            '..KPVDDDVPK...',
            '..KPDXDXDPK...',
            '..KPVDDDVPK...',
            'W..KPVVVPKK...',
            'KWKKPVLVPK....',
            '.KKKPVLVPK....',
            '...KPVVVPK....',
            '...KPPVPPK....',
            '..KPK..KPK....',
            '.KPK....KPK...',
            'KKK......KKK..',
        ],
    },

    // ---------- DEBUGGER: mago con bastón-cursor ----------
    debugger: {
        proyectil: 'cursor',
        quieto: [
            '.....KK....X..',
            '....KVVK..XWX.',
            '...KVVVVK..X..',
            '..KVVXVVVK.S..',
            '.KKKKKKKKKKS..',
            '...KDDDDK..S..',
            '...KDXDXK..S..',
            '..KVVVVVVKKS..',
            '.KVVLVVVVVKS..',
            '.KVVLXXVVKKS..',
            '..KVVVVVVK.S..',
            '..KVVVVVVK.s..',
            '..KVVKKVVK.s..',
            '..KKK..KKK....',
        ],
        ataque: [
            '.....KK..XXX..',
            '....KVVK.XWX..',
            '...KVVVVK.XXX.',
            '..KVVXVVVKS...',
            '.KKKKKKKKKS...',
            '...KDDDDKS....',
            '...KDXDXKS....',
            '..KVVVVVVK....',
            '.KVVLVVVVVK...',
            '.KVVLXXVVVK...',
            '..KVVVVVVK....',
            '..KVVVVVVK....',
            '.KVVK..KVVK...',
            '.KKK....KKK...',
        ],
    },
};

export const ANCHO_CAMPEON = 14;
export const ALTO_CAMPEON = 14;

// Aclara (f > 0) u oscurece (f < 0) un color hex
function ajustar(hex, f) {
    const n = parseInt(hex.slice(1), 16);
    const m = (v) => Math.round(f > 0 ? v + (255 - v) * f : v * (1 + f));
    return `rgb(${m(n >> 16)},${m((n >> 8) & 255)},${m(n & 255)})`;
}

/*
 * Armar cada sprite cuesta (sombreado, contorno...), así que lo dibujamos
 * UNA vez en un canvas aparte y lo guardamos. Después solo lo "estampamos"
 * con drawImage, que es rapidísimo. Esto se llama CACHÉ, igual que la de
 * Laravel pero en el navegador. Importa en la pantalla grande, con 25 jugadores.
 */
const cache = new Map();

function armarSprite(campeon, color, frame, respira, tam) {
    const clave = [campeon, color, frame, respira ? 1 : 0, tam].join('|');
    if (cache.has(clave)) return cache.get(clave);

    let filas = (CAMPEONES[campeon] ?? CAMPEONES.firewall)[frame];
    // Respirar: la parte de arriba baja un píxel (se "aplasta" el torso)
    if (respira) filas = ['.'.repeat(ANCHO_CAMPEON), ...filas.slice(0, 7), ...filas.slice(8)];

    const lleno = (i, j) => j >= 0 && j < filas.length && i >= 0 && i < ANCHO_CAMPEON && filas[j][i] !== '.';
    const c = document.createElement('canvas');
    c.width = (ANCHO_CAMPEON + 2) * tam; // +2: un píxel de borde de cada lado
    c.height = (ALTO_CAMPEON + 2) * tam;
    const g = c.getContext('2d');

    for (let j = -1; j <= ALTO_CAMPEON; j++) {
        for (let i = -1; i <= ANCHO_CAMPEON; i++) {
            const x = (i + 1) * tam;
            const y = (j + 1) * tam;
            if (!lleno(i, j)) {
                // Contorno: los píxeles vacíos pegados al dibujo se pintan oscuros
                if (lleno(i - 1, j) || lleno(i + 1, j) || lleno(i, j - 1) || lleno(i, j + 1)) {
                    g.fillStyle = '#05050c';
                    g.fillRect(x, y, tam, tam);
                }
                continue;
            }
            const letra = filas[j][i];
            let base = letra === 'X' ? color : PALETA_CAMPEONES[letra];
            // Sombreado: con aire arriba se aclara (le da la luz), con aire abajo se oscurece
            if (letra !== 'K' && letra !== 'D') {
                if (!lleno(i, j - 1)) base = ajustar(base, 0.35);
                else if (!lleno(i, j + 1)) base = ajustar(base, -0.35);
            }
            g.fillStyle = base;
            g.fillRect(x, y, tam, tam);
        }
    }
    cache.set(clave, c);
    return c;
}

/**
 * Dibuja un campeón en un canvas 2D.
 *  - (x, y): esquina de arriba a la izquierda del sprite (el contorno sobresale 1 píxel)
 *  - tam: tamaño de cada píxel del sprite
 *  - frame: 'quieto' o 'ataque'
 *  - espejo: true para que mire para el otro lado
 *  - respira: true en el cuadro "inhalando"
 */
export function dibujarCampeon(ctx, campeon, color, x, y, tam, frame = 'quieto', espejo = false, respira = false) {
    const sprite = armarSprite(campeon, color, frame, respira, tam);
    if (espejo) {
        ctx.save();
        ctx.scale(-1, 1);
        ctx.drawImage(sprite, -Math.round(x) - (ANCHO_CAMPEON + 1) * tam, Math.round(y) - tam);
        ctx.restore();
    } else {
        ctx.drawImage(sprite, Math.round(x) - tam, Math.round(y) - tam);
    }
}

/** ¿Está inhalando? Cada campeón respira a su ritmo (la "semilla" lo desfasa) */
export const respirando = (t, semilla = 0) => Math.sin(t * 2.5 + semilla) > 0.4;

/**
 * Los efectos propios de cada clase, alrededor del campeón.
 *  - (cx, cy): centro del sprite, en píxeles del canvas
 *  - ataque: > 0 mientras está pegando (para el efecto de golpe)
 */
export function dibujarAura(ctx, campeon, color, cx, cy, tam, t, ataque = 0) {
    const px = (x, y, c, a = 1, s = 1) => {
        ctx.globalAlpha = a;
        ctx.fillStyle = c;
        ctx.fillRect(Math.round(x), Math.round(y), tam * s, tam * s);
    };

    if (campeon === 'firewall') {
        // Tres fragmentos de escudo que orbitan (los de atrás, más tenues)
        for (let k = 0; k < 3; k++) {
            const a = t * 2 + k * 2.094;
            const x = cx + Math.cos(a) * 9 * tam;
            const y = cy + Math.sin(a) * 2.5 * tam + 2 * tam;
            const adelante = Math.sin(a) > 0;
            px(x, y, color, adelante ? 0.95 : 0.35, 2);
            px(x, y, '#ffffff', adelante ? 0.8 : 0.2);
        }
        // Golpe: onda de choque que se agranda
        if (ataque > 0) {
            const r = (1 - ataque / 0.2) * 13 * tam;
            for (let a = 0; a < Math.PI * 2; a += Math.PI / 10) {
                px(cx + Math.cos(a) * r, cy + Math.sin(a) * r * 0.6, color, ataque * 4);
            }
        }
    } else if (campeon === 'exploit') {
        // Humo de sombra que sube desde los pies
        for (let k = 0; k < 6; k++) {
            const fase = (t * 0.7 + k / 6) % 1;
            const x = cx + Math.sin(k * 7 + t) * 5 * tam;
            const y = cy + 6 * tam - fase * 16 * tam;
            px(x, y, k % 2 ? '#4b2f9a' : '#2a1a55', (1 - fase) * 0.7);
        }
        // Destello en las dagas, cada tanto
        if ((t * 1.3) % 2.2 < 0.12) {
            px(cx - 6 * tam, cy, '#ffffff');
            px(cx + 4 * tam, cy, '#ffffff');
        }
        // Golpe: un tajo en arco de su color
        if (ataque > 0) {
            for (let k = 0; k < 9; k++) {
                const a = -2.4 + k * 0.22;
                px(cx + Math.cos(a) * 10 * tam, cy - 2 * tam + Math.sin(a) * 8 * tam, k % 3 ? color : '#ffffff', ataque * 5);
            }
        }
    } else {
        // Bits que orbitan y titilan alrededor del mago
        for (let k = 0; k < 5; k++) {
            const a = -t * 1.4 + k * 1.257;
            const x = cx + Math.cos(a) * 8 * tam;
            const y = cy - 2 * tam + Math.sin(a) * 6 * tam;
            if (Math.sin(t * 6 + k * 2) > -0.3) px(x, y, k % 2 ? color : '#5ff3ff', 0.85);
        }
        // El cursor del bastón titila
        const punta = { x: cx + 4 * tam, y: cy - 7 * tam };
        if (Math.sin(t * 5) > 0) px(punta.x, punta.y - tam, '#ffffff', 0.9);
        // Golpe: destello de rayitos desde la punta del bastón
        if (ataque > 0) {
            for (let k = 0; k < 8; k++) {
                const a = k * 0.785;
                for (let d = 2; d < 5; d++) px(punta.x + Math.cos(a) * d * tam, punta.y + Math.sin(a) * d * tam, k % 2 ? color : '#ffffff', ataque * 5);
            }
        }
    }
    ctx.globalAlpha = 1;
}

/** Dibuja un proyectil (cada campeón tiene el suyo) centrado en (x, y) */
export function dibujarProyectil(ctx, tipo, color, x, y, tam) {
    ctx.fillStyle = color;
    const px = (i, j, c = color) => { ctx.fillStyle = c; ctx.fillRect(Math.round(x + i * tam), Math.round(y + j * tam), tam, tam); };
    if (tipo === 'corte') {          // un tajo diagonal
        [[-2, 1], [-1, 0], [0, 0], [1, -1], [2, -2]].forEach(([i, j]) => px(i, j));
        px(0, -1, '#ffffff');
    } else if (tipo === 'cursor') {  // una flechita de mouse
        [[0, -2], [0, -1], [1, -1], [0, 0], [1, 0], [2, 0], [0, 1], [1, 1]].forEach(([i, j]) => px(i, j));
        px(0, -1, '#ffffff');
    } else {                         // bloque de energía
        [[-1, -1], [0, -1], [-1, 0], [0, 0]].forEach(([i, j]) => px(i, j));
        px(-1, -1, '#ffffff');
    }
}
