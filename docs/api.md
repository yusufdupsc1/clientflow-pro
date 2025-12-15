# ClientFlow API Docs (Minimal)

## Auth
- Issue a personal access token from Tokens UI or `POST /api/tokens` (auth required).
- Send `Authorization: Bearer {token}` with every request.
- All endpoints require tenant context via current organization membership; cross-org forbidden by policies.

## Base URLs
- Local: `http://127.0.0.1:8000/api`

## Clients
- `GET /clients` (paginated)
- `POST /clients` `{ name, email?, phone?, company?, notes? }`
- `GET /clients/{id}`
- `PATCH /clients/{id}`
- `DELETE /clients/{id}`

## Projects
- `GET /projects`
- `POST /projects` `{ name, client_id?, status?, due_date?, description? }`
- `GET /projects/{id}`
- `PATCH /projects/{id}`
- `DELETE /projects/{id}`

## Invoices
- `GET /invoices?status=&client_id=&project_id=`
- `POST /invoices` `{ title, client_id?, project_id?, items:[{description, quantity, unit_price_cents}] }`
- `GET /invoices/{id}`
- `PATCH /invoices/{id}` (update items recalculates totals)
- `DELETE /invoices/{id}`
- `POST /invoices/{id}/send` (web) – sends email, status to sent
- `POST /invoices/{id}/void` (web) – sent -> void
- `POST /invoices/{id}/payments` – create payment
- `GET /invoices/{id}/pdf` – PDF response

## Payments
- `POST /invoices/{id}/payments` `{ amount_cents, method?, reference? }`

## Audit
- `GET /audit` (web owner/admin) – filters `subject_type`, `action`
- `GET /api/audit` (API owner/admin) – paginated JSON, same filters

## Exports
- `GET /invoices/export?status=&client_id=&project_id=&from=&to=` – CSV
- `GET /payments/export?method=&from=&to=` – CSV

## Webhooks
- Configurable events: `invoices.sent`, `invoices.voided`, `payments.created`.

## Example curl
```bash
TOKEN=your_token_here
curl -H "Authorization: Bearer $TOKEN" \
  http://127.0.0.1:8000/api/clients
```

```bash
curl -X POST -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Demo","items":[{"description":"Line","quantity":1,"unit_price_cents":1000}]}' \
  http://127.0.0.1:8000/api/invoices
```
