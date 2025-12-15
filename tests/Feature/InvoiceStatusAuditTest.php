<?php

namespace Tests\Feature;

use App\Actions\Billing\UpdateInvoice;
use App\Mail\InvoiceSentMail;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceStatusAuditTest extends TestCase
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

    private function createInvoice(User $user, Organization $org, string $title = 'Test Invoice'): Invoice
    {
        Tenant::set($org->id);

        $this->actingAs($user)->post('/invoices', [
            'title' => $title,
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 500],
            ],
        ])->assertRedirect();

        return Invoice::where('title', $title)->firstOrFail();
    }

    public function test_resend_draft_and_sent_logs_before_after(): void
    {
        [$user, $org] = $this->createUserWithOrganization('resend');
        Mail::fake();

        $invoice = $this->createInvoice($user, $org, 'Draft Invoice');

        $this->actingAs($user)->post('/invoices/'.$invoice->id.'/send')->assertRedirect();
        $invoice->refresh();
        $this->assertEquals('sent', $invoice->status);

        $log = ActivityLog::where('action', 'invoices.sent')->latest()->first();
        $this->assertNotNull($log);
        $this->assertEquals('draft', $log->metadata['before']['status'] ?? null);
        $this->assertEquals('sent', $log->metadata['after']['status'] ?? null);

        // Resend while already sent
        $this->actingAs($user)->post('/invoices/'.$invoice->id.'/send')->assertRedirect();

        Mail::assertQueued(InvoiceSentMail::class);
        $this->assertEquals(2, ActivityLog::where('action', 'invoices.sent')->count());
    }

    public function test_paid_or_void_cannot_be_resent(): void
    {
        [$user, $org] = $this->createUserWithOrganization('blocked');
        $invoice = $this->createInvoice($user, $org, 'Blocked Invoice');
        Tenant::set($org->id);

        $invoice = app(UpdateInvoice::class)->handle($invoice, [
            'status' => 'sent',
            'title' => $invoice->title,
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 500],
            ],
        ]);

        $invoice->forceFill(['status' => 'paid', 'paid_at' => now()])->save();

        $this->actingAs($user)->post('/invoices/'.$invoice->id.'/send')->assertStatus(422);
        $this->assertEquals(0, ActivityLog::where('action', 'invoices.sent')->count());

        $invoice->forceFill(['status' => 'void', 'paid_at' => null])->save();
        $this->actingAs($user)->post('/invoices/'.$invoice->id.'/send')->assertStatus(422);
        $this->assertEquals(0, ActivityLog::where('action', 'invoices.sent')->count());
    }

    public function test_void_sent_invoice_logs_before_after(): void
    {
        [$user, $org] = $this->createUserWithOrganization('void');
        $invoice = $this->createInvoice($user, $org, 'Void Invoice');
        Tenant::set($org->id);

        $invoice = app(UpdateInvoice::class)->handle($invoice, [
            'status' => 'sent',
            'title' => $invoice->title,
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 500],
            ],
        ]);

        $this->actingAs($user)->post('/invoices/'.$invoice->id.'/void')->assertRedirect();

        $invoice->refresh();
        $this->assertEquals('void', $invoice->status);
        $this->assertNull($invoice->paid_at);

        $log = ActivityLog::where('action', 'invoices.voided')->latest()->first();
        $this->assertEquals('sent', $log->metadata['before']['status'] ?? null);
        $this->assertEquals('void', $log->metadata['after']['status'] ?? null);
    }
}
