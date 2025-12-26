<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice #{{ $invoice->invoice_number ?? $invoice->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #1f2937;
            background: #fff;
        }
        .container {
            padding: 40px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }
        .header-left, .header-right {
            display: table-cell;
            vertical-align: top;
        }
        .header-left {
            width: 50%;
        }
        .header-right {
            width: 50%;
            text-align: right;
        }
        .logo {
            max-height: 60px;
            max-width: 200px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #111827;
        }
        .company-details {
            font-size: 11px;
            color: #6b7280;
            margin-top: 5px;
        }
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: {{ $organization?->branding_color ?? '#4f46e5' }};
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .invoice-meta {
            margin-top: 10px;
            font-size: 11px;
            color: #6b7280;
        }
        .invoice-meta-row {
            margin-bottom: 3px;
        }
        .invoice-meta-label {
            display: inline-block;
            width: 80px;
        }
        .invoice-meta-value {
            font-weight: bold;
            color: #111827;
        }
        .addresses {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .address-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .address-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9ca3af;
            margin-bottom: 5px;
        }
        .address-content {
            font-size: 12px;
            color: #374151;
        }
        .address-name {
            font-weight: bold;
            color: #111827;
            font-size: 14px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
            padding: 12px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
        }
        .items-table th:last-child {
            text-align: right;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .items-table td:last-child {
            text-align: right;
        }
        .item-description {
            font-weight: 500;
            color: #111827;
        }
        .totals {
            width: 300px;
            margin-left: auto;
            margin-bottom: 30px;
        }
        .totals-row {
            display: table;
            width: 100%;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .totals-label, .totals-value {
            display: table-cell;
        }
        .totals-label {
            color: #6b7280;
        }
        .totals-value {
            text-align: right;
            font-weight: 500;
            color: #111827;
        }
        .totals-row.total {
            border-top: 2px solid #111827;
            border-bottom: none;
            padding-top: 12px;
            margin-top: 8px;
        }
        .totals-row.total .totals-label,
        .totals-row.total .totals-value {
            font-size: 16px;
            font-weight: bold;
        }
        .totals-row.total .totals-value {
            color: {{ $organization?->branding_color ?? '#4f46e5' }};
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-draft { background: #f3f4f6; color: #6b7280; }
        .status-sent { background: #fef3c7; color: #92400e; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        .status-void { background: #f3f4f6; color: #6b7280; }
        .notes {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .notes-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        .notes-content {
            color: #374151;
            font-size: 11px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #9ca3af;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                @if($logoPath)
                    <img src="{{ $logoPath }}" alt="{{ $organization->name }}" class="logo">
                @endif
                <div class="company-name">{{ $organization?->name ?? config('app.name') }}</div>
                <div class="company-details">
                    @if($organization?->address_line1){{ $organization->address_line1 }}<br>@endif
                    @if($organization?->address_line2){{ $organization->address_line2 }}<br>@endif
                    @if($organization?->city || $organization?->state || $organization?->postal_code)
                        {{ $organization?->city }}@if($organization?->city && $organization?->state), @endif{{ $organization?->state }} {{ $organization?->postal_code }}<br>
                    @endif
                    @if($organization?->country){{ $organization->country }}<br>@endif
                    @if($organization?->phone){{ $organization->phone }}<br>@endif
                    @if($organization?->tax_id)Tax ID: {{ $organization->tax_id }}@endif
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">Invoice</div>
                <div class="invoice-meta">
                    <div class="invoice-meta-row">
                        <span class="invoice-meta-label">Invoice #:</span>
                        <span class="invoice-meta-value">{{ $invoice->invoice_number ?? $invoice->id }}</span>
                    </div>
                    <div class="invoice-meta-row">
                        <span class="invoice-meta-label">Date:</span>
                        <span class="invoice-meta-value">{{ optional($invoice->created_at)->format('M j, Y') }}</span>
                    </div>
                    @if($invoice->due_date)
                    <div class="invoice-meta-row">
                        <span class="invoice-meta-label">Due Date:</span>
                        <span class="invoice-meta-value">{{ $invoice->due_date->format('M j, Y') }}</span>
                    </div>
                    @endif
                    <div class="invoice-meta-row" style="margin-top: 8px;">
                        <span class="status-badge status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Addresses -->
        <div class="addresses">
            <div class="address-box">
                <div class="address-label">Bill To</div>
                @if($invoice->client)
                <div class="address-content">
                    <div class="address-name">{{ $invoice->client->name }}</div>
                    @if($invoice->client->email){{ $invoice->client->email }}<br>@endif
                    @if($invoice->client->phone){{ $invoice->client->phone }}<br>@endif
                    @if($invoice->client->address){{ $invoice->client->address }}@endif
                </div>
                @else
                <div class="address-content">—</div>
                @endif
            </div>
            <div class="address-box">
                @if($invoice->project)
                <div class="address-label">Project</div>
                <div class="address-content">
                    <div class="address-name">{{ $invoice->project->name }}</div>
                    @if($invoice->project->description){{ Str::limit($invoice->project->description, 100) }}@endif
                </div>
                @endif
            </div>
        </div>

        <!-- Invoice Title -->
        @if($invoice->title)
        <div style="margin-bottom: 20px;">
            <strong style="font-size: 14px;">{{ $invoice->title }}</strong>
        </div>
        @endif

        <!-- Line Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th style="width: 15%; text-align: center;">Qty</th>
                    <th style="width: 17%; text-align: right;">Unit Price</th>
                    <th style="width: 18%; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td class="item-description">{{ $item->description }}</td>
                    <td style="text-align: center;">{{ $item->quantity }}</td>
                    <td style="text-align: right;">{{ $invoice->currency ?? 'USD' }} {{ number_format($item->unit_price_cents / 100, 2) }}</td>
                    <td style="text-align: right;">{{ $invoice->currency ?? 'USD' }} {{ number_format(($item->unit_price_cents * $item->quantity) / 100, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <div class="totals-row">
                <span class="totals-label">Subtotal</span>
                <span class="totals-value">{{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->subtotal_cents / 100, 2) }}</span>
            </div>
            @if($invoice->tax_cents > 0)
            <div class="totals-row">
                <span class="totals-label">Tax{{ $invoice->tax_rate ? ' (' . $invoice->tax_rate . '%)' : '' }}</span>
                <span class="totals-value">{{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->tax_cents / 100, 2) }}</span>
            </div>
            @endif
            @if($invoice->discount_cents > 0)
            <div class="totals-row">
                <span class="totals-label">Discount</span>
                <span class="totals-value" style="color: #059669;">-{{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->discount_cents / 100, 2) }}</span>
            </div>
            @endif
            <div class="totals-row total">
                <span class="totals-label">Total Due</span>
                <span class="totals-value">{{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->total_cents / 100, 2) }}</span>
            </div>
            @if($invoice->amount_paid_cents > 0)
            <div class="totals-row">
                <span class="totals-label">Amount Paid</span>
                <span class="totals-value" style="color: #059669;">-{{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->amount_paid_cents / 100, 2) }}</span>
            </div>
            <div class="totals-row">
                <span class="totals-label" style="font-weight: bold;">Balance Due</span>
                <span class="totals-value" style="font-weight: bold;">{{ $invoice->currency ?? 'USD' }} {{ number_format(($invoice->total_cents - $invoice->amount_paid_cents) / 100, 2) }}</span>
            </div>
            @endif
        </div>

        <!-- Notes -->
        @if($invoice->notes)
        <div class="notes">
            <div class="notes-title">Notes</div>
            <div class="notes-content">{!! nl2br(e($invoice->notes)) !!}</div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            @if($organization?->invoice_footer)
                {!! nl2br(e($organization->invoice_footer)) !!}
            @else
                Thank you for your business!
            @endif
            <br><br>
            {{ $organization?->name ?? config('app.name') }}
            @if($organization?->website) • {{ $organization->website }}@endif
            @if($organization?->billing_email) • {{ $organization->billing_email }}@endif
        </div>
    </div>
</body>
</html>
</html>
