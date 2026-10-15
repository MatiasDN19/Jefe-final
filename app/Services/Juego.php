<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Acá vive toda la lógica del juego.
 *
 * Laravel no recuerda nada entre una petición y otra, así que el estado
 * (vida del jefe y jugadores) se guarda en la caché. Cada método que
 * MODIFICA el estado lo hace dentro de un lock, para que dos acciones
 * que llegan al mismo tiempo no se pisen (condición de carrera).
 *
 * Regla de oro: el navegador MUESTRA, el servidor DECIDE.
 */
class Juego
{
    public const NOMBRE_JEFE = 'KERNEL CORRUPTO';

    // El jefe se hace más fuerte con cada jugador que entra
    public const VIDA_POR_JUGADOR = 4000;

    // Daño
    public const DANIO_MIN = 8;
    public const DANIO_MAX = 15;
    public const DANIO_ESPECIAL = 60;          // especial lanzado solo
    public const DANIO_COMBO_POR_JUGADOR = 150; // combo de equipo
    public const MIN_ENTRE_ATAQUES = 0.15;     // segundos (anti autoclicker)
    public const MAX_GOLPES_RAFAGA = 8;        // golpes como máximo por pedido

    // Ataque combinado
    public const COMBO_JUGADORES = 3;
    public const COMBO_VENTANA = 3; // segundos

    // Contraataque
    public const CARRILES = 3;
    public const SEGUNDOS_ACTIVO = 30; // solo ataca si hay gente que jugó hace poco

    // Vida de los jugadores
    public const VIDAS_JUGADOR = 3;      // corazones
    public const SEGUNDOS_ATURDIDO = 2;  // cuando te pega un rayo
    public const SEGUNDOS_CAIDO = 8;     // cuando te quedás sin corazones

    // Tiempo de gracia para el lag: el aviso termina, pero el servidor espera
    // un poquito más antes de decidir, así llegan los "me moví" que venían en camino.
    public const MARGEN_LATENCIA = 0.6;  // segundos

    /**
     * Las fases del jefe. A medida que pierde vida se enoja:
     * ataca más seguido, avisa con menos tiempo y pega en más carriles.
     *   - desde: porcentaje de vida en que empieza la fase
     *   - cada: segundos entre contraataques
     *   - aviso: segundos que tienen los jugadores para esquivar
     *   - carriles: cuántos carriles ataca a la vez
     */
    public const FASES = [
        1 => ['nombre' => 'ESTABLE',        'desde' => 100, 'cada' => 10, 'aviso' => 2.0, 'carriles' => 1],
        2 => ['nombre' => 'SOBRECALENTADO', 'desde' => 66,  'cada' => 5,  'aviso' => 1.6, 'carriles' => 1],
        3 => ['nombre' => 'FUERA DE CONTROL', 'desde' => 33, 'cada' => 5, 'aviso' => 1.4, 'carriles' => 2],
    ];

    /**
     * Los campeones. Cada uno tiene una ventaja distinta.
     * Estos números los usa el SERVIDOR para decidir; el navegador solo
     * los recibe para mostrar las barras de recarga.
     */
    public const CAMPEONES = [
        'firewall' => [
            'nombre' => 'FIREWALL',
            'rol' => 'Tanque',
            'pasiva' => 'Escudo reforzado: dura más y recarga más rápido.',
            'escudo' => 3, 'recarga_escudo' => 6,
            'carga_especial' => 20,
            'danio_critico' => 40, 'zona_critico' => 0.14,
        ],
        'exploit' => [
            'nombre' => 'EXPLOIT',
            'rol' => 'Asesino',
            'pasiva' => 'Punto débil: zona de crítico más ancha y críticos más fuertes.',
            'escudo' => 2, 'recarga_escudo' => 10,
            'carga_especial' => 20,
            'danio_critico' => 60, 'zona_critico' => 0.24,
        ],
        'debugger' => [
            'nombre' => 'DEBUGGER',
            'rol' => 'Mago',
            'pasiva' => 'Compilación rápida: su especial carga casi al doble de velocidad.',
            'escudo' => 2, 'recarga_escudo' => 10,
            'carga_especial' => 11,
            'danio_critico' => 40, 'zona_critico' => 0.14,
        ],
    ];

    // Valores por defecto de un jugador (también "arregla" jugadores viejos)
    private const JUGADOR_BASE = [
        'campeon' => 'firewall',
        'carril' => 1,
        'danio' => 0,
        'vida' => self::VIDAS_JUGADOR,
        'congelado_hasta' => 0, // aturdido o caído: hasta acá no puede actuar
        'escudo_hasta' => 0,
        'escudo_listo' => 0,
        'especial_listo' => 0,
        'ultimo_ataque' => 0,
        'ultimo_golpe' => 0.0,
    ];

    // =====================================================================
    // Lectura del estado
    // =====================================================================

    public static function vida(): int
    {
        return Cache::get('jefe.vida', self::vidaMax());
    }

    public static function vidaMax(): int
    {
        return Cache::get('jefe.vida_max', self::VIDA_POR_JUGADOR);
    }

    /** La fase según el porcentaje de vida que le queda al jefe */
    public static function fase(): int
    {
        $pct = self::vida() / max(1, self::vidaMax()) * 100;

        return $pct > self::FASES[2]['desde'] ? 1 : ($pct > self::FASES[3]['desde'] ? 2 : 3);
    }

    /**
     * Si la vida cruzó a una fase nueva, la anota y la devuelve (si no, null).
     * Así el controlador sabe que tiene que avisarle a todos.
     */
    public static function chequearFase(): ?int
    {
        $fase = self::fase();
        if ($fase > Cache::get('jefe.fase', 1) && self::vida() > 0) {
            Cache::forever('jefe.fase', $fase);

            return $fase;
        }

        return null;
    }

    public static function jugadores(): array
    {
        return array_map(
            fn ($j) => array_merge(self::JUGADOR_BASE, $j),
            Cache::get('jefe.jugadores', [])
        );
    }

    public static function jugador(?string $id): ?array
    {
        return $id ? (self::jugadores()[$id] ?? null) : null;
    }

    public static function campeon(array $jugador): array
    {
        return self::CAMPEONES[$jugador['campeon']] ?? self::CAMPEONES['firewall'];
    }

    /** Lo que la pantalla grande necesita saber de cada jugador para dibujarlo */
    public static function publico(array $j): array
    {
        return [
            'id' => $j['id'],
            'nombre' => $j['nombre'],
            'color' => $j['color'],
            'campeon' => $j['campeon'],
            'carril' => $j['carril'],
            'vida' => $j['vida'],
        ];
    }

    /** Jugadores ordenados por daño. Con $limite = 5, el top 5. */
    public static function ranking(?int $limite = null): array
    {
        $lista = collect(self::jugadores())
            ->sortByDesc('danio')
            ->values()
            ->map(fn ($j) => [
                'nombre' => $j['nombre'],
                'color' => $j['color'],
                'campeon' => $j['campeon'],
                'danio' => $j['danio'],
            ]);

        return ($limite ? $lista->take($limite) : $lista)->all();
    }

    // =====================================================================
    // Acciones de los jugadores
    // =====================================================================

    /** Crea el perfil de un jugador. El jefe gana vida con cada uno. */
    public static function unir(string $nombre, string $color, string $campeon): array
    {
        return self::conLock(function () use ($nombre, $color, $campeon) {
            $jugadores = self::jugadores();
            $vidaAntes = self::vida(); // leerla ANTES de tocar la vida máxima
            $hayJugadores = count($jugadores) > 0;

            $jugador = array_merge(self::JUGADOR_BASE, [
                'id' => Str::random(10),
                'nombre' => $nombre,
                'color' => $color,
                'campeon' => $campeon,
                'carril' => random_int(0, self::CARRILES - 1),
            ]);
            $jugadores[$jugador['id']] = $jugador;
            self::guardarJugadores($jugadores);

            // Si el jefe está vivo, se fortalece (el primer jugador no suma:
            // el jefe ya arranca con la vida de un jugador)
            if ($vidaAntes > 0 && $hayJugadores) {
                Cache::forever('jefe.vida_max', count($jugadores) * self::VIDA_POR_JUGADOR);
                Cache::forever('jefe.vida', $vidaAntes + self::VIDA_POR_JUGADOR);
            }

            return $jugador;
        });
    }

    /** Editar el perfil: nombre, color y campeón (el daño y la vida se mantienen) */
    public static function editar(string $id, string $nombre, string $color, string $campeon): ?array
    {
        return self::conLock(function () use ($id, $nombre, $color, $campeon) {
            $jugadores = self::jugadores();
            if (! isset($jugadores[$id])) {
                return null;
            }
            $jugadores[$id] = array_merge($jugadores[$id], compact('nombre', 'color', 'campeon'));
            self::guardarJugadores($jugadores);

            return $jugadores[$id];
        });
    }

    /** Salir de la partida. El jefe pierde la vida que había ganado por ese jugador. */
    public static function salir(string $id): bool
    {
        return self::conLock(function () use ($id) {
            $jugadores = self::jugadores();
            if (! isset($jugadores[$id])) {
                return false;
            }
            unset($jugadores[$id]);
            self::guardarJugadores($jugadores);

            // Achicamos la vida máxima, manteniendo el mismo porcentaje de vida
            if (self::vida() > 0) {
                $maxViejo = self::vidaMax();
                $maxNuevo = max(1, count($jugadores)) * self::VIDA_POR_JUGADOR;
                Cache::forever('jefe.vida', max(1, (int) round(self::vida() * $maxNuevo / $maxViejo)));
                Cache::forever('jefe.vida_max', $maxNuevo);
            }

            return true;
        });
    }

    /** Borra a todos los jugadores y deja al jefe como nuevo */
    public static function nuevaPartida(): void
    {
        self::conLock(function () {
            self::guardarJugadores([]);
            Cache::forever('jefe.vida_max', self::VIDA_POR_JUGADOR);
            Cache::forever('jefe.vida', self::VIDA_POR_JUGADOR);
            Cache::forever('jefe.fase', 1);
            Cache::forever('jefe.especiales', []);
            Cache::forever('jefe.ultimo_contraataque', 0);
        });
    }

    /**
     * Ataque normal. $critico lo decide la barra de timing del navegador
     * (le creemos, pero el daño y el ritmo los controla el servidor).
     */
    /**
     * Atacar con una RÁFAGA de golpes: el celu junta los golpes que diste
     * mientras esperaba la respuesta anterior y los manda todos juntos.
     * $golpes = [false, true, false]  →  3 golpes, el segundo fue crítico.
     */
    public static function atacar(string $id, array $golpes): array
    {
        return self::conLock(function () use ($id, $golpes) {
            $jugadores = self::jugadores();

            if ($error = self::puedeActuar($jugadores, $id)) {
                return $error;
            }

            // Anti autoclicker: cuántos golpes "entran" en el tiempo que pasó
            // desde la ráfaga anterior (uno cada MIN_ENTRE_ATAQUES segundos).
            // Si alguien manda 50 golpes de una, solo cuentan los que entran.
            $ahora = microtime(true);
            $pasaron = $ahora - $jugadores[$id]['ultimo_golpe'];
            // (redondeamos para tolerar que la red atrase o adelante un poco los pedidos)
            $permitidos = (int) min(self::MAX_GOLPES_RAFAGA, round($pasaron / self::MIN_ENTRE_ATAQUES));
            if ($permitidos < 1) {
                return ['ok' => false, 'motivo' => 'muy_rapido'];
            }
            $golpes = array_slice($golpes, 0, $permitidos);
            $jugadores[$id]['ultimo_golpe'] = $ahora;

            $stats = self::campeon($jugadores[$id]);
            $danios = array_map(
                fn ($critico) => $critico ? $stats['danio_critico'] : random_int(self::DANIO_MIN, self::DANIO_MAX),
                $golpes
            );

            return self::aplicarDanio($jugadores, $id, array_sum($danios)) + [
                'critico' => in_array(true, $golpes, true),
                'danios' => $danios,
                'criticos' => $golpes,
            ];
        });
    }

    /** Cambiar de carril (para esquivar el contraataque) */
    public static function mover(string $id, int $carril): ?array
    {
        return self::conLock(function () use ($id, $carril) {
            $jugadores = self::jugadores();
            if (! isset($jugadores[$id])) {
                return null;
            }
            $jugadores[$id]['carril'] = max(0, min(self::CARRILES - 1, $carril));
            $jugadores[$id]['ultimo_ataque'] = time(); // moverse también es "estar jugando"
            self::guardarJugadores($jugadores);

            return $jugadores[$id];
        });
    }

    /** Activar el escudo, si ya recargó */
    public static function escudo(string $id): array
    {
        return self::conLock(function () use ($id) {
            $jugadores = self::jugadores();
            if ($error = self::puedeActuar($jugadores, $id)) {
                return $error;
            }

            $ahora = time();
            $stats = self::campeon($jugadores[$id]);

            if ($jugadores[$id]['escudo_listo'] > $ahora) {
                return ['ok' => false, 'motivo' => 'recargando', 'faltan' => $jugadores[$id]['escudo_listo'] - $ahora];
            }

            $jugadores[$id]['escudo_hasta'] = $ahora + $stats['escudo'];
            $jugadores[$id]['escudo_listo'] = $ahora + $stats['recarga_escudo'];
            self::guardarJugadores($jugadores);

            return ['ok' => true, 'jugador' => $jugadores[$id], 'segundos' => $stats['escudo'], 'recarga' => $stats['recarga_escudo']];
        });
    }

    /**
     * Lanzar el especial. Pega solo, pero si COMBO_JUGADORES jugadores
     * distintos lo lanzan dentro de COMBO_VENTANA segundos... ¡combo!
     */
    public static function especial(string $id): array
    {
        return self::conLock(function () use ($id) {
            $jugadores = self::jugadores();
            if ($error = self::puedeActuar($jugadores, $id)) {
                return $error;
            }

            $ahora = time();
            $stats = self::campeon($jugadores[$id]);

            if ($jugadores[$id]['especial_listo'] > $ahora) {
                return ['ok' => false, 'motivo' => 'recargando', 'faltan' => $jugadores[$id]['especial_listo'] - $ahora];
            }
            $jugadores[$id]['especial_listo'] = $ahora + $stats['carga_especial'];

            // Anotamos este especial y nos quedamos con los de los últimos segundos
            $recientes = array_filter(
                Cache::get('jefe.especiales', []),
                fn ($e) => $e['t'] >= $ahora - self::COMBO_VENTANA && $e['id'] !== $id
            );
            $recientes[] = ['id' => $id, 't' => $ahora];

            $resultado = self::aplicarDanio($jugadores, $id, self::DANIO_ESPECIAL);
            $resultado['recarga'] = $stats['carga_especial'];
            $resultado['listos'] = count($recientes);
            $resultado['combo'] = null;

            // ¿Se juntaron suficientes? ¡Ataque combinado!
            if (count($recientes) >= self::COMBO_JUGADORES && $resultado['vida'] > 0) {
                $jugadores = self::jugadores(); // aplicarDanio ya guardó
                $porJugador = self::DANIO_COMBO_POR_JUGADOR;
                $total = 0;
                $nombres = [];
                foreach ($recientes as $e) {
                    if (! isset($jugadores[$e['id']])) {
                        continue;
                    }
                    $jugadores[$e['id']]['danio'] += $porJugador;
                    $nombres[] = $jugadores[$e['id']]['nombre'];
                    $total += $porJugador;
                }
                $vida = max(0, self::vida() - $total);
                Cache::forever('jefe.vida', $vida);
                self::guardarJugadores($jugadores);

                $resultado['vida'] = $vida;
                $resultado['derrotado'] = $vida === 0;
                $resultado['combo'] = ['nombres' => $nombres, 'danio' => $total];
                $recientes = []; // se gastaron en el combo
            }

            Cache::forever('jefe.especiales', array_values($recientes));

            return $resultado;
        });
    }

    // =====================================================================
    // El contraataque (lo dispara el scheduler, no un jugador)
    // =====================================================================

    /**
     * Paso 1: ¿le toca atacar? Y si le toca, ¿a qué carriles apunta?
     * Devuelve null si no ataca: está muerto, no hay nadie jugando,
     * o todavía no pasó el tiempo de su fase.
     */
    public static function apuntar(): ?array
    {
        if (self::vida() <= 0) {
            return null;
        }
        $activos = self::activos(self::jugadores());
        if (! $activos) {
            return null; // nadie jugando: el jefe espera
        }

        $fase = self::FASES[self::fase()];
        $ahora = microtime(true);
        if ($ahora - Cache::get('jefe.ultimo_contraataque', 0) < $fase['cada'] - 1) {
            return null; // todavía no le toca
        }
        Cache::forever('jefe.ultimo_contraataque', $ahora);

        // El primer carril es el de un jugador al azar (ataca donde hay gente);
        // en la fase 3 suma otro carril distinto.
        $carriles = [$activos[array_rand($activos)]['carril']];
        while (count($carriles) < $fase['carriles']) {
            $otro = random_int(0, self::CARRILES - 1);
            if (! in_array($otro, $carriles)) {
                $carriles[] = $otro;
            }
        }

        return $carriles;
    }

    /** Foto de en qué carril está cada jugador ahora mismo: [id => carril] */
    public static function carrilesActuales(): array
    {
        return array_map(fn ($j) => $j['carril'], self::jugadores());
    }

    /**
     * Paso 2: el impacto. Para ser justos con el lag, miramos DOS momentos:
     *
     *   $foto  = dónde estaba cada uno cuando terminó el aviso
     *   ahora  = dónde está después del margen de latencia
     *
     * Solo le pega al que estaba en el carril en LOS DOS momentos.
     *  - Te moviste justo antes de que termine el aviso, pero tu mensaje llegó
     *    tarde por el túnel → en "ahora" ya no estás → zafás.
     *  - Te metiste al carril cuando el rayo ya había caído en tu pantalla
     *    → en la foto no estabas → zafás.
     * En la duda, gana el jugador (así lo hacen muchos juegos online).
     * Al que le pega, pierde un corazón; si se queda sin corazones, cae.
     */
    public static function impactar(array $carriles, ?array $foto = null): array
    {
        return self::conLock(function () use ($carriles, $foto) {
            $ahora = time();
            $jugadores = self::jugadores();
            $golpeados = [];
            $bloqueados = [];

            foreach (self::activos($jugadores) as $id => $j) {
                if (! in_array($j['carril'], $carriles)) {
                    continue; // ¡esquivó! (ya no está en el carril)
                }
                if ($foto !== null && ! in_array($foto[$id] ?? -1, $carriles)) {
                    continue; // cuando terminó el aviso no estaba ahí (o ni existía)
                }
                if ($j['congelado_hasta'] > $ahora) {
                    continue; // ya está caído o aturdido: no le pegamos dos veces
                }
                if ($j['escudo_hasta'] >= $ahora) {
                    $bloqueados[] = self::publico($j); // el escudo lo salvó
                    continue;
                }

                $vida = $j['vida'] - 1;
                $caido = $vida <= 0;
                $jugadores[$id]['vida'] = $caido ? self::VIDAS_JUGADOR : $vida; // al caer, vuelve con todo
                $jugadores[$id]['congelado_hasta'] = $ahora + ($caido ? self::SEGUNDOS_CAIDO : self::SEGUNDOS_ATURDIDO);

                // Ojo: array_merge y no "+", porque "+" NO pisa claves que ya existen
                $golpeados[] = array_merge(self::publico($jugadores[$id]), [
                    'vida' => $caido ? 0 : $vida,
                    'caido' => $caido,
                    'segundos' => $caido ? self::SEGUNDOS_CAIDO : self::SEGUNDOS_ATURDIDO,
                ]);
            }

            self::guardarJugadores($jugadores);

            return [
                'golpeados' => $golpeados,
                'bloqueados' => $bloqueados,
                // dónde quedó cada uno: las pantallas se re-sincronizan con esto
                'posiciones' => array_map(fn ($j) => $j['carril'], $jugadores),
            ];
        });
    }

    /** Revive al jefe: la vida depende de cuántos jugadores hay */
    public static function reiniciar(): void
    {
        self::conLock(function () {
            $jugadores = array_map(fn ($j) => array_merge($j, [
                'danio' => 0, 'congelado_hasta' => 0, 'escudo_hasta' => 0, 'vida' => self::VIDAS_JUGADOR,
                'escudo_listo' => 0, 'especial_listo' => 0,
            ]), self::jugadores());

            $vidaMax = max(1, count($jugadores)) * self::VIDA_POR_JUGADOR;
            Cache::forever('jefe.vida_max', $vidaMax);
            Cache::forever('jefe.vida', $vidaMax);
            Cache::forever('jefe.especiales', []);
            Cache::forever('jefe.fase', 1);
            self::guardarJugadores($jugadores);
        });
    }

    // =====================================================================
    // Ayudantes privados
    // =====================================================================

    /** Chequeos comunes: ¿existe?, ¿el jefe vive?, ¿está congelado? */
    private static function puedeActuar(array $jugadores, string $id): ?array
    {
        if (! isset($jugadores[$id])) {
            return ['ok' => false, 'motivo' => 'jugador_inexistente'];
        }
        if (self::vida() <= 0) {
            return ['ok' => false, 'motivo' => 'jefe_muerto'];
        }
        if ($jugadores[$id]['congelado_hasta'] > time()) {
            return ['ok' => false, 'motivo' => 'congelado', 'segundos' => $jugadores[$id]['congelado_hasta'] - time()];
        }

        return null;
    }

    /** Resta vida al jefe, suma daño al jugador y guarda todo */
    private static function aplicarDanio(array $jugadores, string $id, int $danio): array
    {
        $vida = self::vida();
        $danio = min($danio, $vida);
        $vida -= $danio;

        $jugadores[$id]['danio'] += $danio;
        $jugadores[$id]['ultimo_ataque'] = time();

        Cache::forever('jefe.vida', $vida);
        self::guardarJugadores($jugadores);

        return [
            'ok' => true,
            'jugador' => $jugadores[$id],
            'danio' => $danio,
            'vida' => $vida,
            'derrotado' => $vida === 0,
        ];
    }

    private static function activos(array $jugadores): array
    {
        return array_filter($jugadores, fn ($j) => $j['ultimo_ataque'] >= time() - self::SEGUNDOS_ACTIVO);
    }

    private static function guardarJugadores(array $jugadores): void
    {
        Cache::forever('jefe.jugadores', $jugadores);
    }

    /**
     * Ejecuta $callback con un lock: si otra petición está modificando
     * el estado, esperamos (hasta 5 segundos) a que termine.
     */
    private static function conLock(callable $callback): mixed
    {
        return Cache::lock('jefe.lock', 10)->block(5, $callback);
    }
}
