<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_user_org_pivot_and_sets_current_org(): void
    {
        $email = 'user+'.Str::lower(Str::random(6)).'@example.com';

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/dashboard');

        $user = User::where('email', $email)->first();
        $this->assertNotNull($user, 'User was not created');

        $organization = Organization::where('owner_user_id', $user->id)->first();
        $this->assertNotNull($organization, 'Organization was not created');

        $this->assertEquals(
            $organization->id,
            $user->current_organization_id,
            'current_organization_id was not set'
        );

        $this->assertDatabaseHas('organization_user', [
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'owner',
        ]);
    }

    public function test_data_from_other_organizations_is_inaccessible(): void
    {
        // Register User A
        $emailA = strtolower('usera+'.Str::random(6).'@example.com');
        $this->post('/register', [
            'name' => 'User A',
            'email' => $emailA,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');
        $userA = User::where('email', $emailA)->firstOrFail();
        $orgA = Organization::where('owner_user_id', $userA->id)->firstOrFail();

        Auth::logout();

        // Register User B
        $emailB = strtolower('userb+'.Str::random(6).'@example.com');
        $this->post('/register', [
            'name' => 'User B',
            'email' => $emailB,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');
        $userB = User::where('email', $emailB)->firstOrFail();
        $orgB = Organization::where('owner_user_id', $userB->id)->firstOrFail();

        // User B tries to switch to Org A (not a member) and access dashboard -> forbidden
        $userB->forceFill([
            'current_organization_id' => $orgA->id,
            'email_verified_at' => now(),
        ])->save();

        $this->actingAs($userB)
            ->get('/dashboard')
            ->assertForbidden();

        // Control: User B with their own org is allowed
        $userB->forceFill(['current_organization_id' => $orgB->id])->save();
        $userB->unsetRelation('currentOrganization');

        $this->assertDatabaseHas('organization_user', [
            'user_id' => $userB->id,
            'organization_id' => $orgB->id,
        ]);

        $this->actingAs($userB->fresh())
            ->get('/dashboard')
            ->assertOk();
    }
}
