<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        :root {
            --brand: {{ $organization?->branding_color ?? '#0f172a' }};
            --muted: #4b5563;
            --light: #f8fafc;
        }
        * { box-sizing: border-box; }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 32px;
            background: #ffffff;
            font-size: 12px;
            line-height: 1.5;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand img {
            max-height: 48px;
            max-width: 180px;
        }
        .pill {
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.05);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-size: 10px;
        }
        .meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 24px;
            margin-bottom: 24px;
        }
        .card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
        }
        .card-header {
            background: var(--light);
            color: var(--muted);
            font-weight: 600;
            padding: 10px 14px;
            border-bottom: 1px solid #e5e7eb;
        }
        .card-body {
            padding: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--muted);
            border-bottom: 1px solid #e5e7eb;
        }
        td {
            border-bottom: 1px solid #e5e7eb;
        }
        .totals {
            margin-top: 12px;
            width: 320px;
            margin-left: auto;
        }
        .totals tr:last-child td {
            font-weight: 700;
            color: var(--brand);
            border-top: 2px solid #0f172a;
        }
        .footer {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            @if ($logoPath)
                <img src="{{ $logoPath }}" alt="Logo">
            @endif
            <div>
                <div style="font-size: 18px; font-weight: 700;">{{ $organization?->name ?? 'Clientflow Pro' }}</div>
                <div style="color: var(--muted); font-size: 11px;">{{ $organization?->billing_email }}</div>
            </div>
        </div>
        <div style="text-align: right;">
            <div class="pill" style="background: {{ $invoice->status === 'paid' ? '#dcfce7' : '#e0f2fe' }}; color: {{ $invoice->status === 'paid' ? '#166534' : '#075985' }};">
                {{ strtoupper($invoice->status) }}
            </div>
            <div style="margin-top: 6px; font-size: 22px; font-weight: 700;">
                {{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->total_cents / 100, 2) }}
            </div>
            <div style="color: var(--muted); font-size: 11px;">Invoice #{{ $invoice->invoice_number ?? $invoice->id }}</div>
        </div>
    </div>

    <div class="meta">
        <div class="card">
            <div class="card-header">Billed To</div>
            <div class="card-body">
                <div style="font-weight: 600;">{{ $invoice->client?->name ?? 'Unspecified client' }}</div>
                <div style="color: var(--muted); font-size: 11px;">{{ $invoice->client?->email }}</div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Invoice Details</div>
            <div class="card-body">
                <div><strong>Issue Date:</strong> {{ optional($invoice->created_at)->toFormattedDateString() }}</div>
                <div><strong>Due Date:</strong> {{ optional($invoice->due_date)->toFormattedDateString() ?? 'Not set' }}</div>
                <div><strong>Project:</strong> {{ $invoice->project?->name ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top: 12px;">
        <div class="card-header">Line Items</div>
        <div class="card-body" style="padding: 0;">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align: right;">Qty</th>
                        <th style="text-align: right;">Unit</th>
                        <th style="text-align: right;">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td style="text-align: right;">{{ $item->quantity }}</td>
                            <td style="text-align: right;">{{ $invoice->currency ?? 'USD' }} {{ number_format($item->unit_price_cents / 100, 2) }}</td>
                            <td style="text-align: right;">{{ $invoice->currency ?? 'USD' }} {{ number_format($item->line_total_cents / 100, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <table class="totals">
        <tr>
            <td style="color: var(--muted);">Subtotal</td>
            <td style="text-align: right;">{{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->subtotal_cents / 100, 2) }}</td>
        </tr>
        <tr>
            <td style="color: var(--muted);">Discounts</td>
            <td style="text-align: right;">-{{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->discount_cents / 100, 2) }}</td>
        </tr>
        <tr>
            <td style="color: var(--muted);">Tax ({{ $invoice->tax_rate_percent }}%)</td>
            <td style="text-align: right;">{{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->tax_cents / 100, 2) }}</td>
        </tr>
        <tr>
            <td>Total Due</td>
            <td style="text-align: right;">{{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->total_cents / 100, 2) }}</td>
        </tr>
    </table>

    @if ($invoice->notes)
        <div class="card" style="margin-top: 16px;">
            <div class="card-header">Notes</div>
            <div class="card-body">
                {{ $invoice->notes }}
            </div>
        </div>
    @endif

    <div class="footer">
        {{ $organization?->name ?? 'Clientflow Pro' }} {{ $organization?->tax_id ? '• Tax ID: '.$organization->tax_id : '' }}
    </div>
</body>
</html>
