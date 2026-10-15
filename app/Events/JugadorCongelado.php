<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * El contraataque del jefe. Va por el canal público, pero lleva el ID
 * del jugador congelado: cada celu se fija si es el suyo.
 *
 * ¿Y si alguien ignora el mensaje? No importa: el que decide si un
 * ataque cuenta es el servidor (Juego::atacar revisa congelado_hasta).
 * Lo que hace el celu es solo visual.
 */
class JugadorCongelado implements ShouldBroadcastNow
{
    public function __construct(
        public string $jugadorId,
        public string $nombre,
        public string $color,
        public int $segundos,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('jefe')];
    }

    public function broadcastAs(): string
    {
        return 'JugadorCongelado';
    }
}
