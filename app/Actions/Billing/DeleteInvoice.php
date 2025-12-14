<?php

namespace App\Actions\Billing;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class DeleteInvoice
{
    public function handle(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            $invoice->items()->delete();
            $invoice->delete();
        });
    }
}
