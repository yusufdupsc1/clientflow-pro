<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TokenManagementTest extends TestCase
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

    public function test_user_can_list_and_revoke_own_tokens(): void
    {
        [, $owner] = $this->createOrgWithOwner('tokens');

        $token = $this->actingAs($owner)->postJson('/api/tokens')->json('token');
        $this->assertNotEmpty($token);

        $list = $this->actingAs($owner)->getJson('/api/tokens')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $list);
        $tokenId = $list[0]['id'];

        $this->actingAs($owner)->deleteJson("/api/tokens/{$tokenId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
            'tokenable_id' => $owner->id,
        ]);
    }

    public function test_user_cannot_revoke_others_token(): void
    {
        [, $ownerA] = $this->createOrgWithOwner('alpha');
        [, $ownerB] = $this->createOrgWithOwner('bravo');

        $tokenId = $this->actingAs($ownerA)->postJson('/api/tokens')->json('token_id');

        $this->actingAs($ownerB)->deleteJson("/api/tokens/{$tokenId}")
            ->assertForbidden();
    }
}
