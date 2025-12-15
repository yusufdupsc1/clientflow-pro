<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrganizationSelectionTest extends TestCase
{
    use RefreshDatabase;

    private function createOrgFor(User $user, string $label): Organization
    {
        $org = Organization::create([
            'name' => ucfirst($label).' Org',
            'slug' => Str::slug($label.'-'.Str::random(5)),
            'owner_user_id' => $user->id,
        ]);

        $org->users()->attach($user->id, ['role' => 'owner']);

        return $org;
    }

    public function test_missing_current_org_redirects_to_select(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $org = $this->createOrgFor($user, 'select');
        $user->forceFill(['current_organization_id' => null])->save();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('organizations.select'));
    }

    public function test_can_switch_current_org_when_member(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $orgA = $this->createOrgFor($user, 'a');
        $orgB = $this->createOrgFor($user, 'b');
        $user->forceFill(['current_organization_id' => $orgA->id])->save();

        $this->actingAs($user)->post(route('organizations.select.store'), [
            'organization_id' => $orgB->id,
        ])->assertRedirect(route('dashboard'));

        $this->assertEquals($orgB->id, $user->fresh()->current_organization_id);
    }

    public function test_invitation_link_hidden_for_member(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $org = $this->createOrgFor($owner, 'nav');
        $owner->forceFill(['current_organization_id' => $org->id])->save();

        $this->actingAs($owner)->get('/dashboard')->assertSee('Invitations');

        $member = User::factory()->create(['email_verified_at' => now()]);
        $org->users()->attach($member->id, ['role' => 'member']);
        $member->forceFill(['current_organization_id' => $org->id])->save();

        $this->actingAs($member)->get('/dashboard')->assertDontSee('Invitations');
    }
}
