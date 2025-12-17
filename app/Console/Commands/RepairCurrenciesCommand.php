<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Support\Billing\Currency;
use Illuminate\Console\Command;

class RepairCurrenciesCommand extends Command
{
    protected $signature = 'app:repair-currencies {--dry-run : Show what would change without writing}';

    protected $description = 'Normalize invalid invoice/payment currency codes (ISO 4217) to the organization default.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $fallbackCurrency = strtoupper(config('stripe.default_currency', 'USD'));
        $updatedInvoices = 0;
        $updatedPayments = 0;

        Organization::query()
            ->select(['id', 'default_currency'])
            ->orderBy('id')
            ->chunkById(200, function ($organizations) use ($dryRun, $fallbackCurrency, &$updatedInvoices, &$updatedPayments) {
                foreach ($organizations as $org) {
                    $orgCurrency = Currency::normalize($org->default_currency, $fallbackCurrency);

                    $updatedInvoices += $this->repairInvoicesForOrganization((int) $org->id, $orgCurrency, $dryRun);
                    $updatedPayments += $this->repairPaymentsForOrganization((int) $org->id, $orgCurrency, $dryRun);
                }
            });

        $this->info("Invoices updated: {$updatedInvoices}");
        $this->info("Payments updated: {$updatedPayments}");

        if ($dryRun) {
            $this->comment('Dry run: no rows were modified.');
        }

        return self::SUCCESS;
    }

    protected function repairInvoicesForOrganization(int $organizationId, string $currency, bool $dryRun): int
    {
        $invoices = Invoice::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->select(['id', 'currency'])
            ->get();

        $updated = 0;

        foreach ($invoices as $invoice) {
            $normalized = Currency::normalize($invoice->currency, $currency);

            if ($invoice->currency === $normalized) {
                continue;
            }

            $updated++;

            if (! $dryRun) {
                Invoice::withoutGlobalScopes()
                    ->whereKey($invoice->id)
                    ->update(['currency' => $normalized]);
            }
        }

        return $updated;
    }

    protected function repairPaymentsForOrganization(int $organizationId, string $currency, bool $dryRun): int
    {
        $payments = Payment::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->select(['id', 'currency'])
            ->get();

        $updated = 0;

        foreach ($payments as $payment) {
            $normalized = Currency::normalize($payment->currency, $currency);

            if ($payment->currency === $normalized) {
                continue;
            }

            $updated++;

            if (! $dryRun) {
                Payment::withoutGlobalScopes()
                    ->whereKey($payment->id)
                    ->update(['currency' => $normalized]);
            }
        }

        return $updated;
    }
}

