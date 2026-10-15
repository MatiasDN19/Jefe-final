<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Se borraron todos los jugadores: todos vuelven al menú.
 */
class NuevaPartida implements ShouldBroadcastNow
{
    public function __construct(
        public int $vida,
        public int $vidaMax,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('jefe')];
    }

    public function broadcastAs(): string
    {
        return 'NuevaPartida';
    }
}
