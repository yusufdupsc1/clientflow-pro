<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    use AuthorizesRequests;

    public function invoices(): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $rows = Invoice::select(['invoice_number', 'title', 'status', 'total_cents', 'sent_at', 'paid_at', 'created_at'])
            ->orderByDesc('created_at')
            ->get();

        return $this->csvResponse($rows->map(function ($inv) {
            return [
                $inv->invoice_number,
                $inv->title,
                $inv->status,
                $inv->total_cents,
                optional($inv->sent_at)->toIso8601String(),
                optional($inv->paid_at)->toIso8601String(),
                optional($inv->created_at)->toIso8601String(),
            ];
        }), ['invoice_number', 'title', 'status', 'total_cents', 'sent_at', 'paid_at', 'created_at'], 'invoices.csv');
    }

    public function payments(): Response
    {
        $this->authorize('viewAny', Payment::class);

        $rows = Payment::select(['id', 'invoice_id', 'amount_cents', 'method', 'reference', 'paid_at', 'created_at'])
            ->orderByDesc('created_at')
            ->get();

        return $this->csvResponse($rows->map(function ($pay) {
            return [
                $pay->id,
                $pay->invoice_id,
                $pay->amount_cents,
                $pay->method,
                $pay->reference,
                optional($pay->paid_at)->toIso8601String(),
                optional($pay->created_at)->toIso8601String(),
            ];
        }), ['id', 'invoice_id', 'amount_cents', 'method', 'reference', 'paid_at', 'created_at'], 'payments.csv');
    }

    protected function csvResponse($rows, array $headers, string $filename): Response
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
