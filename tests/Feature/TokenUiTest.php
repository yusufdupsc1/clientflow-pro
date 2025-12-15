<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TokenUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_and_revoke_tokens_via_ui(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'current_organization_id' => null,
        ]);

        $token = $user->createToken('api', ['*'])->plainTextToken;
        $this->assertNotEmpty($token);

        $response = $this->actingAs($user)->get('/tokens');
        $response->assertOk()->assertSee('API Tokens');

        $tokenId = $user->tokens()->latest()->first()->id;

        $this->actingAs($user)->delete("/tokens/{$tokenId}")
            ->assertRedirect('/tokens');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_user_cannot_delete_someone_else_token_via_ui(): void
    {
        $userA = User::factory()->create(['email_verified_at' => now()]);
        $userB = User::factory()->create(['email_verified_at' => now()]);

        $tokenId = $userA->createToken('api', ['*'])->accessToken->id ?? $userA->tokens()->latest()->first()->id;

        $this->actingAs($userB)->delete("/tokens/{$tokenId}")
            ->assertForbidden();
    }
}
