/**
 * El dibujo del jefe, píxel por píxel.
 *
 * Cada letra es un color de la PALETA y cada "." es un píxel vacío.
 * Solo dibujamos la mitad izquierda (y la columna del centro):
 * la derecha se genera sola como espejo, así el jefe queda simétrico.
 *
 * Lo usan las dos vistas:
 *  - pantalla.js lo arma en 3D con Three.js (cada píxel = un cubito)
 *  - control.js lo dibuja en 2D en un canvas chiquito para el celu
 */

export const PALETA = {
    K: '#1a1033', // contorno
    P: '#2a1a55', // sombra violeta
    V: '#4b2f9a', // violeta
    L: '#7a5cd6', // luz violeta
    D: '#05050c', // vacío (el hueco de la capucha)
    C: '#5ff3ff', // núcleo cian
    W: '#ffffff', // brillo del núcleo
    M: '#e0245e', // acento carmesí (cuernos y garras)
};

// Píxeles que "brillan" solos (no les afecta la luz en 3D)
export const BRILLANTES = ['C', 'W', 'M'];

const MITAD = [
    '......M....M',
    '......MM...M',
    '.......M..MM',
    '........KKKK',
    '.......KPPPP',
    '......KPVVVV',
    '......KPVDDD',
    '.....KPVDDDD',
    '.....KPVDDDC',
    '.....KPVDDCW',
    '.....KPVDDDC',
    '.....KPVDDDD',
    '....KPPVVDDD',
    '...KPPVVLVVV',
    '..KPVVLVVVPP',
    '.KPVVLVVVPPC',
    'KPVK.KPVVPPC',
    'KV...KPVVPPL',
    'K....KPVVVPP',
    '.....KPVLVVP',
    '....KPVVLVVP',
    '....KPVVVLVP',
    '...KPV.KPVVP',
    '...KV..KPV.P',
    '..KV...KV..K',
    '..K....K...K',
    '.M.....M....',
];

// Espejamos: la mitad + la mitad dada vuelta (sin repetir la columna central)
export const FILAS = MITAD.map((m) => m + [...m.slice(0, -1)].reverse().join(''));

export const ANCHO = FILAS[0].length;
export const ALTO = FILAS.length;
