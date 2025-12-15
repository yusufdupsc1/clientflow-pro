<?php

namespace Tests\Feature;

use App\Actions\Billing\UpdateInvoice;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Support\Tenancy\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_posting_payment_updates_amount_paid_and_status(): void
    {
        $email = strtolower('payer+'.Str::random(6).'@example.com');

        $this->post('/register', [
            'name' => 'Payer',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');

        $user = User::where('email', $email)->firstOrFail();

        $this->actingAs($user)->post('/invoices', [
            'title' => 'Payable Invoice',
            'items' => [
                ['description' => 'Line 1', 'quantity' => 1, 'unit_price_cents' => 2000],
            ],
        ])->assertRedirect();

        $invoice = Invoice::where('title', 'Payable Invoice')->firstOrFail();
        $this->assertEquals(0, $invoice->amount_paid_cents);
        $this->assertEquals('draft', $invoice->status);

        Tenant::set($invoice->organization_id);

        $invoice = app(UpdateInvoice::class)->handle($invoice, [
            'status' => 'sent',
            'title' => $invoice->title,
            'items' => [
                ['description' => 'Line 1', 'quantity' => 1, 'unit_price_cents' => 2000],
            ],
        ]);

        $this->actingAs($user)->post('/invoices/'.$invoice->id.'/payments', [
            'amount_cents' => 500,
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertEquals(500, $invoice->amount_paid_cents);
        $this->assertEquals('sent', $invoice->status);

        $this->actingAs($user)->post('/invoices/'.$invoice->id.'/payments', [
            'amount_cents' => 1500,
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertEquals(2000, $invoice->amount_paid_cents);
        $this->assertEquals('paid', $invoice->status);
    }

    public function test_cross_org_cannot_post_payment(): void
    {
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
        Tenant::set($invoiceA->organization_id);

        $invoiceA = app(UpdateInvoice::class)->handle($invoiceA, [
            'status' => 'sent',
            'title' => $invoiceA->title,
            'items' => [
                ['description' => 'Line 1', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ]);

        Auth::logout();

        $emailB = strtolower('userb+'.Str::random(6).'@example.com');
        $this->post('/register', [
            'name' => 'User B',
            'email' => $emailB,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');
        $userB = User::where('email', $emailB)->firstOrFail();

        $userB->forceFill(['current_organization_id' => $invoiceA->organization_id])->save();

        $this->actingAs($userB)
            ->post('/invoices/'.$invoiceA->id.'/payments', [
                'amount_cents' => 100,
            ])
            ->assertForbidden();
    }

    public function test_idempotent_payments_do_not_double_charge(): void
    {
        $email = strtolower('payer+'.Str::random(6).'@example.com');

        $this->post('/register', [
            'name' => 'Payer',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');

        $user = User::where('email', $email)->firstOrFail();

        $this->actingAs($user)->post('/invoices', [
            'title' => 'Idempotent Invoice',
            'items' => [
                ['description' => 'Line 1', 'quantity' => 1, 'unit_price_cents' => 1000],
            ],
        ])->assertRedirect();

        $invoice = Invoice::where('title', 'Idempotent Invoice')->firstOrFail();
        Tenant::set($invoice->organization_id);

        $invoice = app(UpdateInvoice::class)->handle($invoice, [
            'status' => 'sent',
            'title' => $invoice->title,
            'items' => [
                ['description' => 'Line 1', 'quantity' => 1, 'unit_price_cents' => 1000],
            ],
        ]);

        $payload = [
            'amount_cents' => 1000,
            'provider' => 'stripe',
            'external_id' => 'pi_'.Str::random(8),
        ];

        $this->actingAs($user)->post('/invoices/'.$invoice->id.'/payments', $payload)->assertRedirect();
        $this->actingAs($user)->post('/invoices/'.$invoice->id.'/payments', $payload)->assertRedirect();

        $invoice->refresh();
        $this->assertEquals(1000, $invoice->amount_paid_cents);
        $this->assertEquals(1, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_prevent_overpay_returns_422(): void
    {
        $email = strtolower('payer+'.Str::random(6).'@example.com');

        $this->post('/register', [
            'name' => 'Payer',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');

        $user = User::where('email', $email)->firstOrFail();

        $this->actingAs($user)->post('/invoices', [
            'title' => 'Overpay Invoice',
            'items' => [
                ['description' => 'Line 1', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ])->assertRedirect();

        $invoice = Invoice::where('title', 'Overpay Invoice')->firstOrFail();
        Tenant::set($invoice->organization_id);

        $invoice = app(UpdateInvoice::class)->handle($invoice, [
            'status' => 'sent',
            'title' => $invoice->title,
            'items' => [
                ['description' => 'Line 1', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ]);

        $response = $this->actingAs($user)->postJson('/invoices/'.$invoice->id.'/payments', [
            'amount_cents' => 200,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('amount_cents');
        $invoice->refresh();
        $this->assertEquals(0, $invoice->amount_paid_cents);
    }
}
