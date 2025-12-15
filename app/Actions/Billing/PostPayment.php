<?php

namespace App\Actions\Billing;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Support\Activity\ActivityLogger;
use Illuminate\Support\Facades\Log;

class PostPayment
{
    public function handle(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $amount = (int) ($data['amount_cents'] ?? 0);
            $method = $data['method'] ?? $data['provider'] ?? null;
            $reference = $data['reference'] ?? $data['external_id'] ?? null;
            $before = $this->snapshot($invoice);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount_cents' => ['Payment amount must be greater than zero.'],
                ]);
            }

            if ($invoice->status !== 'sent') {
                throw ValidationException::withMessages([
                    'invoice' => ['Invoice must be sent before payment.'],
                ]);
            }

            if ($reference !== null) {
                $existing = Payment::where('invoice_id', $invoice->id)
                    ->where('method', $method)
                    ->where('reference', $reference)
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            $remaining = $invoice->total_cents - $invoice->amount_paid_cents;

            if ($amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount_cents' => ['Payment exceeds remaining balance.'],
                ]);
            }

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'amount_cents' => $amount,
                'paid_at' => now(),
                'organization_id' => $invoice->organization_id,
                'method' => $method,
                'reference' => $reference,
                'provider' => $method,
                'external_id' => $reference,
            ]);

            $invoice->amount_paid_cents += $amount;

            if ($invoice->amount_paid_cents >= $invoice->total_cents) {
                $invoice->status = 'paid';
                $invoice->paid_at = now();
            }

            $invoice->save();
            $after = $this->snapshot($invoice);

            ActivityLogger::log($payment, 'payments.created', $invoice->organization_id, [
                'invoice_id' => $invoice->id,
                'amount_cents' => $payment->amount_cents,
                'provider' => $payment->provider,
                'external_id' => $payment->external_id,
                'before' => $before,
                'after' => $after,
            ]);

            Log::info('invoice.paid_progress', [
                'invoice_id' => $invoice->id,
                'organization_id' => $invoice->organization_id,
                'amount_paid_cents' => $invoice->amount_paid_cents,
                'status' => $invoice->status,
            ]);

            return $payment;
        });
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
