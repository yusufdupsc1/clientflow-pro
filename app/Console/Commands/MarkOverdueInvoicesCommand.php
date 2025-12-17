<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Support\Activity\ActivityLogger;
use Illuminate\Console\Command;

class MarkOverdueInvoicesCommand extends Command
{
    protected $signature = 'invoices:mark-overdue';

    protected $description = 'Mark sent invoices past due date as overdue';

    public function handle(): int
    {
        $today = now()->startOfDay();

        $invoices = Invoice::query()
            ->whereIn('status', ['sent'])
            ->whereNull('paid_at')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->get();

        $updated = 0;

        foreach ($invoices as $invoice) {
            $before = $this->snapshot($invoice);
            $invoice->status = 'overdue';
            $invoice->save();

            ActivityLogger::log($invoice, 'invoices.marked_overdue', $invoice->organization_id, [
                'before' => $before,
                'after' => $this->snapshot($invoice),
            ]);

            $updated++;
        }

        $this->info("Invoices marked overdue: {$updated}");

        return self::SUCCESS;
    }

    protected function snapshot(Invoice $invoice): array
    {
        return [
            'status' => $invoice->status,
            'due_date' => optional($invoice->due_date)->toDateString(),
            'paid_at' => optional($invoice->paid_at)->toIso8601String(),
            'amount_paid_cents' => $invoice->amount_paid_cents,
            'total_cents' => $invoice->total_cents,
        ];
    }
}
