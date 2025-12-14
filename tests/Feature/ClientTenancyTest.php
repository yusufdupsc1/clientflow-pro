<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClientTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_client_sets_organization_id_from_tenant(): void
    {
        $email = strtolower('user+'.Str::random(6).'@example.com');

        $this->post('/register', [
            'name' => 'Owner',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');

        $user = User::where('email', $email)->firstOrFail();
        $organization = Organization::where('owner_user_id', $user->id)->firstOrFail();

        $response = $this->actingAs($user)->post('/clients', [
            'name' => 'Acme Client',
            'email' => 'client@example.com',
        ]);

        $response->assertRedirect();

        $client = Client::where('name', 'Acme Client')->first();
        $this->assertNotNull($client, 'Client was not created');
        $this->assertEquals($organization->id, $client->organization_id);
    }

    public function test_user_cannot_access_other_organization_clients(): void
    {
        // User A and client
        $emailA = strtolower('usera+'.Str::random(6).'@example.com');
        $this->post('/register', [
            'name' => 'User A',
            'email' => $emailA,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');
        $userA = User::where('email', $emailA)->firstOrFail();
        $this->actingAs($userA)->post('/clients', [
            'name' => 'Org A Client',
        ])->assertRedirect();
        $clientA = Client::where('name', 'Org A Client')->firstOrFail();

        Auth::logout();

        // User B
        $emailB = strtolower('userb+'.Str::random(6).'@example.com');
        $this->post('/register', [
            'name' => 'User B',
            'email' => $emailB,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');
        $userB = User::where('email', $emailB)->firstOrFail();

        $userB->forceFill(['current_organization_id' => $clientA->organization_id])->save();

        $this->actingAs($userB)->get(route('clients.show', $clientA))->assertForbidden();
        $this->actingAs($userB)->get(route('clients.edit', $clientA))->assertForbidden();
        $this->actingAs($userB)->delete(route('clients.destroy', $clientA))->assertForbidden();
    }

    public function test_index_only_shows_current_tenant_clients(): void
    {
        // User A with clients
        $emailA = strtolower('usera+'.Str::random(6).'@example.com');
        $this->post('/register', [
            'name' => 'User A',
            'email' => $emailA,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');
        $userA = User::where('email', $emailA)->firstOrFail();
        $this->actingAs($userA)->post('/clients', ['name' => 'Client A1'])->assertRedirect();
        $this->actingAs($userA)->post('/clients', ['name' => 'Client A2'])->assertRedirect();

        Auth::logout();

        // User B with client
        $emailB = strtolower('userb+'.Str::random(6).'@example.com');
        $this->post('/register', [
            'name' => 'User B',
            'email' => $emailB,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');
        $userB = User::where('email', $emailB)->firstOrFail();
        $this->actingAs($userB)->post('/clients', ['name' => 'Client B1'])->assertRedirect();

        // Assert user A only sees A clients
        $response = $this->actingAs($userA)->get('/clients');
        $response->assertOk();
        $clients = $response->viewData('clients');
        $this->assertCount(2, $clients->items());
        $this->assertTrue($clients->pluck('name')->contains('Client A1'));
        $this->assertTrue($clients->pluck('name')->contains('Client A2'));
        $this->assertFalse($clients->pluck('name')->contains('Client B1'));
    }
}
