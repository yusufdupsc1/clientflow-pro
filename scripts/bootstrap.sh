#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "${PROJECT_ROOT}"

require_cmd() {
  local cmd="$1"
  if ! command -v "${cmd}" >/dev/null 2>&1; then
    echo "Missing required command: ${cmd}"
    exit 1
  fi
}

require_cmd php
require_cmd composer
require_cmd node
require_cmd npm

echo "PHP: $(php -v | head -n 1)"
echo "Node: $(node -v)"
echo "npm: $(npm -v)"

if ! php -m | grep -qi pdo_sqlite; then
  echo "Extension pdo_sqlite is missing. On Debian/Ubuntu: sudo apt install -y php-sqlite3"
  exit 1
fi

if ! php -m | grep -qi sqlite3; then
  echo "Extension sqlite3 is missing. On Debian/Ubuntu: sudo apt install -y php-sqlite3"
  exit 1
fi

if [ ! -d vendor ]; then
  echo "Installing PHP dependencies..."
  composer install --no-interaction --prefer-dist
else
  echo "PHP dependencies already installed."
fi

if [ ! -d node_modules ]; then
  echo "Installing Node dependencies..."
  npm install
else
  echo "Node dependencies already installed."
fi

if [ ! -f .env ]; then
  echo "Creating .env from .env.example..."
  cp .env.example .env
fi

if ! grep -qE '^APP_KEY=.{10,}$' .env; then
  echo "Generating APP_KEY..."
  php artisan key:generate --force --ansi
fi

mkdir -p database
if [ ! -f database/database.sqlite ]; then
  echo "Creating SQLite database file..."
  : > database/database.sqlite
fi

if [ ! -f public/build/manifest.json ]; then
  echo "Building frontend assets..."
  npm run build
fi

if [ ! -f public/build/manifest.json ]; then
  echo "public/build/manifest.json is missing after build. Please check Vite build output."
  exit 1
fi

echo "Running database migrations..."
php artisan migrate:fresh --force

echo "Running test suite..."
php artisan test

cat <<'INFO'

Bootstrap complete.
Next commands:
  npm run dev
  php artisan serve
INFO
