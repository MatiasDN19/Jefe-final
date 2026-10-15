<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Un jugador prendió su escudo.
 */
class EscudoActivado implements ShouldBroadcastNow
{
    public function __construct(
        public string $jugadorId,
        public int $segundos,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('jefe')];
    }

    public function broadcastAs(): string
    {
        return 'EscudoActivado';
    }
}
