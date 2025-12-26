<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Mail\OverdueInvoiceMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'app:mark-overdue {--notify : Send email notifications}';

    protected $description = 'Mark sent invoices past their due date as overdue';

    public function handle(): int
    {
        $count = 0;
        $notified = 0;

        $invoices = Invoice::where('status', 'sent')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->get();

        foreach ($invoices as $invoice) {
            $invoice->update(['status' => 'overdue']);
            $count++;

            Log::info('invoice.marked_overdue', [
                'invoice_id' => $invoice->id,
                'organization_id' => $invoice->organization_id,
                'due_date' => $invoice->due_date->toDateString(),
            ]);

            // Send notification if flag is set and we have a recipient
            if ($this->option('notify')) {
                $recipient = $invoice->client?->email
                    ?? $invoice->organization?->billing_email
                    ?? $invoice->organization?->owner?->email;

                if ($recipient && class_exists(OverdueInvoiceMail::class)) {
                    try {
                        Mail::to($recipient)->queue(new OverdueInvoiceMail($invoice));
                        $notified++;
                    } catch (\Exception $e) {
                        Log::warning('invoice.overdue_notification_failed', [
                            'invoice_id' => $invoice->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        $this->info("Marked {$count} invoice(s) as overdue.");

        if ($this->option('notify')) {
            $this->info("Sent {$notified} overdue notification(s).");
        }

        return Command::SUCCESS;
    }
}
