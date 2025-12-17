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
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $tenantId = Tenant::id();

        $clients = Client::count();
        $projects = Project::count();
        $invoices = Invoice::count();

        $receivable = Invoice::whereIn('status', ['draft', 'sent'])
            ->selectRaw('SUM(total_cents - amount_paid_cents) as balance')
            ->value('balance') ?? 0;

        $overdue = Invoice::where('status', 'sent')
            ->whereNull('paid_at')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->count();

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
        $stripeReady = is_array($stripeKeys) && ! empty($stripeKeys[$stripeMode]);

        return view('dashboard', [
            'tenantId' => $tenantId,
            'metrics' => [
                'clients' => $clients,
                'projects' => $projects,
                'invoices' => $invoices,
                'receivable_cents' => (int) $receivable,
                'overdue' => $overdue,
                'mrr_cents' => (int) $mrr,
                'arr_cents' => (int) $arr,
                'collection_rate' => $collectionRate,
                'health' => [
                    'queue' => $queueBacklog < 10 && $failedJobs === 0,
                    'queue_backlog' => $queueBacklog,
                    'failed_jobs' => $failedJobs,
                    'stripe' => $stripeReady,
                ],
            ],
            'recentInvoices' => $recentInvoices,
        ]);
    }
}
