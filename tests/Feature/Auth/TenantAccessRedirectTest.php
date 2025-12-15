<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantAccessRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_unverified_user_is_redirected_to_verification_notice(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_verified_user_without_current_organization_is_redirected_to_selection(): void
    {
        $user = User::factory()->create([
            'current_organization_id' => null,
        ]);

        $organization = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-'.Str::random(6),
            'owner_user_id' => $user->id,
        ]);

        $user->organizations()->attach($organization->id, ['role' => 'owner']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/organizations/select');
    }
}
