<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Paso 2 del contraataque: el golpe cae. El servidor ya decidió a quién
 * le pegó (pierde un corazón, o cae si no le quedan) y a quién salvó el escudo,
 * según dónde tenía anotado a cada uno.
 *
 * Va por el canal público: cada celu se fija si su ID está en la lista.
 * Si alguien ignora el mensaje igual no puede atacar, porque el servidor
 * rechaza las acciones de los aturdidos y caídos.
 */
class ImpactoJefe implements ShouldBroadcastNow
{
    public function __construct(
        public array $carriles,
        public array $golpeados,   // [{id, nombre, vida, caido, segundos}]
        public array $bloqueados,
        public array $posiciones = [], // [id => carril]: dónde quedó cada uno, para re-sincronizar pantallas
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('jefe')];
    }

    public function broadcastAs(): string
    {
        return 'ImpactoJefe';
    }
}
