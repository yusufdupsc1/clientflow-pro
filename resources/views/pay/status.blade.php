<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Status - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-lg">
            <div class="bg-white dark:bg-slate-800 shadow-xl rounded-xl overflow-hidden">
                <!-- Status Header -->
                <div
                    class="px-6 py-8 text-center {{ $isPaid ? 'bg-green-600' : ($isVoid ? 'bg-slate-600' : 'bg-indigo-600') }}">
                    @if($isPaid)
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-500/30 mb-4">
                            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <p class="text-green-200 text-sm uppercase tracking-wide">Invoice Paid</p>
                    @elseif($isVoid)
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-slate-500/30 mb-4">
                            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <p class="text-slate-200 text-sm uppercase tracking-wide">Invoice Voided</p>
                    @else
                        <p class="text-indigo-200 text-sm uppercase tracking-wide">Invoice
                            #{{ $invoice->invoice_number ?? $invoice->id }}</p>
                    @endif
                    <p class="mt-2 text-4xl font-bold text-white">${{ number_format($invoice->total_cents / 100, 2) }}
                    </p>
                </div>

                <!-- Invoice Details -->
                <div class="px-6 py-6 space-y-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Invoice Number</span>
                        <span
                            class="font-medium text-slate-900 dark:text-white">#{{ $invoice->invoice_number ?? $invoice->id }}</span>
                    </div>

                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Title</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ $invoice->title }}</span>
                    </div>

                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Status</span>
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $isPaid ? 'bg-green-100 text-green-800' : ($isVoid ? 'bg-slate-100 text-slate-800' : 'bg-yellow-100 text-yellow-800') }}">
                            {{ ucfirst($invoice->status) }}
                        </span>
                    </div>

                    @if($invoice->paid_at)
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500 dark:text-slate-400">Paid At</span>
                            <span
                                class="font-medium text-slate-900 dark:text-white">{{ $invoice->paid_at->format('M j, Y g:i A') }}</span>
                        </div>
                    @endif

                    @if($invoice->payments->count() > 0)
                        <hr class="border-slate-200 dark:border-slate-700">
                        <h3 class="text-sm font-medium text-slate-900 dark:text-white">Payment History</h3>
                        <div class="space-y-2">
                            @foreach($invoice->payments as $payment)
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500 dark:text-slate-400">{{ $payment->paid_at->format('M j, Y') }}
                                        via {{ ucfirst($payment->method ?? 'Unknown') }}</span>
                                    <span
                                        class="text-green-600 font-medium">${{ number_format($payment->amount_cents / 100, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if(!$isPaid && !$isVoid)
                    <div class="px-6 pb-6">
                        <a href="{{ route('pay.show', $invoice) }}"
                            class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            Pay Now
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>

</html>