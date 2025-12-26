<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Support\Tenancy\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $tenantId = Tenant::id();

        // Primary Metrics
        $clients = Client::count();
        $projects = Project::count();
        $invoices = Invoice::count();

        // Financial Metrics
        $totalBilled = Invoice::sum('total_cents');
        $totalCollected = Payment::sum('amount_cents');

        $receivable = Invoice::whereIn('status', ['draft', 'sent', 'overdue'])
            ->selectRaw('SUM(total_cents - amount_paid_cents) as balance')
            ->value('balance') ?? 0;

        $overdue = Invoice::whereIn('status', ['sent', 'overdue'])
            ->whereNull('paid_at')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->count();

        $collectionRate = $totalBilled > 0 ? round(($totalCollected / $totalBilled) * 100, 1) : 0;

        // MRR (Monthly Recurring Revenue) - based on paid payments in last 30 days
        $mrrCents = (int) Payment::where('paid_at', '>=', now()->subDays(30))
            ->sum('amount_cents');

        // ARR (Annual Recurring Revenue) - MRR * 12
        $arrCents = $mrrCents * 12;

        // Health Indicator
        $health = $this->getHealthStatus();

        $recentInvoices = Invoice::with('client')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $stripeKeys = config('stripe.secret_keys', []);
        $stripeMode = config('stripe.mode', 'test');
        $stripeReady = is_array($stripeKeys) && !empty($stripeKeys[$stripeMode]);

        return view('dashboard', [
            'tenantId' => $tenantId,
            'metrics' => [
                'clients' => $clients,
                'projects' => $projects,
                'invoices' => $invoices,
                'receivable_cents' => (int) $receivable,
                'overdue' => $overdue,
                'mrr_cents' => $mrrCents,
                'arr_cents' => $arrCents,
                'collection_rate' => $collectionRate,
                'health' => [
                    'queue' => $health['queue'] === 'ok',
                    'queue_backlog' => $health['queue_backlog'] ?? 0,
                    'failed_jobs' => $health['failed_jobs'] ?? 0,
                    'stripe' => $health['stripe'] === 'ok',
                ],
            ],
            'health' => $health,
            'recentInvoices' => $recentInvoices,
        ]);
    }

    protected function getHealthStatus(): array
    {
        $status = [
            'overall' => 'ok',
            'database' => 'ok',
            'queue' => 'ok',
            'stripe' => 'ok',
            'queue_backlog' => 0,
            'failed_jobs' => 0,
            'issues' => [],
        ];

        // Check database
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $status['database'] = 'error';
            $status['issues'][] = 'Database connection failed';
        }

        // Check queue
        try {
            $status['queue_backlog'] = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
            $status['failed_jobs'] = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

            if ($status['failed_jobs'] > 0) {
                $status['queue'] = 'error';
                $status['issues'][] = "{$status['failed_jobs']} failed jobs in queue";
            } elseif ($status['queue_backlog'] > 50) {
                $status['queue'] = 'warning';
                $status['issues'][] = "Large queue backlog ({$status['queue_backlog']})";
            }
        } catch (\Exception $e) {
            // Queue might use different driver
        }

        // Check Stripe configuration
        $stripeKey = config('stripe.secret');
        if (empty($stripeKey)) {
            $status['stripe'] = 'warning';
            $status['issues'][] = 'Stripe secret key not configured';
        } elseif (app()->environment('production') && str_starts_with($stripeKey, 'sk_test_')) {
            $status['stripe'] = 'error';
            $status['issues'][] = 'Test Stripe keys are being used in production';
        }

        // Determine overall status
        if ($status['database'] === 'error' || $status['stripe'] === 'error' || $status['queue'] === 'error') {
            $status['overall'] = 'error';
        } elseif ($status['queue'] === 'warning' || $status['stripe'] === 'warning') {
            $status['overall'] = 'warning';
        }

        return $status;
    }
}
