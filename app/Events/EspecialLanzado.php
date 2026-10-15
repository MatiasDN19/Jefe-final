<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Alguien lanzó su especial. Si se juntan varios a tiempo, $combo trae
 * los nombres y el daño del ataque combinado; si no, viene en null.
 */
class EspecialLanzado implements ShouldBroadcastNow
{
    public function __construct(
        public string $jugadorId,
        public string $nombre,
        public string $color,
        public int $listos,      // cuántos especiales hay juntos ahora
        public int $necesarios,  // cuántos hacen falta para el combo
        public ?array $combo,
        public int $vida,
        public int $vidaMax,
        public array $ranking,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('jefe')];
    }

    public function broadcastAs(): string
    {
        return 'EspecialLanzado';
    }
}
