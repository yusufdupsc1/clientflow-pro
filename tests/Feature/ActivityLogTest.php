<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_client_logs_activity(): void
    {
        $owner = $this->registerOwner();
        $orgId = $owner->current_organization_id;

        $this->actingAs($owner)->post('/clients', [
            'name' => 'Activity Client',
        ])->assertRedirect();

        $client = Client::where('name', 'Activity Client')->firstOrFail();

        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $orgId,
            'actor_user_id' => $owner->id,
            'action' => 'clients.created',
            'subject_type' => Client::class,
            'subject_id' => $client->id,
        ]);
    }

    public function test_updating_project_logs_activity(): void
    {
        $owner = $this->registerOwner();
        $orgId = $owner->current_organization_id;

        $this->actingAs($owner)->post('/projects', [
            'name' => 'Initial Project',
            'status' => 'draft',
        ])->assertRedirect();

        $project = Project::where('name', 'Initial Project')->firstOrFail();

        $this->actingAs($owner)->put('/projects/'.$project->id, [
            'name' => 'Updated Project',
            'status' => 'active',
        ])->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $orgId,
            'actor_user_id' => $owner->id,
            'action' => 'projects.updated',
            'subject_type' => Project::class,
            'subject_id' => $project->id,
        ]);
    }

    public function test_deleting_invoice_logs_activity(): void
    {
        $owner = $this->registerOwner();
        $orgId = $owner->current_organization_id;

        $this->actingAs($owner)->post('/invoices', [
            'title' => 'Log Invoice',
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ])->assertRedirect();

        $invoice = Invoice::where('title', 'Log Invoice')->firstOrFail();

        $this->actingAs($owner)->delete('/invoices/'.$invoice->id)->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $orgId,
            'actor_user_id' => $owner->id,
            'action' => 'invoices.deleted',
            'subject_type' => Invoice::class,
            'subject_id' => $invoice->id,
        ]);
    }

    protected function registerOwner(): User
    {
        $email = strtolower('owner+'.Str::random(6).'@example.com');

        $this->post('/register', [
            'name' => 'Owner',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');

        return User::where('email', $email)->firstOrFail();
    }
}
