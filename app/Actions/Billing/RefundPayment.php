<?php

namespace App\Actions\Billing;

<<<<<<< HEAD
use App\Models\Payment;
use App\Services\StripeService;
use App\Support\Activity\ActivityLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\ApiErrorException;

class RefundPayment
{
    public function handle(Payment $payment, array $data = []): Payment
    {
        $invoice = $payment->invoice;
        $refundable = $payment->amount_cents - $payment->refunded_cents;
        $amount = (int) ($data['amount_cents'] ?? $refundable);

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
                    'reason' => $data['reason'] ?? null,
                ]));

                $refundId = $refund->id ?? $refundId;
                $balanceTransaction = $refund->balance_transaction ?? $balanceTransaction;
            } catch (ApiErrorException $e) {
                Log::error('stripe.refund_failed', [
                    'payment_id' => $payment->id,
                    'message' => $e->getMessage(),
                ]);

                throw ValidationException::withMessages([
                    'stripe' => ['Stripe refund failed: '.$e->getMessage()],
                ]);
            } catch (\Throwable $e) {
                Log::error('stripe.refund_failed', [
                    'payment_id' => $payment->id,
                    'message' => $e->getMessage(),
                ]);

                throw ValidationException::withMessages([
                    'stripe' => ['Stripe refund failed: '.$e->getMessage()],
                ]);
            }
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
=======
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\StripeService;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\Log;

class RefundPayment
{
    public function __construct(
        protected StripeService $stripeService
    ) {
    }

    /**
     * Refund a payment (full or partial).
     *
     * @param Payment $payment
     * @param int|null $amountCents Amount to refund in cents, null for full refund
     * @param string|null $reason Reason for refund
     * @return array{success: bool, refund_id?: string, error?: string}
     */
    public function execute(Payment $payment, ?int $amountCents = null, ?string $reason = null): array
    {
        // Default to full refund if no amount specified
        $refundAmount = $amountCents ?? $payment->amount_cents;

        // Validate refund amount
        if ($refundAmount <= 0) {
            return ['success' => false, 'error' => 'Refund amount must be positive'];
        }

        if ($refundAmount > $payment->amount_cents) {
            return ['success' => false, 'error' => 'Refund amount exceeds payment amount'];
        }

        // Check if already fully refunded
        $existingRefunds = $payment->refunded_cents ?? 0;
        if ($existingRefunds >= $payment->amount_cents) {
            return ['success' => false, 'error' => 'Payment already fully refunded'];
        }

        // Check if stripe payment intent exists
        if (empty($payment->stripe_payment_intent_id)) {
            // Manual payment - just record the refund locally
            return $this->recordLocalRefund($payment, $refundAmount, $reason);
        }

        // Process Stripe refund
        try {
            $stripe = new \Stripe\StripeClient(config('stripe.secret'));

            $refundParams = [
                'payment_intent' => $payment->stripe_payment_intent_id,
                'amount' => $refundAmount,
            ];

            if ($reason) {
                $refundParams['reason'] = 'requested_by_customer';
                $refundParams['metadata'] = ['internal_reason' => $reason];
            }

            $refund = $stripe->refunds->create($refundParams);

            // Update payment record
            $payment->refunded_cents = ($payment->refunded_cents ?? 0) + $refundAmount;
            $payment->save();

            // Update invoice paid amount
            $invoice = $payment->invoice;
            if ($invoice) {
                $invoice->amount_paid_cents -= $refundAmount;

                // If full refund and invoice was paid, revert to sent
                if ($invoice->amount_paid_cents <= 0) {
                    $invoice->amount_paid_cents = 0;
                    $invoice->status = 'sent';
                    $invoice->paid_at = null;
                }

                $invoice->save();
            }

            // Log activity
            ActivityLogger::log($payment, 'payments.refunded', $payment->organization_id, [
                'payment_id' => $payment->id,
                'refund_amount_cents' => $refundAmount,
                'stripe_refund_id' => $refund->id,
                'reason' => $reason,
            ]);

            Log::info('payment.refunded', [
                'payment_id' => $payment->id,
                'refund_id' => $refund->id,
                'amount_cents' => $refundAmount,
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('stripe.refund_failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Stripe refund failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Record a local refund for manual payments.
     */
    protected function recordLocalRefund(Payment $payment, int $amountCents, ?string $reason): array
    {
        $payment->refunded_cents = ($payment->refunded_cents ?? 0) + $amountCents;
        $payment->save();

        // Update invoice
        $invoice = $payment->invoice;
        if ($invoice) {
            $invoice->amount_paid_cents -= $amountCents;

            if ($invoice->amount_paid_cents <= 0) {
                $invoice->amount_paid_cents = 0;
                $invoice->status = 'sent';
                $invoice->paid_at = null;
>>>>>>> 6337e80 (feat: Implement comprehensive billing and payment functionality with Stripe integration, invoice management, refunds, and organization-specific settings.)
            }

            $invoice->save();
        }

        ActivityLogger::log($payment, 'payments.refunded', $payment->organization_id, [
            'payment_id' => $payment->id,
<<<<<<< HEAD
            'invoice_id' => $payment->invoice_id,
            'amount_cents' => $amount,
            'refunded_cents' => $payment->refunded_cents,
        ]);

        return $payment->fresh(['invoice']);
=======
            'refund_amount_cents' => $amountCents,
            'reason' => $reason,
            'type' => 'manual',
        ]);

        return [
            'success' => true,
            'refund_id' => 'manual-' . uniqid(),
        ];
>>>>>>> 6337e80 (feat: Implement comprehensive billing and payment functionality with Stripe integration, invoice management, refunds, and organization-specific settings.)
    }
}
