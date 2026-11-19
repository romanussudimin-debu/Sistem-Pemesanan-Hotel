#!/usr/bin/env bash
# .devcontainer/setup.sh — dijalankan otomatis sekali saat Codespace dibuat.
# Bisa juga dijalankan manual di Linux/macOS untuk menyiapkan demo cepat berbasis SQLite.
set -euo pipefail

echo "▶ Memasang dependensi PHP & JavaScript…"
composer install --no-interaction --prefer-dist --no-progress
npm ci --no-audit --no-fund
npm run build

if [ ! -f .env ]; then
    echo "▶ Menyiapkan .env (SQLite, tanpa server database)…"
    cp .env.example .env
    php artisan key:generate --no-interaction
    sed -i -E 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/; s/^(DB_(HOST|PORT|DATABASE|USERNAME|PASSWORD|TIMEZONE)=)/# \1/' .env
    sed -i -E 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/; s/^MAIL_MAILER=.*/MAIL_MAILER=log/' .env
    if [ -n "${CODESPACE_NAME:-}" ]; then
        # URL publik Codespace (port 8000) dan percayai proxy GitHub agar tautan https benar
        sed -i "s|^APP_URL=.*|APP_URL=https://${CODESPACE_NAME}-8000.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-app.github.dev}|" .env
        sed -i 's/^TRUSTED_PROXIES=.*/TRUSTED_PROXIES=*/' .env
    fi
fi

echo "▶ Membuat database & data demo (±30 detik)…"
touch database/database.sqlite
php artisan migrate:fresh --seed --force --no-interaction
php artisan storage:link --no-interaction || true

echo ""
echo "✅ Siap! Aplikasi berjalan di port 8000 (tab PORTS)."
echo "   Admin     : admin@nusastay.test / password"
echo "   Pelanggan : budi@contoh.test / password"
