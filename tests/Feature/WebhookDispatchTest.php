<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\Webhook;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class WebhookDispatchTest extends TestCase
{
    use RefreshDatabase;

    private function createOrgWithOwner(): array
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
            'email' => strtolower('owner+'.Str::random(6).'@example.com'),
        ]);

        $org = Organization::create([
            'name' => 'Org '.Str::random(4),
            'slug' => Str::slug('org-'.Str::random(6)),
            'owner_user_id' => $owner->id,
        ]);

        $org->users()->attach($owner->id, ['role' => 'owner']);
        Permissions::syncUserRole($owner, $org->id, 'owner');
        $owner->forceFill(['current_organization_id' => $org->id])->save();

        return [$org, $owner];
    }

    public function test_invoice_send_and_payment_trigger_webhooks(): void
    {
        Http::fake();

        [$org, $owner] = $this->createOrgWithOwner();

        Webhook::create([
            'organization_id' => $org->id,
            'url' => 'https://example.com/webhook',
            'events' => ['invoices.sent', 'payments.created'],
            'active' => true,
        ]);

        $this->actingAs($owner)->post('/invoices', [
            'title' => 'Invoice WH',
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ])->assertRedirect();

        $invoiceId = \App\Models\Invoice::where('title', 'Invoice WH')->first()->id;

        $this->actingAs($owner)->post("/invoices/{$invoiceId}/send")->assertRedirect();

        $this->actingAs($owner)->patch("/invoices/{$invoiceId}", [
            'title' => 'Invoice WH',
            'status' => 'sent',
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ]);

        $this->actingAs($owner)->post("/invoices/{$invoiceId}/payments", [
            'amount_cents' => 100,
        ])->assertStatus(302);

        Http::assertSentCount(2);
        Http::assertSent(fn ($req) => $req->url() === 'https://example.com/webhook' && $req['event'] === 'invoices.sent');
        Http::assertSent(fn ($req) => $req->url() === 'https://example.com/webhook' && $req['event'] === 'payments.created');
    }
}
