/**
 * Vista previa animada de los campeones en la pantalla de /unirse.
 * Cada tarjeta tiene un <canvas data-campeon="..."> y se pinta
 * con el color que el jugador tenga elegido en ese momento.
 */
import { dibujarCampeon, dibujarAura, respirando, ANCHO_CAMPEON, ALTO_CAMPEON } from './campeones';

const lienzos = [...document.querySelectorAll('canvas[data-campeon]')];
const TAM = 6;

lienzos.forEach((c) => {
    c.width = ANCHO_CAMPEON * TAM + 24;  // margen para el contorno y los efectos
    c.height = ALTO_CAMPEON * TAM + 28;
});

const colorElegido = () => document.querySelector('input[name=color]:checked')?.value ?? '#ffffff';
const campeonElegido = () => document.querySelector('input[name=campeon]:checked')?.value;

let t = 0;
function animar() {
    t++;
    const color = colorElegido();
    const elegido = campeonElegido();

    lienzos.forEach((c, k) => {
        const ctx = c.getContext('2d');
        ctx.clearRect(0, 0, c.width, c.height);
        const activo = c.dataset.campeon === elegido;

        // El elegido salta y ataca cada tanto; los otros solo respiran
        const ciclo = (t + k * 40) % 120;
        const atacando = activo && ciclo < 14;
        const salto = atacando ? -6 : 0;

        // Sombra en el piso
        ctx.fillStyle = 'rgba(0,0,0,.45)';
        ctx.fillRect(14, c.height - 8, c.width - 28, 4);

        const seg = (t + k * 40) / 60;
        if (activo) dibujarAura(ctx, c.dataset.campeon, color, 12 + (ANCHO_CAMPEON * TAM) / 2, 12 + salto + (ALTO_CAMPEON * TAM) / 2, TAM, seg, atacando ? 0.2 - ciclo / 70 : 0);
        ctx.globalAlpha = activo ? 1 : 0.45;
        dibujarCampeon(ctx, c.dataset.campeon, color, 12, 12 + salto, TAM, atacando ? 'ataque' : 'quieto', false, respirando(seg, k));
        ctx.globalAlpha = 1;
    });

    requestAnimationFrame(animar);
}
animar();
