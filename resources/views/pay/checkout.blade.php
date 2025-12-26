<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Invoice #{{ $invoice->invoice_number ?? $invoice->id }} - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-lg">
            <!-- Logo/Branding -->
            <div class="text-center mb-8">
                @if($invoice->organization?->logo_path)
                    <img src="{{ Storage::url($invoice->organization->logo_path) }}"
                        alt="{{ $invoice->organization->name }}" class="mx-auto h-12 w-auto">
                @else
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                        {{ $invoice->organization?->name ?? config('app.name') }}</h1>
                @endif
            </div>

            <div class="bg-white dark:bg-slate-800 shadow-xl rounded-xl overflow-hidden">
                <!-- Header -->
                <div class="bg-indigo-600 px-6 py-8 text-center">
                    <p class="text-indigo-200 text-sm uppercase tracking-wide">Invoice
                        #{{ $invoice->invoice_number ?? $invoice->id }}</p>
                    <p class="mt-2 text-4xl font-bold text-white">${{ $remainingFormatted }}</p>
                    <p class="mt-1 text-indigo-200">{{ $currency }}</p>
                </div>

                <!-- Invoice Details -->
                <div class="px-6 py-6 space-y-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Invoice Title</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ $invoice->title }}</span>
                    </div>

                    @if($invoice->client)
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500 dark:text-slate-400">Billed To</span>
                            <span class="font-medium text-slate-900 dark:text-white">{{ $invoice->client->name }}</span>
                        </div>
                    @endif

                    @if($invoice->due_date)
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500 dark:text-slate-400">Due Date</span>
                            <span
                                class="font-medium text-slate-900 dark:text-white">{{ $invoice->due_date->format('M j, Y') }}</span>
                        </div>
                    @endif

                    <hr class="border-slate-200 dark:border-slate-700">

                    <!-- Line Items -->
                    <div class="space-y-2">
                        @foreach($invoice->items as $item)
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-700 dark:text-slate-300">{{ $item->description }} ×
                                    {{ $item->quantity }}</span>
                                <span
                                    class="text-slate-900 dark:text-white">${{ number_format($item->unit_price_cents * $item->quantity / 100, 2) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <hr class="border-slate-200 dark:border-slate-700">

                    <!-- Totals -->
                    <div class="space-y-1">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500 dark:text-slate-400">Subtotal</span>
                            <span
                                class="text-slate-900 dark:text-white">${{ number_format($invoice->subtotal_cents / 100, 2) }}</span>
                        </div>
                        @if($invoice->tax_cents > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500 dark:text-slate-400">Tax ({{ $invoice->tax_rate }}%)</span>
                                <span
                                    class="text-slate-900 dark:text-white">${{ number_format($invoice->tax_cents / 100, 2) }}</span>
                            </div>
                        @endif
                        @if($invoice->discount_cents > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500 dark:text-slate-400">Discount</span>
                                <span class="text-green-600">-${{ number_format($invoice->discount_cents / 100, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-base font-semibold pt-2">
                            <span class="text-slate-900 dark:text-white">Total Due</span>
                            <span class="text-slate-900 dark:text-white">${{ $remainingFormatted }}</span>
                        </div>
                    </div>
                </div>

                <!-- Pay Button -->
                <div class="px-6 pb-6">
                    @if(session('error'))
                        <div
                            class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/50 text-red-700 dark:text-red-300 text-sm">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('pay.redirect', $invoice) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            Pay with Card
                        </button>
                    </form>

                    <p class="mt-4 text-center text-xs text-slate-500 dark:text-slate-400">
                        Secure payment powered by <span class="font-medium">Stripe</span>
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <p class="mt-6 text-center text-xs text-slate-500 dark:text-slate-400">
                If you have questions about this invoice, please contact
                {{ $invoice->organization?->billing_email ?? $invoice->organization?->owner?->email ?? 'the sender' }}.
            </p>
        </div>
    </div>
</body>

</html>