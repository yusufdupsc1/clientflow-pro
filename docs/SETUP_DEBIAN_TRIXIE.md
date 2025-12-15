# Local setup on Debian 13 (trixie)

Tested environment:
- OS: Debian 13 (trixie)
- PHP: 8.4.11 with extensions: pdo_sqlite, sqlite3
- Composer: 2.9.2
- Node: 22.x (see .nvmrc) / npm 11.x

Steps
1. Install prerequisites:
   ```bash
   sudo apt update
   sudo apt install -y php php-sqlite3 composer nodejs npm
   ```
2. From the project root, install and build:
   ```bash
   nvm install        # respects .nvmrc (Node 22)
   nvm use
   composer install
   npm install
   npm run build      # produces public/build/manifest.json
   ```
3. Prepare Laravel:
   ```bash
   cp .env.example .env   # if not already present
   php artisan key:generate
   php artisan migrate:fresh
   ```
4. One-shot bootstrap and tests:
   ```bash
   composer run bootstrap
   php artisan test
   ```
