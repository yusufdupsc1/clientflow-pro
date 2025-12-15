<?php

namespace App\Actions\Billing;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use App\Support\Activity\ActivityLogger;
use App\Support\Tenancy\Tenant;

class CreateInvoice
{
    public function handle(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $invoiceNumber = $this->nextInvoiceNumber();

            $invoice = Invoice::create([
                'client_id' => $data['client_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'title' => $data['title'],
                'notes' => $data['notes'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'invoice_number' => $invoiceNumber,
                'subtotal_cents' => 0,
                'total_cents' => 0,
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

            $invoice->forceFill([
                'subtotal_cents' => $subtotal,
                'total_cents' => $subtotal,
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
