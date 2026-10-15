<?php

use App\Events\AtaquePreparado;
use App\Events\ImpactoJefe;
use App\Services\Juego;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * El contraataque del jefe, en DOS pasos:
 *
 *   1. AVISO:   el jefe apunta a uno o más carriles y se lo avisa a todos.
 *   2. IMPACTO: unos segundos después, el servidor mira dónde está cada
 *               jugador: a los que siguen ahí les saca un corazón.
 *
 * El scheduler ejecuta este comando cada 5 segundos, pero es el JEFE
 * quien decide si ataca: depende de su fase (Juego::FASES). Enojado,
 * ataca más seguido, avisa con menos tiempo y pega en más carriles.
 *
 * Hay que dejarlo corriendo con:  php artisan schedule:work
 * (iniciar-juego.sh ya lo hace). Para probar a mano: php artisan jefe:contraatacar
 */
Artisan::command('jefe:contraatacar', function () {
    $carriles = Juego::apuntar();

    if ($carriles === null) {
        return; // muerto, sin jugadores o todavía no le toca
    }

    $fase = Juego::fase();
    $aviso = Juego::FASES[$fase]['aviso'];

    // Paso 1: aviso
    broadcast(new AtaquePreparado($carriles, $aviso, $fase));
    $this->info('Apunta a: ' . implode(', ', $carriles) . " (fase {$fase})");

    // Les damos tiempo a los jugadores para esquivar...
    usleep((int) ($aviso * 1_000_000));

    // ...sacamos una "foto" de dónde está cada uno cuando termina el aviso...
    $foto = Juego::carrilesActuales();

    // ...y esperamos un poquito más: los "me moví" que salieron a tiempo
    // del celu pero vienen demorados por el túnel, todavía pueden llegar.
    usleep((int) (Juego::MARGEN_LATENCIA * 1_000_000));

    // Paso 2: impacto (solo pega si estabas en el carril en la foto Y ahora)
    $r = Juego::impactar($carriles, $foto);
    broadcast(new ImpactoJefe($carriles, $r['golpeados'], $r['bloqueados'], $r['posiciones']));

    $this->info('Golpeados: ' . count($r['golpeados']) . ' · Bloqueados: ' . count($r['bloqueados']));
})->purpose('El jefe contraataca');

Schedule::command('jefe:contraatacar')->everyFiveSeconds();
