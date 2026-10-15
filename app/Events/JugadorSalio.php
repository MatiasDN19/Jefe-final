<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Un jugador abandonó la partida. El jefe pierde la vida extra que tenía por él.
 */
class JugadorSalio implements ShouldBroadcastNow
{
    public function __construct(
        public string $jugadorId,
        public string $nombre,
        public int $vida,
        public int $vidaMax,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('jefe')];
    }

    public function broadcastAs(): string
    {
        return 'JugadorSalio';
    }
}
