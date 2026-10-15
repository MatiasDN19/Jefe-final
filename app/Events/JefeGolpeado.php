<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Una ráfaga de ataques normales que pegó (uno o varios golpes juntos). Las propiedades públicas son
 * exactamente lo que recibe el navegador en "e".
 */
class JefeGolpeado implements ShouldBroadcastNow
{
    public function __construct(
        public string $jugadorId,
        public string $nombre,
        public string $color,
        public string $campeon,
        public int $danio,
        public bool $critico,
        public int $vida,
        public int $vidaMax,
        public array $ranking,   // solo el top 5
        public int $golpes = 1,  // cuántos golpes venían en la ráfaga ($danio es la suma)
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('jefe')];
    }

    public function broadcastAs(): string
    {
        return 'JefeGolpeado';
    }
}
