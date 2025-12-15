<?php

namespace App\Jobs;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ExportInvoicesCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected array $filters = []
    ) {
    }

    public function handle(): string
    {
        $query = Invoice::query()->orderByDesc('created_at');

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['client_id'])) {
            $query->where('client_id', $this->filters['client_id']);
        }

        if (! empty($this->filters['project_id'])) {
            $query->where('project_id', $this->filters['project_id']);
        }

        if (! empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', Carbon::parse($this->filters['date_from']));
        }

        if (! empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', Carbon::parse($this->filters['date_to']));
        }

        $rows = $query->get([
            'invoice_number',
            'title',
            'status',
            'total_cents',
            'sent_at',
            'paid_at',
            'created_at',
        ]);

        return $this->buildCsv($rows);
    }

    protected function buildCsv($rows): string
    {
        $headers = ['invoice_number', 'title', 'status', 'total_cents', 'sent_at', 'paid_at', 'created_at'];
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row->invoice_number,
                $row->title,
                $row->status,
                $row->total_cents,
                optional($row->sent_at)->toIso8601String(),
                optional($row->paid_at)->toIso8601String(),
                optional($row->created_at)->toIso8601String(),
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
