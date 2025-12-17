# Clientflow Pro (Laravel 12, Multi-tenant SaaS)

## What this is
- Multi-tenant CRM/projects/invoicing/payments with strict org isolation.
- Breeze auth, Sanctum API, roles/permissions per org, audit logs, exports, webhooks.
- Minimal Blade UI (org switcher, dashboard metrics, CRUD) + API docs/SDK stubs.

## Prereqs
- PHP 8.4 with: pdo_sqlite, sqlite3, mbstring, xml, curl, zip, intl
- Composer
- Node via nvm (LTS; `.nvmrc` = `lts/*`)
- SQLite (default) or your DB
- Optional Mailpit (Docker) for local email

## Install & run (fresh clone)
```bash
git clone <repo> clientflow-pro
cd clientflow-pro
cp .env.example .env
touch database/database.sqlite           # if using SQLite

composer install
npm ci
npm run build

php artisan key:generate
php artisan config:clear && php artisan cache:clear
php artisan migrate
# optional sample data
php artisan app:demo-seed

# start services
npm run dev          # Vite
php artisan serve
```
Open http://127.0.0.1:8000, register, verify email, pick/switch org from the nav dropdown, and use Clients/Projects/Invoices/Audit/Tokens.

## API auth & docs
- Tokens: `POST /api/tokens` (auth required) → `{"token": "...", "token_id": ...}`
- Use header `Authorization: Bearer <token>`
- Docs: `GET /api/docs` (markdown). SDK stubs: `sdk/php/ClientflowApi.php`, `sdk/js/clientflowApi.js`
- Example: `curl -H "Authorization: Bearer $TOKEN" http://127.0.0.1:8000/api/clients`

## Roles & permissions (per org)
- Owner: full
- Admin: manage clients/projects/invoices/payments/invites/roles
- Member: read-only (view only)
Policies enforce org membership + role abilities.

## Features (web)
- Dashboard: counts (clients/projects/invoices), receivables/overdue, recent invoices.
- Org switcher (desktop/mobile), org selection guard.
- Clients/Projects/Invoices CRUD; send/void/overdue invoices; post/refund payments; PDF.
- Audit log (owner/admin), CSV exports (invoices/payments) with filters.
- Tokens UI; Invitations; Webhooks dispatch on invoice sent/voided, payment created.
- Stripe checkout/payment links with webhook logging (`/stripe/webhook`), public pay pages (`/pay/{public_hash}`), per-org Stripe keys (Settings → Billing).
- Invoice branding (logo/color), taxes/discounts/currency, rich PDF template (dompdf).

## Local email (Mailpit)
- `docker compose up -d` (SMTP 1025, UI 8025)
- .env (already set): MAIL_MAILER=smtp, MAIL_HOST=127.0.0.1, MAIL_PORT=1025, MAIL_ENCRYPTION=null, MAIL_USERNAME/PASSWORD=null, MAIL_FROM_ADDRESS=no-reply@local.test
- Clear config/cache after changes: `php artisan config:clear && php artisan cache:clear`
- View: http://127.0.0.1:8025
- Queue locally: set `QUEUE_CONNECTION=sync` or run `php artisan queue:work`
- Smoke: `php artisan mail:test you@example.com`

## API endpoints (high-level)
- Clients/Projects/Invoices (with items)/Payments: CRUD via `/api/...` (auth:sanctum + org, paginated, filters on index)
- Audit JSON: `GET /api/audit` (owner/admin) with `subject_type`, `action` filters
- Exports: `GET /invoices/export`, `GET /payments/export` (CSV, tenant-scoped, filters)
- Webhooks: events `invoices.sent`, `invoices.voided`, `payments.created`

## Stripe setup
- Env: set `STRIPE_MODE` (`test`/`live`), `STRIPE_TEST_SECRET/PUBLISHABLE_KEY/WEBHOOK_SECRET` (and live equivalents), `STRIPE_WEBHOOK_TOLERANCE` if you need a custom signature window.
- Webhook endpoint: `POST /stripe/webhook` (no auth, CSRF disabled). Provide the matching secret. Payload metadata carries `invoice_id` + `organization_id`; events are logged in `stripe_webhook_events`.
- Org-level keys: Settings → Billing lets each org store test/live keys + mode. Live mode is blocked on non-prod for safety.
- Public checkout: send `/pay/{public_hash}` to customers. Buttons create Stripe Checkout sessions; webhooks mark payments/refunds and log them.

## Health & ops
- Health: `GET /health` → `{"status":"ok"}`
- Read-only toggle: `APP_READ_ONLY=true` blocks writes (503)
- Route cache: **disabled** due to name collisions (web/api). Do not `route:cache` until API routes are namespaced.

## Deployment checklist
- Env: `APP_ENV=production`, `APP_DEBUG=false`, real DB, `APP_KEY` set.
- Queue: choose driver (db/redis); run `php artisan queue:work`.
- Cache warmup (after fixing route names): `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
- Storage: `php artisan storage:link`; ensure web user can write `storage/` and `bootstrap/cache/`.
- Schedule: `invoices:mark-overdue` (marks past-due as overdue) and `invoices:send-overdue-reminders` are scheduled daily via `bootstrap/app.php`.
- Mail: configure real SMTP (Mailpit is local-only).
- SSL: terminate TLS at proxy/web server.

## Tests
- `php artisan test` (103 passed locally)

## Troubleshooting
- Missing sqlite drivers: install `php-sqlite3` (Debian/Ubuntu) so `pdo_sqlite`/`sqlite3` appear in `php -m`.
- Manifest missing: `npm ci && npm run build`.
- APP_KEY missing: `php artisan key:generate` after `.env`.
- Node drift: `nvm alias default 'lts/*'` and `.nvmrc` = `lts/*`.
