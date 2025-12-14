<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_invoice_computes_totals_server_side(): void
    {
        $email = strtolower('owner+'.Str::random(6).'@example.com');

        $this->post('/register', [
            'name' => 'Owner',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');

        $user = User::where('email', $email)->firstOrFail();
        $client = $this->actingAs($user)->post('/clients', ['name' => 'Client One']);
        $clientModel = Client::where('name', 'Client One')->firstOrFail();

        $response = $this->actingAs($user)->post('/invoices', [
            'title' => 'Invoice 1',
            'client_id' => $clientModel->id,
            'items' => [
                ['description' => 'Line 1', 'quantity' => 2, 'unit_price_cents' => 500],
                ['description' => 'Line 2', 'quantity' => 1, 'unit_price_cents' => 1000],
            ],
        ]);

        $response->assertRedirect();

        $invoice = Invoice::where('title', 'Invoice 1')->first();
        $this->assertNotNull($invoice, 'Invoice was not created');
        $this->assertEquals(2000, $invoice->subtotal_cents);
        $this->assertEquals(2000, $invoice->total_cents);
    }

    public function test_invoice_creation_is_atomic_on_item_error(): void
    {
        $email = strtolower('owner+'.Str::random(6).'@example.com');

        $this->post('/register', [
            'name' => 'Owner',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');

        $user = User::where('email', $email)->firstOrFail();

        $response = $this->actingAs($user)->postJson('/invoices', [
            'title' => 'Bad Invoice',
            'items' => [
                ['description' => 'Line 1', 'quantity' => 0, 'unit_price_cents' => 500],
            ],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('items.0.quantity');
        $this->assertDatabaseMissing('invoices', ['title' => 'Bad Invoice']);
    }

    public function test_user_cannot_access_other_organization_invoice(): void
    {
        // User A creates invoice
        $emailA = strtolower('usera+'.Str::random(6).'@example.com');
        $this->post('/register', [
            'name' => 'User A',
            'email' => $emailA,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');
        $userA = User::where('email', $emailA)->firstOrFail();
        $this->actingAs($userA)->post('/invoices', [
            'title' => 'A Invoice',
            'items' => [
                ['description' => 'Line 1', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ])->assertRedirect();
        $invoiceA = Invoice::where('title', 'A Invoice')->firstOrFail();

        Auth::logout();

        // User B attempts access
        $emailB = strtolower('userb+'.Str::random(6).'@example.com');
        $this->post('/register', [
            'name' => 'User B',
            'email' => $emailB,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');
        $userB = User::where('email', $emailB)->firstOrFail();

        $userB->forceFill(['current_organization_id' => $invoiceA->organization_id])->save();

        $this->actingAs($userB)->get(route('invoices.show', $invoiceA))->assertForbidden();
        $this->actingAs($userB)->get(route('invoices.edit', $invoiceA))->assertForbidden();
        $this->actingAs($userB)->delete(route('invoices.destroy', $invoiceA))->assertForbidden();
    }

    public function test_cannot_create_invoice_with_foreign_client_or_project(): void
    {
        // User A with client/project
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
        $this->actingAs($userA)->post('/projects', ['name' => 'Project A', 'status' => 'draft'])->assertRedirect();
        $projectA = Project::where('name', 'Project A')->firstOrFail();

        Auth::logout();

        // User B attempts to use A's client/project
        $emailB = strtolower('userb+'.Str::random(6).'@example.com');
        $this->post('/register', [
            'name' => 'User B',
            'email' => $emailB,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');
        $userB = User::where('email', $emailB)->firstOrFail();

        $response = $this->actingAs($userB)->postJson('/invoices', [
            'title' => 'Project B Invoice',
            'client_id' => $clientA->id,
            'project_id' => $projectA->id,
            'items' => [
                ['description' => 'Line 1', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['client_id', 'project_id']);
        $this->assertDatabaseMissing('invoices', ['title' => 'Project B Invoice']);
    }

    public function test_update_invoice_recalculates_totals(): void
    {
        $email = strtolower('owner+'.Str::random(6).'@example.com');

        $this->post('/register', [
            'name' => 'Owner',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');

        $user = User::where('email', $email)->firstOrFail();

        $this->actingAs($user)->post('/invoices', [
            'title' => 'Invoice Update',
            'items' => [
                ['description' => 'Line 1', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ])->assertRedirect();

        $invoice = Invoice::where('title', 'Invoice Update')->firstOrFail();

        $response = $this->actingAs($user)->put('/invoices/'.$invoice->id, [
            'title' => 'Invoice Update',
            'items' => [
                ['description' => 'Line 1', 'quantity' => 3, 'unit_price_cents' => 200],
                ['description' => 'Line 2', 'quantity' => 1, 'unit_price_cents' => 500],
            ],
        ]);

        $response->assertRedirect();

        $invoice->refresh();
        $this->assertEquals(1100, $invoice->subtotal_cents);
        $this->assertEquals(1100, $invoice->total_cents);
        $this->assertCount(2, $invoice->items);
    }
}
