<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiProjectTest extends TestCase
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

    public function test_admin_can_create_project_member_cannot_update(): void
    {
        [$org, $owner] = $this->createOrgWithOwner('proj');
        Sanctum::actingAs($owner, ['*']);

        $projectId = $this->postJson('/api/projects', [
            'name' => 'API Project',
            'status' => 'draft',
        ])->assertStatus(201)->json('data.id');

        $this->getJson('/api/projects')->assertOk()->assertJsonFragment(['name' => 'API Project']);

        $member = User::factory()->create(['email_verified_at' => now()]);
        $org->users()->attach($member->id, ['role' => 'member']);
        Permissions::syncUserRole($member, $org->id, 'member');
        $member->forceFill(['current_organization_id' => $org->id])->save();
        Sanctum::actingAs($member, ['*']);

        $this->patchJson("/api/projects/{$projectId}", [
            'name' => 'Member Update',
            'status' => 'draft',
        ])->assertForbidden();
    }

    public function test_cross_org_forbidden_on_project_show(): void
    {
        [$orgA, $ownerA] = $this->createOrgWithOwner('pa');
        [$orgB, $ownerB] = $this->createOrgWithOwner('pb');

        Sanctum::actingAs($ownerA, ['*']);
        $projectId = $this->postJson('/api/projects', [
            'name' => 'Project A',
            'status' => 'draft',
        ])->json('data.id');

        Sanctum::actingAs($ownerB, ['*']);
        $this->getJson("/api/projects/{$projectId}")->assertForbidden();
    }
}
