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

class ApiInvoiceTest extends TestCase
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

    public function test_owner_can_create_and_update_invoice_member_cannot(): void
    {
        [$org, $owner] = $this->createOrgWithOwner('inv');
        Sanctum::actingAs($owner, ['*']);

        $invoiceId = $this->postJson('/api/invoices', [
            'title' => 'API Invoice',
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ])->assertStatus(201)->json('data.id');

        $this->getJson('/api/invoices?status=draft')->assertOk()->assertJsonFragment(['title' => 'API Invoice']);

        $member = User::factory()->create(['email_verified_at' => now()]);
        $org->users()->attach($member->id, ['role' => 'member']);
        Permissions::syncUserRole($member, $org->id, 'member');
        $member->forceFill(['current_organization_id' => $org->id])->save();
        Sanctum::actingAs($member, ['*']);

        $this->patchJson("/api/invoices/{$invoiceId}", [
            'title' => 'Member Update',
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ])->assertForbidden();
    }

    public function test_cross_org_invoice_access_forbidden(): void
    {
        [$orgA, $ownerA] = $this->createOrgWithOwner('ia');
        [$orgB, $ownerB] = $this->createOrgWithOwner('ib');

        Sanctum::actingAs($ownerA, ['*']);
        $invoiceId = $this->postJson('/api/invoices', [
            'title' => 'Invoice X',
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ])->json('data.id');

        Sanctum::actingAs($ownerB, ['*']);
        $this->getJson("/api/invoices/{$invoiceId}")->assertForbidden();
    }
}
