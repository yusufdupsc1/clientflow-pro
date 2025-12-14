<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjectTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_project_sets_organization_id_from_tenant(): void
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

        $response = $this->actingAs($user)->post('/projects', [
            'name' => 'Build App',
            'status' => 'draft',
        ]);

        $response->assertRedirect();

        $project = Project::where('name', 'Build App')->first();
        $this->assertNotNull($project, 'Project was not created');
        $this->assertEquals($organization->id, $project->organization_id);
    }

    public function test_user_cannot_access_other_organization_projects(): void
    {
        // User A and project
        $emailA = strtolower('usera+'.Str::random(6).'@example.com');
        $this->post('/register', [
            'name' => 'User A',
            'email' => $emailA,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');
        $userA = User::where('email', $emailA)->firstOrFail();
        $this->actingAs($userA)->post('/projects', [
            'name' => 'Org A Project',
            'status' => 'draft',
        ])->assertRedirect();
        $projectA = Project::where('name', 'Org A Project')->firstOrFail();

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

        $userB->forceFill(['current_organization_id' => $projectA->organization_id])->save();

        $this->actingAs($userB)->get(route('projects.show', $projectA))->assertForbidden();
        $this->actingAs($userB)->get(route('projects.edit', $projectA))->assertForbidden();
        $this->actingAs($userB)->delete(route('projects.destroy', $projectA))->assertForbidden();
    }

    public function test_index_only_shows_current_tenant_projects(): void
    {
        // User A with projects
        $emailA = strtolower('usera+'.Str::random(6).'@example.com');
        $this->post('/register', [
            'name' => 'User A',
            'email' => $emailA,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');
        $userA = User::where('email', $emailA)->firstOrFail();
        $this->actingAs($userA)->post('/projects', ['name' => 'Project A1', 'status' => 'draft'])->assertRedirect();
        $this->actingAs($userA)->post('/projects', ['name' => 'Project A2', 'status' => 'draft'])->assertRedirect();

        Auth::logout();

        // User B with project
        $emailB = strtolower('userb+'.Str::random(6).'@example.com');
        $this->post('/register', [
            'name' => 'User B',
            'email' => $emailB,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');
        $userB = User::where('email', $emailB)->firstOrFail();
        $this->actingAs($userB)->post('/projects', ['name' => 'Project B1', 'status' => 'draft'])->assertRedirect();

        // Assert user A only sees A projects
        $response = $this->actingAs($userA)->get('/projects');
        $response->assertOk();
        $projects = $response->viewData('projects');
        $this->assertCount(2, $projects->items());
        $this->assertTrue($projects->pluck('name')->contains('Project A1'));
        $this->assertTrue($projects->pluck('name')->contains('Project A2'));
        $this->assertFalse($projects->pluck('name')->contains('Project B1'));
    }

    public function test_cannot_create_project_with_foreign_client(): void
    {
        // User A with client
        $emailA = strtolower('usera+'.Str::random(6).'@example.com');
        $this->post('/register', [
            'name' => 'User A',
            'email' => $emailA,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');
        $userA = User::where('email', $emailA)->firstOrFail();
        $this->actingAs($userA)->post('/clients', ['name' => 'Client A'])->assertRedirect();
        $clientA = Client::where('name', 'Client A')->firstOrFail();

        Auth::logout();

        // User B attempts to use Client A
        $emailB = strtolower('userb+'.Str::random(6).'@example.com');
        $this->post('/register', [
            'name' => 'User B',
            'email' => $emailB,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');
        $userB = User::where('email', $emailB)->firstOrFail();

        $response = $this->actingAs($userB)->postJson('/projects', [
            'name' => 'Project B',
            'status' => 'draft',
            'client_id' => $clientA->id,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('client_id');
        $this->assertDatabaseMissing('projects', ['name' => 'Project B']);
    }
}
