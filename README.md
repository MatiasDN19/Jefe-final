# KERNEL CORRUPTO — Jefe final colectivo

Juego multijugador en tiempo real hecho con **WebSockets** (Laravel Reverb + Echo) y **Three.js**.

Una pantalla grande muestra al jefe; cada jugador entra desde su celu o compu, elige campeón
y ataca. El jefe contraataca por carriles: hay que esquivar, usar escudo y coordinar especiales
entre 3 jugadores para el ataque combinado.

## Cómo funciona

- Las acciones de los jugadores viajan por HTTP (`fetch` + CSRF) al servidor.
- El servidor valida todo (estado en Cache con lock) y **difunde** el resultado por Reverb
  al canal público `jefe`.
- Todas las pantallas escuchan con Echo y se actualizan al instante.
- El jefe tiene iniciativa propia: el scheduler ejecuta `jefe:contraatacar`.
- Compensación de lag: el impacto se decide con un margen de gracia y "dos fotos" de los carriles.

## Instalar

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan reverb:install   # genera las claves de Reverb
php artisan migrate
```

## Jugar

En local (tres terminales):

```bash
php artisan serve
php artisan reverb:start
php artisan schedule:work
```

Compartido por internet con túneles de Cloudflare (necesita `cloudflared`):

```bash
bash iniciar-juego.sh
```

- Pantalla grande: `/pantalla`
- Jugadores: `/`

## Controles

`A` `D` moverse · `ESPACIO` atacar (zona verde = crítico) · `S` escudo · `W` especial · `M` sonido
