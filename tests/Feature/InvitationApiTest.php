<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvitationApiTest extends TestCase
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

    public function test_owner_can_create_invite_via_api(): void
    {
        [$org, $owner] = $this->createOrgWithOwner('api-create');

        Sanctum::actingAs($owner, ['*']);

        $response = $this->postJson("/api/organizations/{$org->id}/invites", [
            'email' => 'newuser@example.com',
            'role' => 'member',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'organization_id' => $org->id,
                'email' => 'newuser@example.com',
                'role' => 'member',
            ]);

        $this->assertDatabaseHas('invitations', [
            'organization_id' => $org->id,
            'email' => 'newuser@example.com',
            'role' => 'member',
        ]);
    }

    public function test_member_and_cross_org_cannot_create_invite_via_api(): void
    {
        [$org, $owner] = $this->createOrgWithOwner('api-member');
        $member = User::factory()->create(['email_verified_at' => now()]);
        $org->users()->attach($member->id, ['role' => 'member']);
        Permissions::syncUserRole($member, $org->id, 'member');
        $member->forceFill(['current_organization_id' => $org->id])->save();

        Sanctum::actingAs($member, ['*']);

        $this->postJson("/api/organizations/{$org->id}/invites", [
            'email' => 'fail@example.com',
            'role' => 'member',
        ])->assertForbidden();

        [$otherOrg, $otherOwner] = $this->createOrgWithOwner('api-cross');
        Sanctum::actingAs($otherOwner, ['*']);

        $this->postJson("/api/organizations/{$org->id}/invites", [
            'email' => 'cross@example.com',
            'role' => 'member',
        ])->assertForbidden();
    }

    public function test_accept_invite_via_api_creates_user_and_attaches(): void
    {
        [$org, $owner] = $this->createOrgWithOwner('api-accept');

        Sanctum::actingAs($owner, ['*']);

        $createResponse = $this->postJson("/api/organizations/{$org->id}/invites", [
            'email' => 'invitee@example.com',
            'role' => 'admin',
        ])->assertStatus(201);

        $token = $createResponse->json('token');

        $this->postJson("/api/invites/{$token}", [
            'name' => 'Invitee',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertOk();

        $user = User::where('email', 'invitee@example.com')->firstOrFail();
        $invite = Invitation::where('email', 'invitee@example.com')->firstOrFail();

        $this->assertEquals($org->id, $user->current_organization_id);
        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);
        $this->assertNotNull($invite->accepted_at);
        $this->assertNull($invite->token);
    }

    public function test_owner_can_update_member_role_via_api_member_cannot(): void
    {
        [$org, $owner] = $this->createOrgWithOwner('api-roles');
        $member = User::factory()->create(['email_verified_at' => now()]);
        $org->users()->attach($member->id, ['role' => 'member']);
        Permissions::syncUserRole($member, $org->id, 'member');
        $member->forceFill(['current_organization_id' => $org->id])->save();

        Sanctum::actingAs($owner, ['*']);

        $this->patchJson("/api/organizations/{$org->id}/members/{$member->id}", [
            'role' => 'admin',
        ])->assertOk();

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $org->id,
            'user_id' => $member->id,
            'role' => 'admin',
        ]);

        Sanctum::actingAs($member, ['*']);

        $this->patchJson("/api/organizations/{$org->id}/members/{$owner->id}", [
            'role' => 'member',
        ])->assertForbidden();
    }
}
