<?php

namespace App\Actions\Billing;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use App\Support\Activity\ActivityLogger;
use Illuminate\Validation\ValidationException;

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

            $this->assertStatusTransitionAllowed($invoice, $desiredStatus, $items);

            // Locked invoices: allow notes-only updates
            if (in_array($invoice->status, ['paid', 'void'], true)) {
                $invoice->fill([
                    'notes' => $data['notes'] ?? $invoice->notes,
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

            $invoice->forceFill([
                'subtotal_cents' => $subtotal,
                'total_cents' => $subtotal,
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
        $allowed = ['draft', 'sent', 'paid', 'void'];
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
