<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Se emite una sola vez, cuando la vida llega a 0.
 * Lleva el ranking COMPLETO para armar el podio final.
 */
class JefeDerrotado implements ShouldBroadcastNow
{
    public function __construct(
        public string $golpeFinal, // nombre de quien dio el último golpe
        public array $ranking,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('jefe')];
    }

    public function broadcastAs(): string
    {
        return 'JefeDerrotado';
    }
}
