<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RolePolicyCoverageTest extends TestCase
{
    use RefreshDatabase;

    private function createOrgWithOwner(): User
    {
        $owner = User::factory()->create([
            'name' => 'Owner',
            'email' => strtolower('owner+'.Str::random(6).'@example.com'),
            'email_verified_at' => now(),
        ]);

        $org = \App\Models\Organization::create([
            'name' => 'Org '.Str::random(4),
            'slug' => Str::slug('org-'.Str::random(6)),
            'owner_user_id' => $owner->id,
        ]);

        $org->users()->syncWithoutDetaching([$owner->id => ['role' => 'owner']]);
        Permissions::syncUserRole($owner, $org->id, 'owner');
        $owner->forceFill(['current_organization_id' => $org->id])->save();

        return $owner;
    }

    private function addUserToOrg(User $owner, string $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $org = $owner->currentOrganization()->firstOrFail();
        $org->users()->syncWithoutDetaching([$user->id => ['role' => $role]]);
        $user->forceFill(['current_organization_id' => $org->id])->save();

        return $user;
    }

    public function test_member_can_view_but_cannot_update_project(): void
    {
        $owner = $this->createOrgWithOwner();
        $this->actingAs($owner)->post('/projects', [
            'name' => 'Project A',
            'status' => 'draft',
        ])->assertRedirect();

        $project = Project::where('name', 'Project A')->firstOrFail();

        $member = $this->addUserToOrg($owner, 'member');

        $this->actingAs($member)->get("/projects/{$project->id}")->assertOk();
        $this->actingAs($member)->patch("/projects/{$project->id}", [
            'name' => 'Should Fail',
            'status' => 'draft',
        ])->assertForbidden();
    }

    public function test_admin_can_update_invoice_member_cannot(): void
    {
        $owner = $this->createOrgWithOwner();

        $this->actingAs($owner)->post('/invoices', [
            'title' => 'Invoice A',
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ])->assertRedirect();

        $invoice = Invoice::where('title', 'Invoice A')->firstOrFail();

        $admin = $this->addUserToOrg($owner, 'admin');
        $member = $this->addUserToOrg($owner, 'member');

        $this->actingAs($admin)->patch("/invoices/{$invoice->id}", [
            'title' => 'Invoice A Updated',
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ])->assertRedirect();

        $this->actingAs($member)->patch("/invoices/{$invoice->id}", [
            'title' => 'Member Update',
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ])->assertForbidden();
    }

    public function test_member_cannot_post_payment(): void
    {
        $owner = $this->createOrgWithOwner();

        $this->actingAs($owner)->post('/invoices', [
            'title' => 'Invoice Pay',
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ])->assertRedirect();

        $invoice = Invoice::where('title', 'Invoice Pay')->firstOrFail();

        $member = $this->addUserToOrg($owner, 'member');

        $this->actingAs($member)->post("/invoices/{$invoice->id}/payments", [
            'amount_cents' => 100,
        ])->assertForbidden();
    }

    public function test_cross_org_access_forbidden_for_resources(): void
    {
        $ownerA = $this->createOrgWithOwner();
        $ownerB = $this->createOrgWithOwner();

        $this->actingAs($ownerA)->post('/clients', ['name' => 'OrgA Client'])->assertRedirect();
        $client = Client::where('name', 'OrgA Client')->firstOrFail();

        $this->actingAs($ownerB)->get("/clients/{$client->id}")->assertForbidden();
    }
}
