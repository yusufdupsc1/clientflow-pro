<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Response;
use App\Jobs\ExportInvoicesCsv;
use App\Jobs\ExportPaymentsCsv;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    use AuthorizesRequests;

    public function invoices(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $filters = $request->validate([
            'status' => ['nullable', 'string'],
            'client_id' => ['nullable', 'integer'],
            'project_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $csv = ExportInvoicesCsv::dispatchSync($filters);

        return $this->csvResponse($csv, 'invoices.csv');
    }

    public function payments(Request $request): Response
    {
        $this->authorize('viewAny', Payment::class);

        $filters = $request->validate([
            'method' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $csv = ExportPaymentsCsv::dispatchSync($filters);

        return $this->csvResponse($csv, 'payments.csv');
    }

    protected function csvResponse(string $csv, string $filename): Response
    {
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
