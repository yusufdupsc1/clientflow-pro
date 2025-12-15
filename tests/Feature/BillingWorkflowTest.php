<?php

namespace Tests\Feature;

use App\Actions\Billing\CreateInvoice;
use App\Actions\Billing\UpdateInvoice;
use App\Actions\Billing\PostPayment;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithOrganization(string $label = 'user'): array
    {
        $user = User::factory()->create([
            'name' => ucfirst($label),
            'email' => strtolower($label.'+'.Str::random(6).'@example.com'),
            'email_verified_at' => now(),
        ]);

        $org = Organization::create([
            'name' => ucfirst($label).' Org',
            'slug' => Str::slug($label.'-'.Str::random(6)),
            'owner_user_id' => $user->id,
        ]);

        $org->users()->attach($user->id, ['role' => 'owner']);
        $user->forceFill(['current_organization_id' => $org->id])->save();

        return [$user, $org];
    }

    private function createInvoiceForUser(User $user, Organization $organization): Invoice
    {
        Tenant::set($organization->id);

        return app(CreateInvoice::class)->handle([
            'title' => 'Test Invoice',
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 1000],
            ],
        ]);
    }

    public function test_invoice_numbers_are_unique_per_organization(): void
    {
        [$userA, $orgA] = $this->createUserWithOrganization('userA');
        $invoiceA1 = $this->createInvoiceForUser($userA, $orgA);
        $invoiceA2 = $this->createInvoiceForUser($userA, $orgA);

        $this->assertEquals(1, $invoiceA1->invoice_number);
        $this->assertEquals(2, $invoiceA2->invoice_number);

        [$userB, $orgB] = $this->createUserWithOrganization('userB');
        $invoiceB1 = $this->createInvoiceForUser($userB, $orgB);

        $this->assertEquals(1, $invoiceB1->invoice_number);
    }

    public function test_status_transitions_to_sent_and_paid(): void
    {
        [$user, $organization] = $this->createUserWithOrganization('status');
        Tenant::set($organization->id);

        $invoice = $this->createInvoiceForUser($user, $organization);
        $this->assertEquals('draft', $invoice->status);

        $invoice = app(UpdateInvoice::class)->handle($invoice, [
            'status' => 'sent',
            'title' => $invoice->title,
            'notes' => $invoice->notes,
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 1000],
            ],
        ]);

        $this->assertEquals('sent', $invoice->status);
        $this->assertNotNull($invoice->sent_at);
        $this->assertNull($invoice->paid_at);

        $payment = app(PostPayment::class)->handle($invoice, [
            'amount_cents' => $invoice->total_cents,
            'method' => 'card',
            'reference' => 'txn-1',
        ]);

        $invoice->refresh();

        $this->assertEquals('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertEquals($invoice->total_cents, $invoice->amount_paid_cents);
        $this->assertEquals($invoice->organization_id, $payment->organization_id);
    }

    public function test_payment_requires_sent_status(): void
    {
        [$user, $organization] = $this->createUserWithOrganization('require-sent');
        Tenant::set($organization->id);

        $invoice = $this->createInvoiceForUser($user, $organization);

        $this->expectException(ValidationException::class);

        app(PostPayment::class)->handle($invoice, [
            'amount_cents' => 500,
        ]);
    }

    public function test_cannot_modify_paid_invoice_items(): void
    {
        [$user, $organization] = $this->createUserWithOrganization('locked');
        Tenant::set($organization->id);

        $invoice = $this->createInvoiceForUser($user, $organization);

        // Send
        $invoice = app(UpdateInvoice::class)->handle($invoice, [
            'status' => 'sent',
            'title' => $invoice->title,
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 500],
            ],
        ]);

        // Pay fully
        app(PostPayment::class)->handle($invoice, [
            'amount_cents' => $invoice->total_cents,
            'method' => 'cash',
        ]);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);

        $this->expectException(ValidationException::class);

        app(UpdateInvoice::class)->handle($invoice, [
            'status' => 'sent',
            'title' => $invoice->title,
            'items' => [
                ['description' => 'New Line', 'quantity' => 1, 'unit_price_cents' => 100],
            ],
        ]);
    }
}
