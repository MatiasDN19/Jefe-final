<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Paso 1 del contraataque: el jefe AVISA a qué carriles va a pegar.
 * Los jugadores tienen unos segundos para moverse o usar el escudo.
 * En la fase 3 apunta a dos carriles a la vez.
 */
class AtaquePreparado implements ShouldBroadcastNow
{
    public function __construct(
        public array $carriles,
        public float $segundos,
        public int $fase,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('jefe')];
    }

    public function broadcastAs(): string
    {
        return 'AtaquePreparado';
    }
}
