<?php

namespace App\Actions\Billing;

use App\Models\Invoice;
use App\Support\Activity\ActivityLogger;
use App\Support\Webhooks\WebhookDispatcher;

class VoidInvoice
{
    public function handle(Invoice $invoice): Invoice
    {
        if (!in_array($invoice->status, ['sent', 'overdue'], true)) {
            abort(response()->json(['message' => 'Only sent invoices can be voided.'], 422));
        }

        $before = $this->snapshot($invoice);

        $invoice->status = 'void';
        $invoice->paid_at = null;
        $invoice->save();

        $after = $this->snapshot($invoice);

        ActivityLogger::log($invoice, 'invoices.voided', $invoice->organization_id, [
            'before' => $before,
            'after' => $after,
        ]);

        WebhookDispatcher::dispatch('invoices.voided', $invoice->organization_id, [
            'invoice_id' => $invoice->id,
            'status' => $invoice->status,
        ]);

        return $invoice;
    }

    protected function snapshot(Invoice $invoice): array
    {
        return [
            'status' => $invoice->status,
            'sent_at' => optional($invoice->sent_at)->toIso8601String(),
            'paid_at' => optional($invoice->paid_at)->toIso8601String(),
            'amount_paid_cents' => $invoice->amount_paid_cents,
            'total_cents' => $invoice->total_cents,
        ];
    }
}
