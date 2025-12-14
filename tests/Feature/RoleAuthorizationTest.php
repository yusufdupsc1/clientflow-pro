<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_client(): void
    {
        // Owner sets up org
        $ownerEmail = strtolower('owner+'.Str::random(6).'@example.com');
        $this->post('/register', [
            'name' => 'Owner',
            'email' => $ownerEmail,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');
        $owner = User::where('email', $ownerEmail)->firstOrFail();
        $orgId = $owner->current_organization_id;

        // Admin joins org
        $admin = User::factory()->create(['email' => strtolower('admin+'.Str::random(6).'@example.com')]);
        $admin->organizations()->attach($orgId, ['role' => 'admin']);
        $admin->forceFill(['current_organization_id' => $orgId])->save();

        $this->actingAs($admin)->post('/clients', ['name' => 'Admin Client'])->assertRedirect();
        $this->assertDatabaseHas('clients', ['name' => 'Admin Client', 'organization_id' => $orgId]);
    }

    public function test_member_cannot_create_client(): void
    {
        $ownerEmail = strtolower('owner+'.Str::random(6).'@example.com');
        $this->post('/register', [
            'name' => 'Owner',
            'email' => $ownerEmail,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');
        $owner = User::where('email', $ownerEmail)->firstOrFail();
        $orgId = $owner->current_organization_id;

        $member = User::factory()->create(['email' => strtolower('member+'.Str::random(6).'@example.com')]);
        $member->organizations()->attach($orgId, ['role' => 'member']);
        $member->forceFill(['current_organization_id' => $orgId])->save();

        $this->actingAs($member)->post('/clients', ['name' => 'Member Client'])->assertForbidden();
        $this->assertDatabaseMissing('clients', ['name' => 'Member Client']);
    }

    public function test_member_can_view_invoice(): void
    {
        $ownerEmail = strtolower('owner+'.Str::random(6).'@example.com');
        $this->post('/register', [
            'name' => 'Owner',
            'email' => $ownerEmail,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');
        $owner = User::where('email', $ownerEmail)->firstOrFail();
        $orgId = $owner->current_organization_id;

        $this->actingAs($owner)->post('/invoices', [
            'title' => 'Owner Invoice',
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ])->assertRedirect();
        $invoice = Invoice::where('title', 'Owner Invoice')->firstOrFail();

        $member = User::factory()->create(['email' => strtolower('member+'.Str::random(6).'@example.com')]);
        $member->organizations()->attach($orgId, ['role' => 'member']);
        $member->forceFill(['current_organization_id' => $orgId])->save();

        $this->actingAs($member)->get('/invoices/'.$invoice->id)->assertOk();
    }

    public function test_owner_can_delete_client(): void
    {
        $ownerEmail = strtolower('owner+'.Str::random(6).'@example.com');
        $this->post('/register', [
            'name' => 'Owner',
            'email' => $ownerEmail,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');
        $owner = User::where('email', $ownerEmail)->firstOrFail();
        $orgId = $owner->current_organization_id;

        $this->actingAs($owner)->post('/clients', ['name' => 'Owner Client'])->assertRedirect();
        $client = Client::where('name', 'Owner Client')->firstOrFail();

        $this->actingAs($owner)->delete('/clients/'.$client->id)->assertRedirect();
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }
}
