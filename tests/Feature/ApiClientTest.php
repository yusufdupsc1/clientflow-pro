<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiClientTest extends TestCase
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

    public function test_owner_can_crud_clients_member_cannot_create(): void
    {
        [$org, $owner] = $this->createOrgWithOwner('clients');
        Sanctum::actingAs($owner, ['*']);

        $create = $this->postJson("/api/clients", [
            'name' => 'API Client',
            'email' => 'client@example.com',
        ])->assertStatus(201)->json('data.id');

        $this->getJson('/api/clients')->assertOk()->assertJsonFragment(['name' => 'API Client']);

        $this->getJson("/api/clients/{$create}")->assertOk()->assertJsonFragment(['email' => 'client@example.com']);

        $this->patchJson("/api/clients/{$create}", [
            'name' => 'Renamed Client',
        ])->assertOk()->assertJsonFragment(['name' => 'Renamed Client']);

        $this->deleteJson("/api/clients/{$create}")->assertNoContent();

        $member = User::factory()->create(['email_verified_at' => now()]);
        $org->users()->attach($member->id, ['role' => 'member']);
        Permissions::syncUserRole($member, $org->id, 'member');
        $member->forceFill(['current_organization_id' => $org->id])->save();
        Sanctum::actingAs($member, ['*']);

        $this->postJson("/api/clients", ['name' => 'Should Fail'])->assertForbidden();
    }

    public function test_cross_org_forbidden(): void
    {
        [$orgA, $ownerA] = $this->createOrgWithOwner('a');
        [$orgB, $ownerB] = $this->createOrgWithOwner('b');

        Sanctum::actingAs($ownerA, ['*']);
        $clientId = $this->postJson("/api/clients", ['name' => 'OrgA Client'])->json('data.id');

        Sanctum::actingAs($ownerB, ['*']);
        $this->getJson("/api/clients/{$clientId}")->assertForbidden();
    }
}
