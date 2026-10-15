<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Un jugador cambió de carril. La pantalla grande lo mueve.
 */
class JugadorMovido implements ShouldBroadcastNow
{
    public function __construct(
        public string $jugadorId,
        public int $carril,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('jefe')];
    }

    public function broadcastAs(): string
    {
        return 'JugadorMovido';
    }
}
