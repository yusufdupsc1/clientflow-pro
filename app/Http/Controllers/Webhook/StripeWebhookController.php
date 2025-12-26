<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessStripeWebhook;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeService $stripe): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (empty($signature)) {
            Log::warning('stripe.webhook_missing_signature');
            return response('Missing signature', 400);
        }

        try {
            $event = $stripe->constructWebhookEvent($payload, $signature);
        } catch (SignatureVerificationException $e) {
            Log::warning('stripe.webhook_invalid_signature', [
                'error' => $e->getMessage(),
            ]);
            return response('Invalid signature', 400);
        } catch (\Exception $e) {
            Log::error('stripe.webhook_error', [
                'error' => $e->getMessage(),
            ]);
            return response('Webhook error', 400);
        }

        Log::info('stripe.webhook_received', [
            'type' => $event->type,
            'id' => $event->id,
        ]);

        // Dispatch to queue for async processing
        ProcessStripeWebhook::dispatch($event->toArray());

        return response('OK', 200);
    }
}
