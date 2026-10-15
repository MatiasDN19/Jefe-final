<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Alguien entró a la batalla. Lleva los datos para dibujar a su campeón
 * en la pantalla grande, y la vida nueva del jefe (que se fortaleció).
 */
class JugadorUnido implements ShouldBroadcastNow
{
    public function __construct(
        public array $jugador,   // id, nombre, color, campeon, carril
        public int $vida,
        public int $vidaMax,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('jefe')];
    }

    public function broadcastAs(): string
    {
        return 'JugadorUnido';
    }
}
