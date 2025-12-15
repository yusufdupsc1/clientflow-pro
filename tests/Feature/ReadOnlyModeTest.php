<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReadOnlyModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_write_requests_are_blocked_in_read_only_mode(): void
    {
        [$user, $org] = $this->userWithOrg();
        Config::set('app.read_only', true);

        $this->actingAs($user)->post('/clients', [
            'name' => 'Blocked Client',
        ])->assertStatus(503);
    }

    public function test_get_requests_still_work_in_read_only_mode(): void
    {
        [$user] = $this->userWithOrg();
        Config::set('app.read_only', true);

        $this->actingAs($user)->get('/health')->assertOk();
    }

    protected function userWithOrg(): array
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $org = Organization::create([
            'name' => 'ReadOnly Org',
            'slug' => 'readonly-'.Str::random(6),
            'owner_user_id' => $user->id,
        ]);

        $org->users()->attach($user->id, ['role' => 'owner']);
        $user->forceFill(['current_organization_id' => $org->id])->save();

        return [$user, $org];
    }
}
