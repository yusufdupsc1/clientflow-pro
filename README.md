<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Quick start

1. Install prerequisites (PHP with pdo_sqlite/sqlite3, Composer, Node/npm).
2. From the project root: `composer run bootstrap`
3. Start dev servers: `npm run dev` and `php artisan serve`
4. Health check: `curl http://localhost:8000/health`
5. Seed demo data (optional): `php artisan app:demo-seed`

## API authentication (tokens)

Sanctum personal access tokens are tied to the authenticated user and their current organization; policies still enforce org membership/roles.

Issue a token (session auth):
```bash
curl -X POST http://localhost:8000/api/tokens -b "your_session_cookie" | jq .
# => { "token": "<plaintext>", "token_id": 1 }
```

Use the token (invite example):
```bash
TOKEN="<plaintext>"
ORG=1
curl -X POST http://localhost:8000/api/organizations/$ORG/invites \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"email":"apiuser@example.com","role":"member"}'
```

List your tokens:
```bash
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/tokens
```

Revoke a token:
```bash
TOKEN_ID=1
curl -X DELETE -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/tokens/$TOKEN_ID
```

## API docs & SDK stubs

- API reference (markdown): `GET /api/docs` (text/markdown). See `docs/api.md`.
- Minimal SDK stubs:
  - PHP: `sdk/php/ClientflowApi.php`
  - JS: `sdk/js/clientflowApi.js`
- Example:
```bash
TOKEN="<plaintext>"
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/clients
```

## Roles & permissions (per organization)

- **Owner:** full access to all resources in the org.
- **Admin:** manage clients, projects, invoices, payments, invites, member roles.
- **Member:** read-only (view clients/projects/invoices/payments); cannot create/update/delete.

Policies enforce both org membership and the permission map above for every resource.

## Audit & exports

- Audit log (owner/admin): `GET /audit` — scoped to current org; filter by `subject_type` via query string.
- CSV exports (tenant-scoped, filterable):
  - `GET /invoices/export` → invoices.csv (filters: status, client_id, project_id, date_from, date_to)
  - `GET /payments/export` → payments.csv (filters: method, date_from, date_to)
  Returns `text/csv`; export jobs are queueable (dispatchSync in tests). Use `queue:work` for async in production.

## CI

GitHub Actions workflow runs on pushes/PRs (PHP 8.3 + Node 22):
- composer install
- npm ci && npm run build
- php artisan migrate:fresh
- php artisan test

## Troubleshooting

- Missing sqlite drivers: install `php-sqlite3` (Debian/Ubuntu) so `pdo_sqlite` and `sqlite3` show in `php -m`.
- Missing `public/build/manifest.json`: run `npm install` then `npm run build`.
- If APP_KEY is missing, run `php artisan key:generate` after ensuring `.env` exists.
- Node version drift: use `nvm alias default 'lts/*'` and an `.nvmrc` of `lts/*` for consistency.
- Vite in tests: feature tests are configured to avoid requiring a build; ensure `npm run build` for local UI.

## Local email (Mailpit)

- Run Mailpit: `docker compose up -d` (SMTP: 1025, UI: 8025).
- Configure `.env` (already in `.env.example`):
  - MAIL_MAILER=smtp
  - MAIL_HOST=127.0.0.1
  - MAIL_PORT=1025
  - MAIL_ENCRYPTION=null
  - MAIL_USERNAME=null
  - MAIL_PASSWORD=null
  - MAIL_FROM_ADDRESS=no-reply@local.test
- Clear config/cache after changes:
  - `php artisan config:clear`
  - `php artisan cache:clear`
- View emails: http://127.0.0.1:8025
- Queues: for local dev you may set `QUEUE_CONNECTION=sync`; if using database queue, run `php artisan queue:work`.
- Smoke test: `php artisan mail:test you@example.com`
