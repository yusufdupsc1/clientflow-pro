<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Services\StripeService;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Tests\TestCase;

class TestSession extends Session
{
    public $url;
    public $id;
}

class StripeVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_create_invoice_and_generate_payment_link()
    {
        // 1. Setup Admin User & Organization
        $user = User::factory()->create([
            'email' => 'admin@clientflow.pro',
            'password' => bcrypt('password123'),
        ]);

        $org = Organization::create([
            'name' => 'Verification Corp',
            'slug' => 'verif-corp-' . Str::random(5),
            'owner_user_id' => $user->id,
            'stripe_mode' => 'test',
            'stripe_test_secret' => 'sk_test_mock',
            'stripe_test_publishable_key' => 'pk_test_mock',
        ]);

        Permissions::ensureDefaultRolesForOrganization($org->id);
        Permissions::syncUserRole($user, $org->id, 'owner');
        $user->organizations()->attach($org->id, ['role' => 'owner']);
        $user->forceFill(['current_organization_id' => $org->id])->save();

        // 2. Login
        $response = $this->post('/login', [
            'email' => 'admin@clientflow.pro',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        // 3. Create Client
        \App\Support\Tenancy\Tenant::set($org->id);
        $client = Client::forceCreate([
            'organization_id' => $org->id,
            'name' => 'Stripe Tester',
            'email' => 'test@example.com',
        ]);

        // 4. Create Invoice via Controller (Simulating UI form submission)
        $invoiceData = [
            'client_id' => $client->id,
            'title' => 'Stripe Test Invoice',
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'currency' => 'USD',
            'items' => [
                [
                    'description' => 'Service',
                    'quantity' => 1,
                    'unit_price_cents' => 2000, // $20.00
                ]
            ],
        ];

        $response = $this->actingAs($user)->post(route('invoices.store'), $invoiceData);
        $response->assertRedirect();

        $invoice = Invoice::where('title', 'Stripe Test Invoice')->firstOrFail();
        $this->assertEquals(2000, $invoice->total_cents);
        $this->assertNotNull($invoice->public_hash);

        // 5. Simulate Visiting Public Payment Page
        $response = $this->get(route('portal.show', $invoice->public_hash));
        $response->assertStatus(200);
        $response->assertSeeText('Stripe Test Invoice');
        $response->assertSeeText('USD');
        $response->assertSeeText('20.00');

        // 6. Verify Checkout Redirection (Mocking Stripe)
        $this->mock(StripeService::class, function ($mock) use ($invoice) {
            $session = new TestSession();
            $session->url = 'https://checkout.stripe.com/mock-session';
            $session->id = 'cs_test_123';

            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn($session);

            // Allow other methods
            $mock->shouldReceive('useOrganization')->andReturnNull();
        });

        $response = $this->post(route('portal.checkout', $invoice->public_hash));
        $response->assertRedirect('https://checkout.stripe.com/mock-session');
    }
}
