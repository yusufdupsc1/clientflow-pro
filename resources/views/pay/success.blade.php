<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full bg-gradient-to-br from-green-50 to-emerald-100 dark:from-slate-900 dark:to-slate-800">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white dark:bg-slate-800 shadow-xl rounded-xl overflow-hidden text-center px-6 py-12">
                <!-- Success Icon -->
                <div
                    class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 dark:bg-green-900">
                    <svg class="h-10 w-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <h1 class="mt-6 text-2xl font-bold text-slate-900 dark:text-white">Payment Successful!</h1>
                <p class="mt-2 text-slate-600 dark:text-slate-400">
                    Thank you for your payment for Invoice #{{ $invoice->invoice_number ?? $invoice->id }}.
                </p>

                <div class="mt-6 p-4 bg-slate-50 dark:bg-slate-700 rounded-lg">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Amount Paid</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">
                        ${{ number_format($invoice->total_cents / 100, 2) }}
                    </p>
                </div>

                <p class="mt-6 text-sm text-slate-500 dark:text-slate-400">
                    A payment receipt will be sent to your email shortly.
                </p>

                <div class="mt-8">
                    <a href="{{ route('pay.status', $invoice) }}"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        View Invoice Status
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>