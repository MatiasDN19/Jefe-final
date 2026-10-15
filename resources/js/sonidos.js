/**
 * Efectos de sonido retro, generados en el momento con la Web Audio API.
 * No hay ningún archivo de audio: cada sonido es una lista de notas que
 * toca un oscilador (como los chips de sonido de las consolas viejas).
 *
 *   - 'square'   = onda cuadrada: el sonido "chiptune" clásico
 *   - 'sawtooth' = diente de sierra: más áspero, para golpes y alarmas
 *   - 'triangle' = triangular: más suave, para cosas mágicas
 *
 * Ojo: los navegadores solo dejan sonar audio DESPUÉS de que el usuario
 * tocó algo (clic, tecla). Por eso el primer sonido sale con el primer toque.
 */

// [frecuencia inicial, frecuencia final, cuándo empieza (s), duración (s)]
const SONIDOS = {
    tick:     { onda: 'square',   vol: 0.03, notas: [[1400, 1400, 0, 0.03]] },
    mover:    { onda: 'square',   vol: 0.03, notas: [[600, 900, 0, 0.04]] },
    golpe:    { onda: 'square',   vol: 0.05, notas: [[330, 110, 0, 0.07]] },
    critico:  { onda: 'sawtooth', vol: 0.06, notas: [[880, 220, 0, 0.12], [1320, 1320, 0.05, 0.08]] },
    perfecto: { onda: 'square',   vol: 0.06, notas: [[1047, 1047, 0, 0.06], [1568, 1568, 0.06, 0.06], [2093, 2093, 0.12, 0.12]] },
    danio:    { onda: 'sawtooth', vol: 0.07, notas: [[180, 60, 0, 0.25]] },
    caer:     { onda: 'sawtooth', vol: 0.07, notas: [[400, 50, 0, 0.6]] },
    escudo:   { onda: 'triangle', vol: 0.09, notas: [[300, 1200, 0, 0.2]] },
    bloqueo:  { onda: 'triangle', vol: 0.09, notas: [[1500, 1500, 0, 0.05], [2000, 2000, 0.05, 0.1]] },
    especial: { onda: 'triangle', vol: 0.09, notas: [[200, 1600, 0, 0.35]] },
    combo:    { onda: 'square',   vol: 0.05, notas: [[784, 784, 0, 0.05], [1175, 1175, 0.05, 0.08]] },
    alerta:   { onda: 'sawtooth', vol: 0.05, notas: [[880, 880, 0, 0.08], [660, 660, 0.12, 0.08]] },
    fase:     { onda: 'sawtooth', vol: 0.07, notas: [[110, 220, 0, 0.3], [110, 330, 0.3, 0.3], [110, 440, 0.6, 0.5]] },
    start:    { onda: 'square',   vol: 0.07, notas: [[523, 523, 0, 0.08], [659, 659, 0.07, 0.08], [784, 784, 0.14, 0.08], [1047, 1047, 0.21, 0.08], [1568, 1568, 0.3, 0.2]] },
    listo:    { onda: 'square',   vol: 0.06, notas: [[784, 784, 0, 0.1], [1047, 1047, 0.1, 0.1], [1568, 1568, 0.2, 0.18]] },
    error:    { onda: 'square',   vol: 0.06, notas: [[220, 220, 0, 0.08], [180, 180, 0.08, 0.12]] },
    victoria: { onda: 'square',   vol: 0.07, notas: [[523, 523, 0, 0.12], [659, 659, 0.12, 0.12], [784, 784, 0.24, 0.12], [1047, 1047, 0.36, 0.4]] },
    impacto:  { onda: 'sawtooth', vol: 0.08, notas: [[90, 40, 0, 0.4]] },
};

let ctx = null;

// El silencio se recuerda en el navegador (try/catch: puede no haber storage)
let mudo = false;
try { mudo = localStorage.getItem('jefe-mudo') === '1'; } catch { /* nada */ }

export function estaMudo() { return mudo; }

export function alternarSilencio() {
    mudo = !mudo;
    try { localStorage.setItem('jefe-mudo', mudo ? '1' : '0'); } catch { /* nada */ }
    return mudo;
}

export function sonido(nombre) {
    if (mudo) return;
    const s = SONIDOS[nombre];
    if (!s) return;
    try {
        ctx ??= new (window.AudioContext || window.webkitAudioContext)();
        if (ctx.state === 'suspended') ctx.resume();
        s.notas.forEach(([desde, hasta, cuando, dur]) => {
            const osc = ctx.createOscillator();
            const vol = ctx.createGain();
            const t0 = ctx.currentTime + cuando;
            osc.type = s.onda;
            osc.frequency.setValueAtTime(desde, t0);
            osc.frequency.exponentialRampToValueAtTime(Math.max(20, hasta), t0 + dur);
            vol.gain.setValueAtTime(s.vol, t0);
            vol.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
            osc.connect(vol).connect(ctx.destination);
            osc.start(t0);
            osc.stop(t0 + dur + 0.02);
        });
    } catch { /* sin audio, el juego sigue igual */ }
}
