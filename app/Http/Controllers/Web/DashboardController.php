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

        $clients = Client::count();
        $projects = Project::count();
        $invoices = Invoice::count();

        $receivable = Invoice::whereIn('status', ['draft', 'sent', 'overdue'])
            ->selectRaw('SUM(total_cents - amount_paid_cents) as balance')
            ->value('balance') ?? 0;

        $overdue = Invoice::whereIn('status', ['sent', 'overdue'])
            ->whereNull('paid_at')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->count();

        // Collection Rate: paid invoices / total sent invoices (last 90 days)
        $sentLast90Days = Invoice::whereIn('status', ['sent', 'paid', 'overdue'])
            ->where('sent_at', '>=', now()->subDays(90))
            ->count();

        $paidLast90Days = Invoice::where('status', 'paid')
            ->where('sent_at', '>=', now()->subDays(90))
            ->count();

        $collectionRate = $sentLast90Days > 0
            ? round(($paidLast90Days / $sentLast90Days) * 100, 1)
            : 0;

        // MRR (Monthly Recurring Revenue) - based on paid invoices in last 30 days
        $mrrCents = Payment::where('paid_at', '>=', now()->subDays(30))
            ->sum('amount_cents');

        // ARR (Annual Recurring Revenue) - MRR * 12
        $arrCents = $mrrCents * 12;

        // Health Indicator
        $health = $this->getHealthStatus();

        $recentInvoices = Invoice::with('client')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $mrr = Payment::where('created_at', '>=', now()->subMonth())->sum('amount_cents');
        $arr = $mrr * 12;

        $totalBilled = Invoice::sum('total_cents');
        $totalCollected = Payment::sum('amount_cents');
        $collectionRate = $totalBilled > 0 ? round(($totalCollected / $totalBilled) * 100, 1) : 0;

        $queueBacklog = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
        $failedJobs = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
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
                'mrr_cents' => (int) $mrrCents,
                'arr_cents' => (int) $arrCents,
                'collection_rate' => $collectionRate,
                'health' => [
                    'queue' => $queueBacklog < 10 && $failedJobs === 0,
                    'queue_backlog' => $queueBacklog,
                    'failed_jobs' => $failedJobs,
                    'stripe' => $stripeReady,
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
            'issues' => [],
        ];

        // Check database
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $status['database'] = 'error';
            $status['issues'][] = 'Database connection failed';
        }

        // Check queue (by checking if jobs table exists and is accessible)
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            if ($failedJobs > 10) {
                $status['queue'] = 'warning';
                $status['issues'][] = "{$failedJobs} failed jobs in queue";
            }
        } catch (\Exception $e) {
            // Queue might use different driver, that's ok
        }

        // Check Stripe configuration
        $stripeKey = config('stripe.secret');
        if (empty($stripeKey)) {
            $status['stripe'] = 'warning';
            $status['issues'][] = 'Stripe not configured';
        } elseif (app()->environment('production') && str_starts_with($stripeKey, 'sk_test_')) {
            $status['stripe'] = 'error';
            $status['issues'][] = 'Test Stripe keys in production!';
        }

        // Determine overall status
        if ($status['database'] === 'error' || $status['stripe'] === 'error') {
            $status['overall'] = 'error';
        } elseif ($status['queue'] === 'warning' || $status['stripe'] === 'warning') {
            $status['overall'] = 'warning';
        }

        return $status;
    }
}
