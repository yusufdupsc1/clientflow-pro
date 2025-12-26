<?php

namespace App\Jobs;

use App\Actions\Billing\PostPayment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\StripeWebhookEvent;
use App\Support\Tenancy\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProcessStripeWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public StripeWebhookEvent $event)
    {
    }

    public function handle(PostPayment $postPayment): void
    {
        $payload = $this->event->payload ?? [];
        $type = $payload['type'] ?? $this->event->type;
        $object = $payload['data']['object'] ?? [];
        $metadata = $object['metadata'] ?? [];
        $invoiceId = $metadata['invoice_id'] ?? $this->event->invoice_id;
        $organizationId = $metadata['organization_id'] ?? $this->event->organization_id;

        if ($organizationId) {
            Tenant::set((int) $organizationId);
        }

        Log::info('stripe.webhook_processing', [
            'type' => $type,
            'event_id' => $this->event->id,
            'invoice_id' => $invoiceId,
        ]);

        try {
            if (in_array($type, ['checkout.session.completed', 'payment_intent.succeeded'], true)) {
                $this->handlePaymentSucceeded($postPayment, $invoiceId, $organizationId, $object);
            } elseif ($type === 'charge.refunded') {
                $this->handleRefund($object);
            }

            $this->event->status = 'processed';
            $this->event->processed_at = now();
            $this->event->save();
        } catch (\Throwable $e) {
            $this->event->status = 'failed';
            $this->event->save();

            Log::error('stripe.webhook.failed', [
                'event_id' => $this->event->event_id,
                'type' => $type,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            Tenant::clear();
        }
    }

    protected function handlePaymentSucceeded(PostPayment $postPayment, ?int $invoiceId, ?int $organizationId, array $object): void
    {
        if (!$invoiceId) {
            Log::warning('stripe.webhook_no_invoice_id', ['object' => $object]);
            return;
        }

        $invoice = Invoice::withoutGlobalScopes()->find($invoiceId);

        if (!$invoice) {
            Log::warning('stripe.webhook_invoice_not_found', ['invoice_id' => $invoiceId]);
            return;
        }

        // Set tenant context for the payment
        Tenant::set($invoice->organization_id);

        try {
            $amount = (int) ($object['amount_total'] ?? $object['amount_received'] ?? $object['amount'] ?? 0);
            $currency = strtoupper($object['currency'] ?? $invoice->currency ?? 'USD');
            $paymentIntentId = $object['payment_intent'] ?? $object['id'] ?? null;
            $chargeId = $object['latest_charge'] ?? $object['charge'] ?? null;

            if ($amount <= 0) {
                return;
            }

            // Check if already processed (idempotency)
            if ($invoice->stripe_payment_intent_id === $paymentIntentId && $paymentIntentId !== null) {
                Log::info('stripe.webhook_already_processed', [
                    'invoice_id' => $invoice->id,
                    'payment_intent' => $paymentIntentId,
                ]);
                return;
            }

            $payment = $postPayment->handle($invoice, [
                'amount_cents' => $amount,
                'provider' => 'stripe',
                'reference' => $paymentIntentId,
                'external_id' => $paymentIntentId,
                'method' => 'card',
            ]);

            $payment->forceFill([
                'currency' => $currency,
                'stripe_payment_intent_id' => $paymentIntentId,
                'stripe_charge_id' => $chargeId,
                'stripe_receipt_url' => $object['receipt_url'] ?? null,
                'stripe_webhook_event_id' => $this->event->event_id,
            ])->save();

            $this->event->payment_id = $payment->id;
            $this->event->invoice_id = $invoice->id;
            $this->event->organization_id = $organizationId ?? $invoice->organization_id;

            Log::info('stripe.webhook_payment_recorded', [
                'invoice_id' => $invoice->id,
                'amount_cents' => $amount,
                'payment_id' => $payment->id,
            ]);
        } catch (ValidationException $e) {
            $this->event->status = 'skipped';
            Log::warning('stripe.webhook.payment.skipped', [
                'event_id' => $this->event->event_id,
                'invoice_id' => $invoiceId,
                'reason' => $e->getMessage(),
            ]);
        }
    }

    protected function handleRefund(array $object): void
    {
        $paymentIntentId = $object['payment_intent'] ?? null;
        $amount = (int) ($object['amount'] ?? $object['amount_refunded'] ?? 0);

        $payment = Payment::query()
            ->when($paymentIntentId, fn($q) => $q->where('stripe_payment_intent_id', $paymentIntentId))
            ->when(!$paymentIntentId && isset($object['id']), fn($q) => $q->where('stripe_charge_id', $object['id']))
            ->first();

        if (!$payment) {
            Log::warning('stripe.webhook_refund_payment_not_found', ['payment_intent' => $paymentIntentId]);
            return;
        }

        $this->event->payment_id = $payment->id;
        $this->event->invoice_id = $payment->invoice_id;
        $this->event->organization_id = $this->event->organization_id ?? $payment->organization_id;

        if ($amount <= 0) {
            return;
        }

        $payment->stripe_refund_id = $object['id'] ?? $payment->stripe_refund_id;
        $payment->stripe_balance_transaction_id = $object['balance_transaction'] ?? $payment->stripe_balance_transaction_id;
        $payment->refunded_cents += $amount;
        $payment->refunded_at = $payment->refunded_at ?? now();
        $payment->save();

        $invoice = $payment->invoice;

        if ($invoice) {
            $invoice->amount_paid_cents = max(0, $invoice->amount_paid_cents - $amount);

            if ($invoice->amount_paid_cents < $invoice->total_cents) {
                $invoice->paid_at = null;
                $invoice->status = ($invoice->due_date && $invoice->due_date->isPast()) ? 'overdue' : 'sent';
            }

            $invoice->save();
        }

        Log::info('stripe.webhook_refund_recorded', [
            'payment_id' => $payment->id,
            'amount_cents' => $amount,
        ]);
    }
}
