<?php

namespace App\Actions\Billing;

use App\Models\Invoice;
use App\Support\Billing\Currency;
use Illuminate\Support\Facades\DB;
use App\Support\Activity\ActivityLogger;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class UpdateInvoice
{
    public function handle(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $currentStatus = $invoice->status;
            $desiredStatus = $data['status'] ?? $invoice->status;
            unset($data['status']);

            $orgCurrency = auth()->user()?->currentOrganization?->default_currency;
            $currency = Currency::normalize(
                $data['currency'] ?? $invoice->currency ?? $orgCurrency,
                strtoupper(config('stripe.default_currency', 'USD'))
            );
            $discount = max(0, (int) ($data['discount_cents'] ?? $invoice->discount_cents ?? 0));
            $taxRate = (float) ($data['tax_rate_percent'] ?? $invoice->tax_rate_percent ?? 0);

            $this->assertStatusTransitionAllowed($invoice, $desiredStatus, $items);

            if (! $invoice->public_hash) {
                $invoice->public_hash = Str::uuid()->toString();
            }

            // Locked invoices: allow notes-only updates
            if (in_array($invoice->status, ['paid', 'void'], true)) {
                $invoice->fill([
                    'notes' => $data['notes'] ?? $invoice->notes,
                    'public_hash' => $invoice->public_hash,
                ])->save();

                return $invoice->refresh(['items', 'client', 'project']);
            }

            $invoice->fill([
                'client_id' => $data['client_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'title' => $data['title'],
                'notes' => $data['notes'] ?? null,
                'due_date' => $data['due_date'] ?? $invoice->due_date,
                'status' => $desiredStatus,
                'currency' => $currency,
            ])->save();

            $invoice->items()->delete();

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
                'tax_rate_percent' => $taxRate,
                'total_cents' => $total,
            ])->save();

            if ($currentStatus === 'draft' && $desiredStatus === 'sent') {
                $invoice->status = 'sent';
                $invoice->sent_at = now();
                $invoice->save();
            }

            ActivityLogger::log($invoice, 'invoices.updated');

            return $invoice->fresh(['items', 'client', 'project']);
        });
    }

    protected function assertStatusTransitionAllowed(Invoice $invoice, string $desiredStatus, array $items): void
    {
        $allowed = ['draft', 'sent', 'overdue', 'paid', 'void'];
        if (! in_array($desiredStatus, $allowed, true)) {
            throw ValidationException::withMessages(['status' => ['Invalid status.']]);
        }

        if (in_array($invoice->status, ['paid', 'void'], true)) {
            if ($desiredStatus !== $invoice->status) {
                throw ValidationException::withMessages([
                    'status' => ['Paid or void invoices cannot change status.'],
                ]);
            }

            if ($items !== []) {
                throw ValidationException::withMessages([
                    'items' => ['Paid or void invoices cannot change items.'],
                ]);
            }
        }

        if ($invoice->status === 'draft' && $desiredStatus === 'void') {
            throw ValidationException::withMessages([
                'status' => ['Draft invoices cannot be voided.'],
            ]);
        }

        if ($desiredStatus === 'paid') {
            throw ValidationException::withMessages([
                'status' => ['Invoices become paid via payments.'],
            ]);
        }
    }
}
