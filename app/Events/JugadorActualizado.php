<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Un jugador editó su perfil (nombre, color o campeón).
 */
class JugadorActualizado implements ShouldBroadcastNow
{
    public function __construct(
        public array $jugador,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('jefe')];
    }

    public function broadcastAs(): string
    {
        return 'JugadorActualizado';
    }
}
