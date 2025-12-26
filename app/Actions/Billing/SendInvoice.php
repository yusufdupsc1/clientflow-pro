<?php

namespace App\Actions\Billing;

use App\Models\Invoice;
use App\Mail\InvoiceSentMail;
use App\Support\Activity\ActivityLogger;
use App\Support\Webhooks\WebhookDispatcher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInvoice
{
    public function handle(Invoice $invoice): Invoice
    {
        if (in_array($invoice->status, ['paid', 'void'], true)) {
            abort(response()->json(['message' => 'Invoice cannot be sent in its current state.'], 422));
        }

        $before = $this->snapshot($invoice);

        if ($invoice->status !== 'sent') {
            $invoice->status = 'sent';
            $invoice->sent_at = now();
            $invoice->save();
        }

        $after = $this->snapshot($invoice);

        Mail::to($invoice->client?->email ?? auth()->user()->email)
            ->queue(new InvoiceSentMail($invoice));

        ActivityLogger::log($invoice, 'invoices.sent', $invoice->organization_id, [
            'status' => $invoice->status,
            'sent_at' => $invoice->sent_at?->toIso8601String(),
            'before' => $before,
            'after' => $after,
        ]);

        WebhookDispatcher::dispatch('invoices.sent', $invoice->organization_id, [
            'invoice_id' => $invoice->id,
            'status' => $invoice->status,
        ]);

        Log::info('invoice.sent', [
            'invoice_id' => $invoice->id,
            'organization_id' => $invoice->organization_id,
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
