#!/data/data/com.termux/files/usr/bin/bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

if ! command -v php >/dev/null 2>&1 || [ ! -f artisan ]; then
    printf '%s\n' "PHP or the Laravel project is missing. Run bash setup-android-termux.sh first."
    exit 1
fi

if [ ! -f .env ] || [ ! -f database/database.sqlite ]; then
    printf '%s\n' "The .env file or SQLite database is missing. Complete the migration/setup first."
    exit 1
fi

LAN_IP="$(ip -4 route get 1.1.1.1 2>/dev/null | sed -n 's/.* src \([^ ]*\).*/\1/p' | head -n 1 || true)"

php artisan config:clear
php artisan serve --host=0.0.0.0 --port=8001 &
SERVER_PID=$!
trap 'kill "$SERVER_PID" 2>/dev/null || true' EXIT INT TERM

printf '\n%s\n' "Etivacsilog POS server is starting on port 8001."
printf '%s\n' "On this tablet: http://127.0.0.1:8001"
if [ -n "$LAN_IP" ]; then
    printf 'On devices connected to the same Wi-Fi: http://%s:8001\n' "$LAN_IP"
else
    printf '%s\n' "Connect the tablet to Wi-Fi and find its IP in Android Wi-Fi settings."
    printf '%s\n' "Other devices should open http://TABLET-IP:8001"
fi
printf '%s\n' "Keep this Termux session open. Press Ctrl+C to stop the server."

wait "$SERVER_PID"
