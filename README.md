# Clientflow Pro

Multi-tenant CRM for clients/projects/invoices with Stripe test/live payments.

## Highlights
- Strict organization (tenant) isolation via global model scopes + request middleware.
- Roles/permissions per organization (Owner/Admin/Member).
- Invoicing workflow: draft → sent/overdue → paid/void, PDF generation (dompdf), taxes/discounts.
- Stripe Checkout + webhooks (`POST /stripe/webhook`) + public invoice pay pages (`/pay/{public_hash}`).
- Audit logs + CSV exports + API (Sanctum) + API docs.

## Requirements
- PHP 8.4 (extensions: `pdo_sqlite`, `sqlite3`, `mbstring`, `xml`, `curl`, `zip`, `intl`)
- Composer
- Node (LTS recommended; see `.nvmrc`)
- SQLite (default) or another supported DB

## Local setup (fresh clone)
```bash
git clone https://github.com/yusufdupsc1/clientflow-pro.git
cd clientflow-pro

cp .env.example .env
touch database/database.sqlite

composer install
npm ci
npm run build

php artisan key:generate
php artisan migrate

# optional demo data (creates org + invoices)
php artisan app:demo-seed
```

Run the app:
```bash
php artisan serve
php artisan queue:work
# optional scheduler
php artisan schedule:work
```

Open `http://localhost:8000`.

Important: use a single host consistently (`localhost` or `127.0.0.1`) to avoid session/cookie issues with tenant selection.

## Demo users (after `app:demo-seed`)
- `acme-studio.owner@example.com` / `password`
- `acme-studio.admin@example.com` / `password`
- `acme-studio.member@example.com` / `password`

## Stripe (localhost testing)

### 1) Get Stripe test keys
Stripe Dashboard (Test mode) → Developers → API keys:
- `STRIPE_TEST_PUBLISHABLE_KEY=pk_test_...`
- `STRIPE_TEST_SECRET=sk_test_...`

You can configure keys:
- Globally via `.env`, or
- Per organization in the UI: Settings → Billing & Stripe

Organization keys take precedence when set.

### 2) Start webhook forwarding (Stripe CLI)
```bash
stripe login
stripe listen --forward-to http://localhost:8000/stripe/webhook
```
Copy the signing secret printed by Stripe CLI:
- `STRIPE_TEST_WEBHOOK_SECRET=whsec_...`

### 3) Configure env vars
In `.env`:
```env
STRIPE_MODE=test
STRIPE_TEST_SECRET=sk_test_...
STRIPE_TEST_PUBLISHABLE_KEY=pk_test_...
STRIPE_TEST_WEBHOOK_SECRET=whsec_...
```
Then:
```bash
php artisan config:clear
```

### 4) Create an invoice and pay
1. Create an invoice (or use seeded ones).
2. Open the invoice → copy the **Public payment link**.
3. Visit `/pay/{public_hash}` and click **Pay with card**.
4. Use Stripe test card: `4242 4242 4242 4242` (any future expiry, any CVC).

### Currency safety
Stripe requires a valid ISO 4217 currency code (e.g. `USD`).
If you created invoices earlier with invalid currency values, normalize them:
```bash
php artisan app:repair-currencies
```
Dry run:
```bash
php artisan app:repair-currencies --dry-run
```

## Local email (optional: Mailpit)
```bash
docker compose up -d
```
- SMTP: `127.0.0.1:1025`
- UI: `http://localhost:8025`
- Smoke test: `php artisan mail:test you@example.com`

## API auth & docs
- Tokens: `POST /api/tokens` (auth required) → `{"token":"...","token_id":...}`
- Use: `Authorization: Bearer <token>`
- Docs: `GET /api/docs`

## Operations / production checklist
- Set `APP_ENV=production`, `APP_DEBUG=false`, and a real DB.
- Configure queues (`QUEUE_CONNECTION=database|redis`) and run workers.
- Configure scheduler (cron calling `php artisan schedule:run`) or keep `schedule:work` running.
- Ensure `storage/` and `bootstrap/cache/` are writable; run `php artisan storage:link`.
- Configure Stripe **live** keys and webhook secret (live mode is blocked outside production by default).

## Tests
```bash
php artisan test
```

## Troubleshooting
<<<<<<< HEAD
- `403 Tenant not resolved`: you are likely missing an organization selection (log in and pick an org), or you’re mixing `localhost` and `127.0.0.1`.
- SQLite driver missing: install `php-sqlite3` so `pdo_sqlite`/`sqlite3` appear in `php -m`.
- Vite manifest missing: run `npm ci && npm run build`.
=======
- Missing sqlite drivers: install `php-sqlite3` (Debian/Ubuntu) so `pdo_sqlite`/`sqlite3` appear in `php -m`.
- Manifest missing: `npm ci && npm run build`.
- APP_KEY missing: `php artisan key:generate` after `.env`.
- Node drift: `nvm alias default 'lts/*'` and `.nvmrc` = `lts/*`.

---

## Stripe Payment Integration

### Getting Stripe Keys (Step-by-Step)

1. **Create a Stripe Account**
   - Go to https://dashboard.stripe.com/register
   - Complete email verification and business profile

2. **Get API Keys (Test Mode)**
   - Navigate to https://dashboard.stripe.com/test/apikeys
   - Copy "Publishable key" (starts with `pk_test_`)
   - Click "Reveal test key" to get "Secret key" (starts with `sk_test_`)

3. **Set Up Webhook (for payment notifications)**
   - Go to https://dashboard.stripe.com/test/webhooks
   - Click "Add endpoint"
   - Endpoint URL: `https://your-domain.com/stripe/webhook`
   - Select events: `checkout.session.completed`, `payment_intent.succeeded`
   - Copy the "Signing secret" (starts with `whsec_`)

4. **Update .env**
   ```bash
   STRIPE_KEY=pk_test_your_publishable_key
   STRIPE_SECRET=sk_test_your_secret_key
   STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
   STRIPE_TEST_MODE=true
   ```

5. **Local Testing with Stripe CLI**
   ```bash
   # Install Stripe CLI: https://stripe.com/docs/stripe-cli#install
   stripe login
   stripe listen --forward-to localhost:8000/stripe/webhook
   # Copy the webhook signing secret it displays and use that locally
   ```

### Payment Flow
1. Create and send invoice from dashboard
2. Client receives email with "Pay Now" button → `/pay/{invoice_id}`
3. Client clicks "Pay with Card" → redirects to Stripe Checkout
4. After payment → webhook triggers → invoice marked "paid"
5. Receipt email sent automatically

### Going Live
```bash
# In .env for production:
STRIPE_KEY=pk_live_your_publishable_key
STRIPE_SECRET=sk_live_your_secret_key
STRIPE_WEBHOOK_SECRET=whsec_live_webhook_secret
STRIPE_TEST_MODE=false
```
> ⚠️ **Never use live keys when `APP_ENV` != `production`**

---

## Organization Settings

New settings available at `/settings`:
- **Profile**: Org name, billing email, phone, address (appears on invoices)
- **Billing**: Tax ID/VAT, default currency, default tax rate, payment terms
- **Branding**: Logo upload (appears on PDF invoices), invoice footer

---

## New Features (This Build)

| Feature | Route/Endpoint |
|---------|----------------|
| Payment Checkout | `GET /pay/{invoice}` (public) |
| Stripe Webhook | `POST /stripe/webhook` |
| Org Settings | `GET /settings/*` |
| PDF with branding | `GET /invoices/{id}/pdf` |
| Mark Overdue | `php artisan app:mark-overdue` |

---

## Production Deployment Checklist

```bash
# Environment
APP_ENV=production
APP_DEBUG=false
STRIPE_TEST_MODE=false  # Only with live keys!

# Required commands
php artisan key:generate  # If not set
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan view:cache

# Verify
curl https://your-domain.com/health  # Should return {"status":"ok"}

# Queue worker (for emails, webhooks)
php artisan queue:work --tries=3
```

### Safety Checks (run before deploy)
```bash
# Check for test keys in production .env
grep -E "sk_test_|pk_test_" .env && echo "⚠️ TEST KEYS DETECTED" || echo "✓ No test keys"

# Verify APP_DEBUG is false
grep "APP_DEBUG=true" .env && echo "⚠️ DEBUG IS ON" || echo "✓ Debug off"
```
>>>>>>> 6337e80 (feat: Implement comprehensive billing and payment functionality with Stripe integration, invoice management, refunds, and organization-specific settings.)
