<?php

namespace App\Actions\Billing;

use App\Models\Invoice;
use App\Support\Billing\Currency;
use Illuminate\Support\Facades\DB;
use App\Support\Activity\ActivityLogger;
use App\Support\Tenancy\Tenant;
use Illuminate\Support\Str;

class CreateInvoice
{
    public function handle(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $invoiceNumber = $this->nextInvoiceNumber();
            $organization = auth()->user()?->currentOrganization;
            $currency = Currency::normalize(
                $data['currency'] ?? $organization?->default_currency,
                strtoupper(config('stripe.default_currency', 'USD'))
            );
            $discount = max(0, (int) ($data['discount_cents'] ?? 0));
            $taxRate = (float) ($data['tax_rate_percent'] ?? 0);

            $invoice = Invoice::create([
                'client_id' => $data['client_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'title' => $data['title'],
                'notes' => $data['notes'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'invoice_number' => $invoiceNumber,
                'public_hash' => Str::uuid()->toString(),
                'subtotal_cents' => 0,
                'discount_cents' => $discount,
                'tax_cents' => 0,
                'tax_rate_percent' => $taxRate,
                'total_cents' => 0,
                'currency' => $currency,
            ]);

            $preparedItems = [];
            $subtotal = 0;

            foreach ($items as $item) {
                $lineTotal = (int) $item['quantity'] * (int) $item['unit_price_cents'];
                $subtotal += $lineTotal;

                $preparedItems[] = [
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price_cents' => $item['unit_price_cents'],
                    'line_total_cents' => $lineTotal,
                ];
            }

            if ($preparedItems) {
                $invoice->items()->createMany($preparedItems);
            }

            $taxable = max($subtotal - $discount, 0);
            $taxCents = (int) round($taxable * ($taxRate / 100));
            $total = max(0, $taxable + $taxCents);

            $invoice->forceFill([
                'subtotal_cents' => $subtotal,
                'discount_cents' => $discount,
                'tax_cents' => $taxCents,
                'total_cents' => $total,
            ])->save();

            ActivityLogger::log($invoice, 'invoices.created');

            return $invoice->fresh(['items', 'client', 'project']);
        });
    }

    protected function nextInvoiceNumber(): int
    {
        $tenantId = Tenant::id();

        $max = Invoice::query()
            ->when($tenantId, fn ($q) => $q->where('organization_id', $tenantId))
            ->max('invoice_number');

        return (int) $max + 1;
    }
}
