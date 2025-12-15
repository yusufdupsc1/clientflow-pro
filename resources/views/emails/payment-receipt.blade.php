Payment received.
Invoice #{{ $invoice->invoice_number }} for {{ number_format($invoice->total_cents / 100, 2) }}.
Payment amount: {{ number_format($payment->amount_cents / 100, 2) }} via {{ $payment->method ?? 'payment' }}.
