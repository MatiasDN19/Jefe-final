<?php

namespace App\Http\Controllers;

use App\Events\EscudoActivado;
use App\Events\FaseCambiada;
use App\Events\JugadorActualizado;
use App\Events\JugadorSalio;
use App\Events\NuevaPartida;
use App\Events\EspecialLanzado;
use App\Events\JefeDerrotado;
use App\Events\JefeGolpeado;
use App\Events\JefeReiniciado;
use App\Events\JugadorMovido;
use App\Events\JugadorUnido;
use App\Services\Juego;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JuegoController extends Controller
{
    // =====================================================================
    // Menú
    // =====================================================================

    /** GET / — la portada con el menú */
    public function portada()
    {
        $jugador = Juego::jugador(session('jugador_id'));

        return view('juego.portada', [
            'jugador' => $jugador,
            'stats' => $jugador ? Juego::campeon($jugador) : null,
            'cantidad' => count(Juego::jugadores()),
            'fase' => Juego::fase(),
        ]);
    }

    /** GET /unirse — formulario: nombre, color y campeón */
    public function formUnirse()
    {
        // Si ya tiene perfil, no le creamos otro: lo mandamos al menú
        if (Juego::jugador(session('jugador_id'))) {
            return redirect()->route('portada');
        }

        return view('juego.unirse', ['campeones' => Juego::CAMPEONES, 'jugador' => null]);
    }

    /** GET /perfil — el mismo formulario, pero con tus datos cargados */
    public function formPerfil()
    {
        $jugador = Juego::jugador(session('jugador_id'));
        if (! $jugador) {
            return redirect()->route('unirse');
        }

        return view('juego.unirse', ['campeones' => Juego::CAMPEONES, 'jugador' => $jugador]);
    }

    /** POST /perfil */
    public function perfil(Request $request)
    {
        $data = $this->validarPerfil($request);
        $jugador = Juego::editar($this->miId(), $data['nombre'], $data['color'], $data['campeon']);

        if (! $jugador) {
            return redirect()->route('unirse');
        }

        broadcast(new JugadorActualizado(Juego::publico($jugador)));

        return redirect()->route('jugar');
    }

    /** POST /salir — abandonar la partida */
    public function salir()
    {
        $jugador = Juego::jugador(session('jugador_id'));

        if ($jugador && Juego::salir($jugador['id'])) {
            broadcast(new JugadorSalio($jugador['id'], $jugador['nombre'], Juego::vida(), Juego::vidaMax()));
        }
        session()->forget('jugador_id');

        return redirect()->route('portada');
    }

    /** POST /unirse — crea el perfil, lo guarda en la sesión y manda al control */
    public function unirse(Request $request)
    {
        $data = $this->validarPerfil($request);

        $jugador = Juego::unir($data['nombre'], $data['color'], $data['campeon']);

        // La sesión es lo que nos dice "quién es" este navegador en cada acción.
        // Así nadie puede hacerse pasar por otro jugador.
        session(['jugador_id' => $jugador['id']]);

        broadcast(new JugadorUnido(Juego::publico($jugador), Juego::vida(), Juego::vidaMax()));

        // Jugador creado: volvemos al menú (sin repetir la intro), que ahora
        // muestra "¡A LA BATALLA!" y el resto de las opciones
        return redirect()->route('portada', ['menu' => 1]);
    }

    /** GET /jugar — el control del jugador */
    public function jugar()
    {
        $jugador = Juego::jugador(session('jugador_id'));

        if (! $jugador) {
            return redirect()->route('unirse');
        }

        return view('juego.control', [
            'jugador' => $jugador,
            'stats' => Juego::campeon($jugador),
            'vida' => Juego::vida(),
            'vidaMax' => Juego::vidaMax(),
            'fase' => Juego::fase(),
            // Tus compañeros, para dibujarlos como "fantasmas" en tu pantalla
            'otros' => array_values(array_map(
                [Juego::class, 'publico'],
                array_filter(Juego::jugadores(), fn ($j) => $j['id'] !== $jugador['id'])
            )),
        ]);
    }

    // =====================================================================
    // Acciones (las llama el control con fetch(), sin recargar)
    // =====================================================================

    /** POST /atacar */
    public function atacar(Request $request)
    {
        // Llega una ráfaga: una lista de golpes, cada uno dice si fue crítico
        $data = $request->validate([
            'golpes' => ['required', 'array', 'min:1', 'max:20'],
            'golpes.*' => ['boolean'],
        ]);
        $golpes = array_map(fn ($g) => (bool) $g, $data['golpes']);
        $r = Juego::atacar($this->miId(), $golpes);

        if (! $r['ok']) {
            return response()->json($r, 409); // 409 = "ahora no se puede"
        }

        $j = $r['jugador'];
        broadcast(new JefeGolpeado(
            jugadorId: $j['id'],
            nombre: $j['nombre'],
            color: $j['color'],
            campeon: $j['campeon'],
            danio: $r['danio'],
            critico: $r['critico'],
            vida: $r['vida'],
            vidaMax: Juego::vidaMax(),
            ranking: Juego::ranking(5),
            golpes: count($r['danios']),
        ));

        $this->chequearVictoria($r, $j['nombre']);

        return response()->json([
            'ok' => true,
            'danios' => $r['danios'],     // un número por golpe que contó
            'criticos' => $r['criticos'],
            'miDanio' => $j['danio'],
        ]);
    }

    /** POST /mover — cambiar de carril */
    public function mover(Request $request)
    {
        $data = $request->validate(['carril' => ['required', 'integer', 'between:0,' . (Juego::CARRILES - 1)]]);
        $j = Juego::mover($this->miId(), $data['carril']);

        if (! $j) {
            return response()->json(['ok' => false, 'motivo' => 'jugador_inexistente'], 409);
        }

        broadcast(new JugadorMovido($j['id'], $j['carril']));

        return response()->json(['ok' => true, 'carril' => $j['carril']]);
    }

    /** POST /escudo */
    public function escudo()
    {
        $r = Juego::escudo($this->miId());

        if (! $r['ok']) {
            return response()->json($r, 409);
        }

        broadcast(new EscudoActivado($r['jugador']['id'], $r['segundos']));

        return response()->json(['ok' => true, 'segundos' => $r['segundos'], 'recarga' => $r['recarga']]);
    }

    /** POST /especial */
    public function especial()
    {
        $r = Juego::especial($this->miId());

        if (! $r['ok']) {
            return response()->json($r, 409);
        }

        $j = $r['jugador'];
        broadcast(new EspecialLanzado(
            jugadorId: $j['id'],
            nombre: $j['nombre'],
            color: $j['color'],
            listos: $r['listos'],
            necesarios: Juego::COMBO_JUGADORES,
            combo: $r['combo'],
            vida: $r['vida'],
            vidaMax: Juego::vidaMax(),
            ranking: Juego::ranking(5),
        ));

        $this->chequearVictoria($r, $j['nombre']);

        return response()->json(['ok' => true, 'recarga' => $r['recarga'], 'combo' => $r['combo'] !== null]);
    }

    // =====================================================================
    // Pantalla grande
    // =====================================================================

    /** GET /pantalla — lo que se proyecta en el aula */
    public function pantalla()
    {
        return view('juego.pantalla', [
            'vida' => Juego::vida(),
            'vidaMax' => Juego::vidaMax(),
            'ranking' => Juego::ranking(5),
            'jugadores' => array_values(array_map([Juego::class, 'publico'], Juego::jugadores())),
            'fase' => Juego::fase(),
        ]);
    }

    /** POST /nueva-partida — borra a todos los jugadores y revive al jefe */
    public function nuevaPartida()
    {
        Juego::nuevaPartida();

        broadcast(new NuevaPartida(Juego::vida(), Juego::vidaMax()));

        return response()->json(['ok' => true]);
    }

    /** POST /reiniciar — revive al jefe para otra ronda */
    public function reiniciar()
    {
        Juego::reiniciar();

        broadcast(new JefeReiniciado(Juego::vida(), Juego::vidaMax()));

        return response()->json(['ok' => true]);
    }

    // =====================================================================

    private function miId(): string
    {
        return (string) session('jugador_id');
    }

    private function chequearVictoria(array $resultado, string $nombre): void
    {
        if ($resultado['derrotado']) {
            broadcast(new JefeDerrotado($nombre, Juego::ranking()));

            return;
        }

        // ¿El golpe lo hizo pasar de fase? Avisamos a todos (una sola vez)
        if ($fase = Juego::chequearFase()) {
            broadcast(new FaseCambiada($fase, Juego::FASES[$fase]['nombre']));
        }
    }

    private function validarPerfil(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:16'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'campeon' => ['required', Rule::in(array_keys(Juego::CAMPEONES))],
        ]);
    }
}
