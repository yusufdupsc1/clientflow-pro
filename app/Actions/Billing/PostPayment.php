<?php

namespace App\Actions\Billing;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostPayment
{
    public function handle(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $amount = (int) ($data['amount_cents'] ?? 0);
            $provider = $data['provider'] ?? null;
            $externalId = $data['external_id'] ?? null;

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount_cents' => ['Payment amount must be greater than zero.'],
                ]);
            }

            if ($invoice->status === 'void') {
                throw ValidationException::withMessages([
                    'invoice' => ['Cannot pay a void invoice.'],
                ]);
            }

            if ($externalId !== null) {
                $existing = Payment::where('invoice_id', $invoice->id)
                    ->where('provider', $provider)
                    ->where('external_id', $externalId)
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
                'provider' => $provider,
                'external_id' => $externalId,
            ]);

            $invoice->amount_paid_cents += $amount;

            if ($invoice->amount_paid_cents >= $invoice->total_cents) {
                $invoice->status = 'paid';
            } elseif ($invoice->status === 'draft') {
                $invoice->status = 'issued';
            }

            $invoice->save();

            return $payment;
        });
    }
}
