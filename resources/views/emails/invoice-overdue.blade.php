<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice Overdue</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #374151;
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: #ef4444;
            color: #fff;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .content {
            padding: 30px;
        }

        .alert {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin-bottom: 20px;
            color: #991b1b;
        }

        .details {
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 18px;
        }

        .detail-label {
            color: #6b7280;
        }

        .btn {
            display: inline-block;
            background: #4f46e5;
            color: #fff;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
        }

        .btn:hover {
            background: #4338ca;
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: #9ca3af;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>⚠️ Invoice Overdue</h1>
            </div>
            <div class="content">
                <div class="alert">
                    <strong>Payment Required:</strong> This invoice is past its due date.
                </div>

                <p>Hello{{ $client?->name ? ' ' . $client->name : '' }},</p>

                <p>This is a reminder that Invoice <strong>#{{ $invoice->invoice_number ?? $invoice->id }}</strong> is
                    now overdue. Please arrange payment at your earliest convenience.</p>

                <div class="details">
                    <div class="detail-row">
                        <span class="detail-label">Invoice #</span>
                        <span>{{ $invoice->invoice_number ?? $invoice->id }}</span>
                    </div>
                    @if($invoice->title)
                        <div class="detail-row">
                            <span class="detail-label">Description</span>
                            <span>{{ $invoice->title }}</span>
                        </div>
                    @endif
                    <div class="detail-row">
                        <span class="detail-label">Due Date</span>
                        <span style="color: #ef4444;">{{ $invoice->due_date?->format('M j, Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Amount Due</span>
                        <span>${{ number_format(($invoice->total_cents - $invoice->amount_paid_cents) / 100, 2) }}</span>
                    </div>
                </div>

                <div style="text-align: center;">
                    <a href="{{ $paymentUrl }}" class="btn">Pay Now</a>
                </div>

                <p style="margin-top: 30px;">If you have already sent payment, please disregard this notice.</p>

                <p>
                    Thank you,<br>
                    {{ $organization?->name ?? config('app.name') }}
                </p>
            </div>
        </div>
        <div class="footer">
            @if($organization?->billing_email)
                Questions? Contact us at {{ $organization->billing_email }}
            @endif
        </div>
    </div>
</body>

</html>