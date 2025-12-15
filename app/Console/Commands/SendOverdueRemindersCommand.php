<?php

namespace App\Console\Commands;

use App\Mail\OverdueInvoiceReminderMail;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Support\Activity\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendOverdueRemindersCommand extends Command
{
    protected $signature = 'invoices:send-overdue-reminders';

    protected $description = 'Send overdue reminders for sent invoices';

    public function handle(): int
    {
        $now = now()->startOfDay();

        $invoices = Invoice::with(['client', 'organization.owner'])
            ->where('status', 'sent')
            ->whereNull('paid_at')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $now)
            ->get();

        $sent = 0;

        foreach ($invoices as $invoice) {
            if ($this->alreadyReminded($invoice)) {
                continue;
            }

            $recipient = $invoice->client?->email
                ?? optional($invoice->organization?->owner)->email;

            if (! $recipient) {
                continue;
            }

            $before = $this->snapshot($invoice);

            Mail::to($recipient)->queue(new OverdueInvoiceReminderMail($invoice));

            ActivityLogger::log($invoice, 'invoices.overdue_reminder', $invoice->organization_id, [
                'before' => $before,
                'after' => $this->snapshot($invoice),
            ]);

            $sent++;
        }

        $this->info("Overdue reminders sent: {$sent}");

        return self::SUCCESS;
    }

    protected function alreadyReminded(Invoice $invoice): bool
    {
        return ActivityLog::where('subject_type', $invoice->getMorphClass())
            ->where('subject_id', $invoice->id)
            ->where('action', 'invoices.overdue_reminder')
            ->exists();
    }

    protected function snapshot(Invoice $invoice): array
    {
        return [
            'status' => $invoice->status,
            'sent_at' => optional($invoice->sent_at)->toIso8601String(),
            'paid_at' => optional($invoice->paid_at)->toIso8601String(),
            'amount_paid_cents' => $invoice->amount_paid_cents,
            'total_cents' => $invoice->total_cents,
            'due_date' => optional($invoice->due_date)->toDateString(),
        ];
    }
}
