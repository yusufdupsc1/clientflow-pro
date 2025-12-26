<?php

namespace App\Actions\Billing;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\StripeService;
use App\Support\Activity\ActivityLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\ApiErrorException;

class RefundPayment
{
    /**
     * Refund a payment (full or partial).
     */
    public function handle(Payment $payment, array $data = []): Payment
    {
        $invoice = $payment->invoice;
        $refundable = $payment->amount_cents - $payment->refunded_cents;
        $amount = (int) ($data['amount_cents'] ?? $refundable);
        $reason = $data['reason'] ?? null;

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount_cents' => ['Refund amount must be greater than zero.'],
            ]);
        }

        if ($amount > $refundable) {
            throw ValidationException::withMessages([
                'amount_cents' => ['Cannot refund more than the captured amount.'],
            ]);
        }

        $refundId = $payment->stripe_refund_id;
        $balanceTransaction = $payment->stripe_balance_transaction_id;

        if ($payment->stripe_payment_intent_id) {
            try {
                $stripe = app(StripeService::class);

                if ($invoice && $invoice->organization) {
                    $stripe->useOrganization($invoice->organization);
                }

                $refund = $stripe->client()->refunds->create(array_filter([
                    'payment_intent' => $payment->stripe_payment_intent_id,
                    'amount' => $amount,
                    'reason' => 'requested_by_customer',
                    'metadata' => ['internal_reason' => $reason],
                ]));

                $refundId = $refund->id ?? $refundId;
                $balanceTransaction = $refund->balance_transaction ?? $balanceTransaction;
            } catch (ApiErrorException $e) {
                Log::error('stripe.refund_failed', [
                    'payment_id' => $payment->id,
                    'message' => $e->getMessage(),
                ]);

                throw ValidationException::withMessages([
                    'stripe' => ['Stripe refund failed: ' . $e->getMessage()],
                ]);
            } catch (\Throwable $e) {
                Log::error('stripe.refund_failed', [
                    'payment_id' => $payment->id,
                    'message' => $e->getMessage(),
                ]);

                throw ValidationException::withMessages([
                    'stripe' => ['Stripe refund failed: ' . $e->getMessage()],
                ]);
            }
        } else {
            // Manual payment
            $refundId = 'manual-' . uniqid();
        }

        $payment->forceFill([
            'refunded_cents' => $payment->refunded_cents + $amount,
            'refunded_at' => now(),
            'stripe_refund_id' => $refundId,
            'stripe_balance_transaction_id' => $balanceTransaction,
        ])->save();

        if ($invoice) {
            $invoice->amount_paid_cents = max(0, $invoice->amount_paid_cents - $amount);

            if ($invoice->amount_paid_cents < $invoice->total_cents) {
                $invoice->paid_at = null;
                $invoice->status = ($invoice->due_date && $invoice->due_date->isPast()) ? 'overdue' : 'sent';
            }

            $invoice->save();
        }

        ActivityLogger::log($payment, 'payments.refunded', $payment->organization_id, [
            'payment_id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'amount_cents' => $amount,
            'refunded_cents' => $payment->refunded_cents,
            'stripe_refund_id' => $refundId,
            'reason' => $reason,
        ]);

        return $payment->fresh(['invoice']);
    }
}
