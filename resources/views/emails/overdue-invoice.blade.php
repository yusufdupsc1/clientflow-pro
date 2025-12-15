Invoice #{{ $invoice->invoice_number }} is overdue.
Total due: {{ number_format($invoice->total_cents / 100, 2) }}.
Due date: {{ optional($invoice->due_date)->toFormattedDateString() }}.
