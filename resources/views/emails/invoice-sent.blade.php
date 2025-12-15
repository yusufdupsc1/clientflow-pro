<p>Invoice {{ $invoice->invoice_number ?? $invoice->id }} has been sent.</p>
<p>Total: {{ number_format($invoice->total_cents / 100, 2) }}</p>
