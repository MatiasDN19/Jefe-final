<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * El jefe perdió suficiente vida como para pasar de fase: se enoja.
 */
class FaseCambiada implements ShouldBroadcastNow
{
    public function __construct(
        public int $fase,
        public string $nombre,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('jefe')];
    }

    public function broadcastAs(): string
    {
        return 'FaseCambiada';
    }
}
