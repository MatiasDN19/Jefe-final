#!/usr/bin/env bash
#
# Levanta TODO el juego con un solo comando:
#
#     bash iniciar-juego.sh
#
# Para cortar todo: Ctrl+C (cierra también los túneles).
#
# Qué hace, en orden:
#   1. Compila el JavaScript (npm run build)
#   2. Abre los dos túneles de Cloudflare (Laravel :8000 y Reverb :8080)
#   3. Lee la dirección del túnel de Reverb y la escribe en el .env
#   4. Levanta Laravel, Reverb y el contraataque del jefe juntos
#

cd "$(dirname "$0")" || exit 1

# Al salir (Ctrl+C), cerramos los túneles que quedaron en segundo plano
trap 'echo; echo "Cerrando túneles..."; kill $(jobs -p) 2>/dev/null' EXIT

echo "==> 1/4 Compilando el JavaScript..."
rm -f public/hot   # si quedó de "npm run dev", Laravel buscaría el JS en :5173
npm run build || exit 1

echo "==> 2/4 Abriendo túneles de Cloudflare..."
mkdir -p storage/logs
LOG_WEB=storage/logs/tunel-web.log
LOG_REVERB=storage/logs/tunel-reverb.log
rm -f "$LOG_WEB" "$LOG_REVERB"
cloudflared tunnel --url http://localhost:8000 --edge-ip-version 4 --protocol http2 > "$LOG_WEB" 2>&1 &
cloudflared tunnel --url http://localhost:8080 --edge-ip-version 4 --protocol http2 > "$LOG_REVERB" 2>&1 &

echo "==> 3/4 Esperando las direcciones de los túneles..."
URL_WEB=""
URL_REVERB=""
for i in $(seq 1 40); do
    URL_WEB=$(grep -o 'https://[a-z0-9-]*\.trycloudflare\.com' "$LOG_WEB" 2>/dev/null | head -1)
    URL_REVERB=$(grep -o 'https://[a-z0-9-]*\.trycloudflare\.com' "$LOG_REVERB" 2>/dev/null | head -1)
    [ -n "$URL_WEB" ] && [ -n "$URL_REVERB" ] && break
    sleep 1
done

if [ -z "$URL_WEB" ] || [ -z "$URL_REVERB" ]; then
    echo "No se pudieron abrir los túneles. Mirá $LOG_WEB y $LOG_REVERB"
    exit 1
fi

# Escribimos la dirección de Reverb (sin https://) en el .env
sed -i "s|^REVERB_PUBLIC_HOST=.*|REVERB_PUBLIC_HOST=${URL_REVERB#https://}|" .env

echo
echo "  ╔══════════════════════════════════════════════════════════════╗"
echo "     PANTALLA GRANDE:  $URL_WEB/pantalla"
echo "     JUGADORES:        $URL_WEB"
echo "     (reverb: $URL_REVERB)"
echo "  ╚══════════════════════════════════════════════════════════════╝"
echo "  Los túneles pueden tardar unos segundos en responder."
echo

# (el jefe arranca 3 segundos después, así Reverb ya está escuchando)
echo "==> 4/4 Levantando Laravel, Reverb y el jefe... (Ctrl+C para cortar todo)"
npx concurrently -k -n web,reverb,jefe -c cyan,magenta,red \
    "php artisan serve" \
    "php artisan reverb:start" \
    "node -e \"setTimeout(() => {}, 3000)\" && php artisan schedule:work"
