<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Support\Tenancy\Tenant;
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

        return view('dashboard', [
            'tenantId' => $tenantId,
            'metrics' => [
                'clients' => $clients,
                'projects' => $projects,
                'invoices' => $invoices,
                'receivable_cents' => (int) $receivable,
                'overdue' => $overdue,
            ],
            'recentInvoices' => $recentInvoices,
        ]);
    }
}
