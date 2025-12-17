<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessStripeWebhook;
use App\Models\Organization;
use App\Models\StripeWebhookEvent;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeService $stripeService)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $decoded = json_decode($payload, true) ?: [];

        if (! $signature) {
            return response()->json(['message' => 'Missing Stripe signature.'], 400);
        }

        $organization = null;
        $organizationIdFromPayload = $decoded['data']['object']['metadata']['organization_id'] ?? null;
        $modeFromPayload = ($decoded['livemode'] ?? false) ? 'live' : 'test';

        if ($organizationIdFromPayload) {
            $organization = Organization::find((int) $organizationIdFromPayload);
        }

        $webhookSecret = $stripeService->webhookSecretForMode($modeFromPayload, $organization);

        if (! $webhookSecret) {
            return response()->json(['message' => 'Stripe webhook secret not configured.'], 400);
        }

        try {
            $event = $stripeService->verifyWebhook($payload, $signature, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        } catch (\Throwable $e) {
            Log::error('stripe.webhook.unverified', ['message' => $e->getMessage()]);

            return response()->json(['message' => 'Unable to verify webhook.'], 400);
        }

        $eventId = $event['id'] ?? (string) Str::uuid();

        $metadata = $event['data']['object']['metadata'] ?? [];
        $organizationId = $metadata['organization_id'] ?? $organization?->id ?? null;
        $invoiceId = $metadata['invoice_id'] ?? null;

        Log::info('stripe.webhook.received', [
            'event_id' => $eventId,
            'type' => $event['type'] ?? 'unknown',
            'livemode' => $event['livemode'] ?? false,
            'organization_id' => $organizationId,
            'invoice_id' => $invoiceId,
        ]);

        $record = StripeWebhookEvent::firstOrCreate(
            ['event_id' => $eventId],
            [
                'type' => $event['type'] ?? 'unknown',
                'payload' => $event,
                'live_mode' => (bool) ($event['livemode'] ?? false),
                'received_at' => now(),
                'status' => 'queued',
                'organization_id' => $organizationId,
                'invoice_id' => $invoiceId,
            ]
        );

        if ($record->wasRecentlyCreated || $record->status !== 'processed') {
            ProcessStripeWebhook::dispatch($record);
        }

        return response()->json(['received' => true]);
    }
}
