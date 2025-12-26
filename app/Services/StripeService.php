<?php

namespace App\Services;

use App\Models\Invoice;
<<<<<<< HEAD
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
=======
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use Illuminate\Support\Facades\Log;

class StripeService
{
    protected StripeClient $client;
    protected bool $testMode;

    public function __construct()
    {
        $this->testMode = config('stripe.test_mode', true);
        $this->client = new StripeClient(config('stripe.secret'));
    }

    /**
     * Check if running in test mode.
     */
    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    /**
     * Validate that we're not using test keys in production.
     */
    public function validateEnvironment(): bool
    {
        $secret = config('stripe.secret');

        if (app()->environment('production') && str_starts_with($secret, 'sk_test_')) {
            Log::critical('stripe.test_keys_in_production', [
                'message' => 'Test Stripe keys detected in production environment!',
            ]);
            return false;
        }

        return true;
    }

    /**
     * Create a Checkout Session for an invoice.
     */
    public function createCheckoutSession(Invoice $invoice, string $successUrl, string $cancelUrl): Session
    {
        $invoice->loadMissing(['items', 'client', 'organization']);

        $lineItems = $invoice->items->map(function ($item) use ($invoice) {
            return [
                'price_data' => [
                    'currency' => $invoice->currency ?? config('stripe.currency', 'usd'),
                    'product_data' => [
                        'name' => $item->description,
                    ],
                    'unit_amount' => $item->unit_price_cents,
                ],
                'quantity' => $item->quantity,
            ];
        })->toArray();

        // Add tax if applicable
        if ($invoice->tax_cents > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $invoice->currency ?? config('stripe.currency', 'usd'),
                    'product_data' => [
                        'name' => 'Tax',
                    ],
                    'unit_amount' => $invoice->tax_cents,
                ],
                'quantity' => 1,
            ];
        }

        // Subtract discount if applicable
        $discountAmount = $invoice->discount_cents ?? 0;

        $session = $this->client->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $invoice->id,
            'customer_email' => $invoice->client?->email,
            'metadata' => [
                'invoice_id' => $invoice->id,
                'organization_id' => $invoice->organization_id,
                'invoice_number' => $invoice->invoice_number,
            ],
            'discounts' => $discountAmount > 0 ? [] : [], // Stripe requires coupon setup for discounts
        ]);

        // Store the session ID on the invoice
        $invoice->update([
            'stripe_checkout_session_id' => $session->id,
        ]);

        Log::info('stripe.checkout_session_created', [
            'invoice_id' => $invoice->id,
            'session_id' => $session->id,
            'test_mode' => $this->testMode,
        ]);

        return $session;
    }

    /**
     * Retrieve a Checkout Session.
     */
    public function retrieveSession(string $sessionId): Session
    {
        return $this->client->checkout->sessions->retrieve($sessionId, [
            'expand' => ['payment_intent'],
        ]);
    }

    /**
     * Verify webhook signature and construct event.
     */
    public function constructWebhookEvent(string $payload, string $signature): \Stripe\Event
    {
        $webhookSecret = config('stripe.webhook_secret');

        if (empty($webhookSecret)) {
            throw new \RuntimeException('Stripe webhook secret not configured');
        }

        try {
            return Webhook::constructEvent($payload, $signature, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            Log::warning('stripe.webhook_signature_invalid', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get the Stripe client for direct API calls.
     */
    public function getClient(): StripeClient
    {
        return $this->client;
>>>>>>> 6337e80 (feat: Implement comprehensive billing and payment functionality with Stripe integration, invoice management, refunds, and organization-specific settings.)
    }
}
