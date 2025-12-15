<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function createOrgWithOwner(string $label = 'org'): array
    {
        $owner = User::factory()->create([
            'name' => ucfirst($label).' Owner',
            'email' => strtolower($label).'.owner+'.Str::random(5).'@example.com',
            'email_verified_at' => now(),
        ]);

        $org = Organization::create([
            'name' => ucfirst($label).' Org',
            'slug' => Str::slug($label.'-'.Str::random(5)),
            'owner_user_id' => $owner->id,
        ]);

        $org->users()->attach($owner->id, ['role' => 'owner']);
        Permissions::syncUserRole($owner, $org->id, 'owner');
        $owner->forceFill(['current_organization_id' => $org->id])->save();

        return [$org, $owner];
    }

    public function test_owner_can_post_payment_member_forbidden(): void
    {
        [$org, $owner] = $this->createOrgWithOwner('pay');
        Sanctum::actingAs($owner, ['*']);

        $invoiceId = $this->postJson('/api/invoices', [
            'title' => 'Invoice Pay',
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ])->json('data.id');

        $this->patchJson("/api/invoices/{$invoiceId}", [
            'title' => 'Invoice Pay',
            'status' => 'sent',
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ])->assertOk();

        $this->postJson("/api/invoices/{$invoiceId}/payments", [
            'amount_cents' => 100,
        ])->assertStatus(201);

        $member = User::factory()->create(['email_verified_at' => now()]);
        $org->users()->attach($member->id, ['role' => 'member']);
        Permissions::syncUserRole($member, $org->id, 'member');
        $member->forceFill(['current_organization_id' => $org->id])->save();
        Sanctum::actingAs($member, ['*']);

        $this->postJson("/api/invoices/{$invoiceId}/payments", [
            'amount_cents' => 100,
        ])->assertForbidden();
    }

    public function test_cross_org_payment_forbidden(): void
    {
        [$orgA, $ownerA] = $this->createOrgWithOwner('pa');
        [$orgB, $ownerB] = $this->createOrgWithOwner('pb');

        Sanctum::actingAs($ownerA, ['*']);
        $invoiceId = $this->postJson('/api/invoices', [
            'title' => 'Invoice Cross',
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ])->json('data.id');

        Sanctum::actingAs($ownerB, ['*']);
        $this->postJson("/api/invoices/{$invoiceId}/payments", [
            'amount_cents' => 100,
        ])->assertForbidden();
    }
}
