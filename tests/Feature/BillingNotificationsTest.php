<?php

namespace Tests\Feature;

use App\Actions\Billing\CreateInvoice;
use App\Actions\Billing\PostPayment;
use App\Mail\OverdueInvoiceReminderMail;
use App\Mail\PaymentReceiptMail;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillingNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_receipt_email_is_sent_on_payment(): void
    {
        Mail::fake();
        [$user, $org] = $this->createUserAndOrg('receipt');
        Tenant::set($org->id);

        $client = Client::create([
            'organization_id' => $org->id,
            'name' => 'Receipt Client',
            'email' => 'receipt@example.com',
        ]);

        $invoice = app(CreateInvoice::class)->handle([
            'title' => 'Receipt Invoice',
            'client_id' => $client->id,
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 1000],
            ],
        ]);

        $invoice->forceFill(['status' => 'sent', 'sent_at' => now()])->save();

        app(PostPayment::class)->handle($invoice, [
            'amount_cents' => 500,
            'method' => 'card',
            'reference' => 'ref-123',
        ]);

        Mail::assertQueued(PaymentReceiptMail::class, function (PaymentReceiptMail $mail) use ($invoice, $client) {
            return $mail->invoice->is($invoice->fresh())
                && $mail->hasTo($client->email);
        });
    }

    public function test_overdue_reminder_sends_once_and_logs(): void
    {
        Mail::fake();
        Carbon::setTestNow('2025-01-10 10:00:00');

        [$user, $org] = $this->createUserAndOrg('overdue');
        Tenant::set($org->id);

        $client = Client::create([
            'organization_id' => $org->id,
            'name' => 'Overdue Client',
            'email' => 'overdue@example.com',
        ]);

        $invoice = app(CreateInvoice::class)->handle([
            'title' => 'Overdue Invoice',
            'client_id' => $client->id,
            'due_date' => Carbon::now()->subDays(3)->toDateString(),
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 1000],
            ],
        ]);

        $invoice->forceFill([
            'status' => 'sent',
            'sent_at' => Carbon::now()->subDays(4),
            'paid_at' => null,
        ])->save();

        // Not overdue invoice should be ignored
        $futureInvoice = app(CreateInvoice::class)->handle([
            'title' => 'Future Invoice',
            'client_id' => $client->id,
            'due_date' => Carbon::now()->addDays(5)->toDateString(),
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price_cents' => 500],
            ],
        ]);
        $futureInvoice->forceFill(['status' => 'sent', 'sent_at' => Carbon::now()])->save();

        Artisan::call('invoices:send-overdue-reminders');

        Mail::assertQueued(OverdueInvoiceReminderMail::class, function (OverdueInvoiceReminderMail $mail) use ($invoice, $client) {
            return $mail->invoice->is($invoice->fresh())
                && $mail->hasTo($client->email);
        });

        $this->assertEquals(
            1,
            ActivityLog::where('action', 'invoices.overdue_reminder')
                ->where('subject_id', $invoice->id)
                ->count()
        );

        // No duplicate reminders
        Artisan::call('invoices:send-overdue-reminders');
        $this->assertEquals(
            1,
            ActivityLog::where('action', 'invoices.overdue_reminder')
                ->where('subject_id', $invoice->id)
                ->count()
        );

        Mail::assertQueued(OverdueInvoiceReminderMail::class, 1);
    }

    private function createUserAndOrg(string $label): array
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
}
