<?php

namespace App\Support\Webhooks;

use App\Models\Webhook;
use Illuminate\Support\Facades\Http;

class WebhookDispatcher
{
    public static function dispatch(string $event, int $organizationId, array $payload = []): void
    {
        $webhooks = Webhook::query()
            ->where('organization_id', $organizationId)
            ->where('active', true)
            ->get();

        foreach ($webhooks as $webhook) {
            if (! $webhook->handles($event)) {
                continue;
            }

            Http::withHeaders([
                'X-Clientflow-Event' => $event,
            ])->post($webhook->url, [
                'event' => $event,
                'organization_id' => $organizationId,
                'payload' => $payload,
            ]);
        }
    }
}
