<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Actions\Billing\UpdateInvoice;
use App\Mail\InvoiceSentMail;

class InvoiceSendTest extends TestCase
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

    private function createInvoice(User $user, Organization $org): Invoice
    {
        Tenant::set($org->id);

        $this->actingAs($user)->post('/invoices', [
            'title' => 'Sendable',
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 500],
            ],
        ])->assertRedirect();

        return Invoice::where('title', 'Sendable')->firstOrFail();
    }

    public function test_send_transitions_to_sent_and_logs_and_emails(): void
    {
        [$user, $org] = $this->createUserWithOrganization('sender');
        $invoice = $this->createInvoice($user, $org);

        Mail::fake();

        $response = $this->actingAs($user)->post('/invoices/'.$invoice->id.'/send');
        $response->assertRedirect();

        $invoice->refresh();
        $this->assertEquals('sent', $invoice->status);
        $this->assertNotNull($invoice->sent_at);

        Mail::assertQueued(InvoiceSentMail::class, function ($mail) use ($invoice) {
            return $mail->invoice->is($invoice);
        });

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'invoices.sent',
            'organization_id' => $org->id,
            'subject_type' => $invoice->getMorphClass(),
            'subject_id' => $invoice->id,
        ]);
    }

    public function test_cannot_send_paid_invoice(): void
    {
        [$user, $org] = $this->createUserWithOrganization('paid');
        $invoice = $this->createInvoice($user, $org);
        Tenant::set($org->id);

        // Mark as paid via status helper
        $invoice = app(UpdateInvoice::class)->handle($invoice, [
            'status' => 'sent',
            'title' => $invoice->title,
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 500],
            ],
        ]);

        // simulate full pay
        $invoice->forceFill(['status' => 'paid', 'paid_at' => now()])->save();

        $this->actingAs($user)
            ->post('/invoices/'.$invoice->id.'/send')
            ->assertStatus(422);
    }

    public function test_pdf_endpoint_scoped_to_tenant(): void
    {
        [$user, $org] = $this->createUserWithOrganization('pdfa');
        $invoice = $this->createInvoice($user, $org);

        $this->actingAs($user)
            ->get('/invoices/'.$invoice->id.'/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        // Cross-org user forbidden
        [$userB, $orgB] = $this->createUserWithOrganization('pdfb');
        $userB->forceFill(['current_organization_id' => $orgB->id])->save();

        $this->actingAs($userB)
            ->get('/invoices/'.$invoice->id.'/pdf')
            ->assertForbidden();
    }
}
