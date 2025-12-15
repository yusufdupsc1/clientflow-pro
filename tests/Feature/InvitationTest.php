<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvitationTest extends TestCase
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

    public function test_owner_can_create_invite_member_cannot(): void
    {
        [$org, $owner] = $this->createOrgWithOwner('invite');
        $member = User::factory()->create(['email_verified_at' => now()]);
        $org->users()->attach($member->id, ['role' => 'member']);
        Permissions::syncUserRole($member, $org->id, 'member');
        $member->forceFill(['current_organization_id' => $org->id])->save();

        $this->actingAs($owner)->post("/organizations/{$org->id}/invites", [
            'email' => 'newuser@example.com',
            'role' => 'member',
        ])->assertRedirect();

        $this->assertDatabaseHas('invitations', [
            'organization_id' => $org->id,
            'email' => 'newuser@example.com',
            'role' => 'member',
        ]);

        $this->actingAs($member)->post("/organizations/{$org->id}/invites", [
            'email' => 'fail@example.com',
            'role' => 'member',
        ])->assertForbidden();
    }

    public function test_accept_invite_creates_user_and_attaches_role(): void
    {
        [$org, $owner] = $this->createOrgWithOwner('accept');

        $this->actingAs($owner)->post("/organizations/{$org->id}/invites", [
            'email' => 'invitee@example.com',
            'role' => 'member',
        ])->assertRedirect();

        $invite = Invitation::where('email', 'invitee@example.com')->firstOrFail();

        Auth::logout();

        $this->post("/invites/{$invite->token}", [
            'name' => 'Invitee',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');

        $user = User::where('email', 'invitee@example.com')->firstOrFail();

        $this->assertEquals($org->id, $user->current_organization_id);
        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);

        $invite->refresh();
        $this->assertNotNull($invite->accepted_at);
        $this->assertNull($invite->token);
    }

    public function test_existing_user_accepts_invite_without_duplicate(): void
    {
        [$org, $owner] = $this->createOrgWithOwner('existing');

        $existing = User::factory()->create([
            'email' => 'existing@example.com',
            'email_verified_at' => now(),
        ]);
        $existing->forceFill(['current_organization_id' => $org->id])->save();

        $this->actingAs($owner)->post("/organizations/{$org->id}/invites", [
            'email' => 'existing@example.com',
            'role' => 'admin',
        ])->assertRedirect();

        $invite = Invitation::where('email', 'existing@example.com')->firstOrFail();

        $this->post("/invites/{$invite->token}", [
            'name' => 'Existing User',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertEquals(1, $org->users()->where('users.id', $existing->id)->count());
        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $org->id,
            'user_id' => $existing->id,
            'role' => 'admin',
        ]);
    }

    public function test_owner_can_change_member_role_member_cannot(): void
    {
        [$org, $owner] = $this->createOrgWithOwner('roles');
        $member = User::factory()->create(['email_verified_at' => now()]);
        $org->users()->attach($member->id, ['role' => 'member']);
        Permissions::syncUserRole($member, $org->id, 'member');

        $this->actingAs($owner)->patch("/organizations/{$org->id}/members/{$member->id}", [
            'role' => 'admin',
        ])->assertRedirect();

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $org->id,
            'user_id' => $member->id,
            'role' => 'admin',
        ]);

        // revert member back to member role
        $org->users()->updateExistingPivot($member->id, ['role' => 'member']);
        Permissions::syncUserRole($member, $org->id, 'member');
        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $org->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);
        $this->assertEquals('member', $org->users()->whereKey($member->id)->first()->pivot->role);
        $member->forceFill(['current_organization_id' => $org->id])->save();

        $other = User::factory()->create(['email_verified_at' => now()]);
        $org->users()->attach($other->id, ['role' => 'member']);
        Permissions::syncUserRole($other, $org->id, 'member');
        $other->forceFill(['current_organization_id' => $org->id])->save();

        $this->actingAs($member->fresh())->patchJson("/organizations/{$org->id}/members/{$other->id}", [
            'role' => 'owner',
        ])->assertForbidden();

        $this->assertEquals('member', $org->users()->whereKey($other->id)->first()->pivot->role);
    }

    public function test_cross_org_invite_creation_forbidden(): void
    {
        [$orgA, $ownerA] = $this->createOrgWithOwner('a');
        [$orgB, $ownerB] = $this->createOrgWithOwner('b');

        $this->actingAs($ownerB)->post("/organizations/{$orgA->id}/invites", [
            'email' => 'x@example.com',
            'role' => 'member',
        ])->assertForbidden();
    }
}
