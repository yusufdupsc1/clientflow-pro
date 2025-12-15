<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TokenApiTest extends TestCase
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

    public function test_owner_can_issue_token_and_use_it_for_invite(): void
    {
        [$org, $owner] = $this->createOrgWithOwner('token');

        $issue = $this->actingAs($owner)->postJson('/api/tokens')
            ->assertStatus(201)
            ->json('token');

        $this->assertNotEmpty($issue);

        $this->withToken($issue)->postJson("/api/organizations/{$org->id}/invites", [
            'email' => 'apiuser@example.com',
            'role' => 'member',
        ])->assertStatus(201);
    }

    public function test_token_cannot_manage_other_org(): void
    {
        [$orgA, $ownerA] = $this->createOrgWithOwner('a');
        [$orgB, $ownerB] = $this->createOrgWithOwner('b');

        $tokenA = $this->actingAs($ownerA)->postJson('/api/tokens')
            ->assertStatus(201)
            ->json('token');

        $this->withToken($tokenA)->postJson("/api/organizations/{$orgB->id}/invites", [
            'email' => 'x@example.com',
            'role' => 'member',
        ])->assertForbidden();
    }
}
