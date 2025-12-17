<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Organization;
use App\Support\Billing\Currency;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeService
{
    protected ?StripeClient $client = null;

    protected string $mode;

    protected ?Organization $organization = null;

    public function __construct(?Organization $organization = null)
    {
        $this->organization = $organization;
        $this->mode = $this->determineMode($organization);
        $this->guardAgainstLiveKeyMisuse();
        $this->bootClient();
    }

    public function mode(): string
    {
        return $this->mode;
    }

    public function useOrganization(?Organization $organization): void
    {
        $this->organization = $organization;
        $this->mode = $this->determineMode($organization);
        $this->guardAgainstLiveKeyMisuse();
        $this->bootClient();
    }

    protected function bootClient(): void
    {
        $secret = $this->secretKey();

        $this->client = $secret ? new StripeClient($secret) : null;
    }

    public function client(): StripeClient
    {
        if (! $this->client) {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        return $this->client;
    }

    public function publishableKey(): ?string
    {
        return $this->publishableKeyForMode($this->mode, $this->organization);
    }

    public function secretKey(): ?string
    {
        return $this->secretKeyForMode($this->mode, $this->organization);
    }

    public function webhookSecret(): ?string
    {
        return $this->webhookSecretForMode($this->mode, $this->organization);
    }

    public function secretKeyForMode(string $mode, ?Organization $organization = null): ?string
    {
        $mode = $this->normalizeMode($mode);

        if ($organization) {
            if ($mode === 'live' && $organization->stripe_live_secret) {
                return $organization->stripe_live_secret;
            }

            if ($mode === 'test' && $organization->stripe_test_secret) {
                return $organization->stripe_test_secret;
            }
        }

        return config('stripe.secret_keys')[$mode] ?? null;
    }

    public function publishableKeyForMode(string $mode, ?Organization $organization = null): ?string
    {
        $mode = $this->normalizeMode($mode);

        if ($organization) {
            if ($mode === 'live' && $organization->stripe_live_publishable_key) {
                return $organization->stripe_live_publishable_key;
            }

            if ($mode === 'test' && $organization->stripe_test_publishable_key) {
                return $organization->stripe_test_publishable_key;
            }
        }

        return config('stripe.publishable_keys')[$mode] ?? null;
    }

    public function webhookSecretForMode(string $mode, ?Organization $organization = null): ?string
    {
        $mode = $this->normalizeMode($mode);

        if ($organization) {
            if ($mode === 'live' && $organization->stripe_live_webhook_secret) {
                return $organization->stripe_live_webhook_secret;
            }

            if ($mode === 'test' && $organization->stripe_test_webhook_secret) {
                return $organization->stripe_test_webhook_secret;
            }
        }

        return config('stripe.webhook.secrets')[$mode] ?? null;
    }

    public function tolerance(): int
    {
        return (int) config('stripe.webhook.tolerance', 300);
    }

    public function ensureInvoiceStripeArtifacts(Invoice $invoice): Invoice
    {
        $this->useOrganization($this->resolveInvoiceOrganization($invoice));
        $invoice = $this->ensureInvoiceDefaults($invoice);

        $chargeAmountCents = $this->chargeAmountCents($invoice);

        if (! $invoice->stripe_product_id) {
            $product = $this->client()->products->create([
                'name' => $invoice->title,
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'organization_id' => $invoice->organization_id,
                ],
            ]);

            $invoice->stripe_product_id = $product->id;
        }

        if ($chargeAmountCents > 0 && $this->priceNeedsRefresh($invoice, $chargeAmountCents)) {
            $price = $this->client()->prices->create([
                'currency' => Currency::normalizeForStripe(
                    $invoice->currency,
                    config('stripe.default_currency', 'usd')
                ),
                'unit_amount' => (int) $chargeAmountCents,
                'product' => $invoice->stripe_product_id,
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'organization_id' => $invoice->organization_id,
                ],
            ]);

            $invoice->stripe_price_id = $price->id;
            $invoice->stripe_price_amount_cents = $chargeAmountCents;
            $invoice->stripe_price_currency = $invoice->currency;

            // Price is tied to a specific amount; invalidate any cached checkout/payment link URLs.
            $invoice->stripe_payment_link_id = null;
            $invoice->stripe_payment_link_url = null;
            $invoice->stripe_checkout_session_id = null;
        }

        $invoice->stripe_mode = $this->mode;
        $invoice->save();

        return $invoice->fresh();
    }

    public function createCheckoutSession(Invoice $invoice, string $successUrl, string $cancelUrl): string
    {
        if ($this->chargeAmountCents($invoice) <= 0) {
            throw new RuntimeException('Invoice balance is already paid.');
        }

        $invoice = $this->ensureInvoiceStripeArtifacts($invoice);

        $session = $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price' => $invoice->stripe_price_id,
                    'quantity' => 1,
                ],
            ],
            'metadata' => [
                'invoice_id' => $invoice->id,
                'organization_id' => $invoice->organization_id,
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        $invoice->stripe_checkout_session_id = $session->id;
        $invoice->stripe_payment_link_url = $session->url;
        $invoice->save();

        return $session->url;
    }

    public function createPaymentLink(Invoice $invoice): ?string
    {
        if ($this->chargeAmountCents($invoice) <= 0) {
            return null;
        }

        $invoice = $this->ensureInvoiceStripeArtifacts($invoice);

        if ($invoice->stripe_payment_link_url) {
            return $invoice->stripe_payment_link_url;
        }

        try {
            $link = $this->client()->paymentLinks->create([
                'line_items' => [
                    [
                        'price' => $invoice->stripe_price_id,
                        'quantity' => 1,
                    ],
                ],
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'organization_id' => $invoice->organization_id,
                ],
                'after_completion' => [
                    'type' => 'redirect',
                    'redirect' => ['url' => route('pay.invoices.show', $invoice->public_hash).'?success=1'],
                ],
            ]);

            $invoice->stripe_payment_link_id = $link->id;
            $invoice->stripe_payment_link_url = $link->url;
            $invoice->save();

            return $link->url;
        } catch (ApiErrorException $e) {
            Log::error('stripe.payment_link_failed', [
                'invoice_id' => $invoice->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function verifyWebhook(string $payload, string $signatureHeader, ?string $secret = null): array
    {
        $secret ??= $this->webhookSecret();

        if (! $secret) {
            throw new RuntimeException('Stripe webhook secret is not configured.');
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signatureHeader,
                $secret,
                $this->tolerance()
            );
        } catch (SignatureVerificationException $e) {
            Log::warning('stripe.webhook.signature_failed', ['message' => $e->getMessage()]);
            throw $e;
        }

        return $event->toArray();
    }

    protected function determineMode(?Organization $organization = null): string
    {
        $mode = $organization?->stripe_mode ?? config('stripe.mode', 'test');

        return $this->normalizeMode($mode);
    }

    protected function normalizeMode(string $mode): string
    {
        return in_array($mode, ['live', 'test'], true) ? $mode : 'test';
    }

    protected function guardAgainstLiveKeyMisuse(): void
    {
        if ($this->mode === 'live' && config('stripe.guard_live_on_non_prod', true) && ! app()->environment('production')) {
            throw new RuntimeException('Live Stripe mode is blocked outside production.');
        }
    }

    protected function ensureInvoiceDefaults(Invoice $invoice): Invoice
    {
        if (! $invoice->public_hash) {
            $invoice->public_hash = (string) Str::uuid();
        }

        $defaultCurrency = strtoupper($this->organization?->default_currency ?? config('stripe.default_currency', 'usd'));
        $invoice->currency = Currency::normalize($invoice->currency, $defaultCurrency);

        $invoice->stripe_mode = $this->mode;
        $invoice->save();

        return $invoice;
    }

    protected function resolveInvoiceOrganization(Invoice $invoice): ?Organization
    {
        if ($invoice->relationLoaded('organization') && $invoice->organization) {
            return $invoice->organization;
        }

        if ($invoice->organization) {
            return $invoice->organization;
        }

        if ($invoice->organization_id) {
            return Organization::find($invoice->organization_id);
        }

        return $this->organization;
    }

    protected function chargeAmountCents(Invoice $invoice): int
    {
        return max(0, (int) $invoice->total_cents - (int) $invoice->amount_paid_cents);
    }

    protected function priceNeedsRefresh(Invoice $invoice, int $chargeAmountCents): bool
    {
        if (! $invoice->stripe_price_id) {
            return true;
        }

        if (! $invoice->stripe_price_currency || strtoupper($invoice->stripe_price_currency) !== strtoupper($invoice->currency)) {
            return true;
        }

        return (int) $invoice->stripe_price_amount_cents !== (int) $chargeAmountCents;
    }
}
