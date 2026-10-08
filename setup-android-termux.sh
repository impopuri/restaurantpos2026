#!/data/data/com.termux/files/usr/bin/bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

if ! command -v pkg >/dev/null 2>&1; then
    printf '%s\n' "Run this script inside Termux on Android."
    exit 1
fi

if [ ! -f artisan ] || [ ! -f composer.json ]; then
    printf '%s\n' "Run this script from the copied Etivacsilog project folder."
    exit 1
fi

printf '%s\n' "Installing Android packages..."
pkg update -y
pkg install -y php composer nodejs-lts git sqlite iproute2 unzip

if ! php -r 'exit(extension_loaded("pdo_sqlite") ? 0 : 1);'; then
    printf '%s\n' "The installed PHP does not have pdo_sqlite enabled."
    printf '%s\n' "Update Termux packages with 'pkg upgrade' and run this script again."
    exit 1
fi

if [ ! -f .env ]; then
    cp .env.example .env
fi

set_env() {
    local key="$1"
    local value="$2"
    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        printf '%s=%s\n' "$key" "$value" >> .env
    fi
}

set_env APP_ENV production
set_env APP_DEBUG false
set_env APP_URL http://localhost
set_env DB_CONNECTION sqlite
set_env "DB_DATABASE" "$APP_DIR/database/database.sqlite"
set_env SESSION_DRIVER file
set_env CACHE_STORE file
set_env QUEUE_CONNECTION sync

mkdir -p database storage/framework/cache storage/framework/sessions \
    storage/framework/views storage/logs bootstrap/cache public/uploads/receipts
touch database/database.sqlite
chmod -R u+rwX storage bootstrap/cache public/uploads

printf '%s\n' "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --no-interaction
fi

printf '%s\n' "Building browser assets..."
npm ci --no-audit --no-fund
npm run build

if [ ! -e public/storage ]; then
    php artisan storage:link
fi

printf '%s\n' "Applying database migrations..."
if [ -s database/database.sqlite ]; then
    BACKUP_PATH="database/database.sqlite.before-migration-$(date +%Y%m%d-%H%M%S)"
    cp database/database.sqlite "$BACKUP_PATH"
    printf 'Database backup created: %s\n' "$BACKUP_PATH"
fi
php artisan migrate --force
php artisan config:cache
php artisan view:cache

printf '\n%s\n' "Setup finished."
printf '%s\n' "Keep this project in Termux's private home folder, not shared Downloads storage."
printf '%s\n' "Before starting, confirm database/database.sqlite and .env are the ones you migrated."
printf '%s\n' "Start the POS with: bash start-android-termux.sh"
