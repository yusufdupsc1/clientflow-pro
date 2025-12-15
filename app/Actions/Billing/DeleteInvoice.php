<?php

namespace App\Actions\Billing;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use App\Support\Activity\ActivityLogger;

class DeleteInvoice
{
    public function handle(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            ActivityLogger::log($invoice, 'invoices.deleted');
            $invoice->items()->delete();
            $invoice->delete();
        });
    }
}
