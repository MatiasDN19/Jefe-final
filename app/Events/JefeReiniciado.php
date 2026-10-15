<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Se emite cuando revivimos al jefe para jugar otra ronda.
 * Así todas las pantallas y celus vuelven a empezar sin recargar.
 */
class JefeReiniciado implements ShouldBroadcastNow
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
        return 'JefeReiniciado';
    }
}
