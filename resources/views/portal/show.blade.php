<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal - {{ $organization->name }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap"
        rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .editorial-heading {
            font-family: 'Playfair Display', serif;
        }

        .portal-glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .dark .portal-glass {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 min-h-screen">
    <div class="max-w-5xl mx-auto px-4 py-12 md:py-24">
        <!-- Brand Header -->
        <header class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-16">
            <div class="space-y-4">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $organization->name }}"
                        class="h-12 w-auto grayscale hover:grayscale-0 transition-all duration-500">
                @else
                    <span class="text-xl font-bold tracking-tight uppercase">{{ $organization->name }}</span>
                @endif
                <h1 class="editorial-heading text-5xl md:text-7xl font-medium tracking-tight">
                    Client Portal.
                </h1>
            </div>
            <div class="text-right space-y-1">
                <p class="text-sm font-medium text-slate-500 uppercase tracking-widest">Business Workspace</p>
                <p class="text-lg">{{ $organization->billing_email ?? $organization->owner?->email }}</p>
            </div>
        </header>

        @if(session('success') || $paymentConfirmed)
            <div
                class="mb-8 p-4 rounded-lg bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') ?? 'Payment confirmed successfully. Thank you.' }}</span>
            </div>
        @endif

        @if(session('error'))
            <div
                class="mb-8 p-4 rounded-lg bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <!-- Main Content: Invoice Details -->
            <div class="lg:col-span-2 space-y-12">
                <section class="portal-glass rounded-3xl p-8 md:p-12 shadow-2xl shadow-slate-200/50 dark:shadow-none">
                    <div class="flex flex-col md:flex-row justify-between items-start gap-8 mb-12">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2 block">Invoice
                                Reference</span>
                            <h2 class="text-3xl font-bold tracking-tight">
                                #{{ $invoice->invoice_number ?? $invoice->id }}</h2>
                            <p class="text-slate-500 mt-1">{{ $invoice->title }}</p>
                        </div>
                        <div class="text-right">
                            <span
                                class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2 block">Status</span>
                            <span @class([
                                'px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider',
                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' => $paid,
                                'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' => !$paid && $invoice->status === 'sent',
                                'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' => $invoice->status === 'overdue',
                                'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-400' => in_array($invoice->status, ['draft', 'void']),
                            ])>
                                {{ $paid ? 'Paid' : ucfirst($invoice->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-6">
                        @foreach($invoice->items as $item)
                            <div
                                class="flex justify-between items-center py-4 border-b border-slate-100 dark:border-slate-800 last:border-0">
                                <div>
                                    <h4 class="font-medium">{{ $item->description }}</h4>
                                    <p class="text-sm text-slate-500">Quantity: {{ $item->quantity }}</p>
                                </div>
                                <span class="font-semibold">{{ $invoice->currency }}
                                    {{ number_format($item->unit_price_cents * $item->quantity / 100, 2) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div
                        class="mt-12 pt-8 border-t-2 border-slate-900 dark:border-slate-100 flex justify-between items-end">
                        <div>
                            <p class="text-sm font-medium text-slate-500 uppercase tracking-widest mb-1">Total Balance
                            </p>
                            <p class="text-5xl font-bold tracking-tighter">{{ $invoice->currency }}
                                {{ $remainingFormatted }}</p>
                        </div>
                        @if(!$paid)
                            <form action="{{ route('portal.checkout', $invoice->public_hash) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-8 py-4 rounded-xl font-bold hover:scale-105 transition-transform duration-300 shadow-xl shadow-slate-900/20">
                                    Pay Now
                                </button>
                            </form>
                        @endif
                    </div>
                </section>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="portal-glass rounded-2xl p-6">
                        <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">Client Information
                        </h4>
                        <p class="font-bold text-lg">{{ $invoice->client->name }}</p>
                        <p class="text-slate-500 text-sm mt-1">{{ $invoice->client->email }}</p>
                        @if($invoice->client->address)
                            <p class="text-slate-500 text-sm mt-4 italic">{{ $invoice->client->address }}</p>
                        @endif
                    </div>
                    <div class="portal-glass rounded-2xl p-6">
                        <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">Project Context</h4>
                        @if($invoice->project)
                            <p class="font-bold text-lg">{{ $invoice->project->name }}</p>
                            <p class="text-slate-500 text-sm mt-1">{{ Str::limit($invoice->project->description, 100) }}</p>
                        @else
                            <p class="text-slate-400 italic">No specific project linked.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar: Actions & History -->
            <aside class="space-y-12">
                <section class="space-y-4">
                    <h3 class="editorial-heading text-2xl">Actions.</h3>
                    <div class="flex flex-col gap-3">
                        <a href="{{ route('portal.download', $invoice->public_hash) }}"
                            class="flex items-center justify-between p-4 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-slate-900 dark:hover:border-white transition-colors group">
                            <span class="font-medium">Download PDF</span>
                            <svg class="w-5 h-5 transform group-hover:translate-y-1 transition-transform" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </a>
                        <button onclick="window.print()"
                            class="flex items-center justify-between p-4 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-slate-900 dark:hover:border-white transition-colors group">
                            <span class="font-medium">Print Invoice</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                        </button>
                    </div>
                </section>

                @if(count($history) > 0)
                    <section class="space-y-4">
                        <h3 class="editorial-heading text-2xl">History.</h3>
                        <div class="space-y-4">
                            @foreach($history as $pastInvoice)
                                <a href="{{ route('portal.show', $pastInvoice->public_hash) }}"
                                    class="block p-4 portal-glass rounded-xl hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
                                    <div class="flex justify-between items-start mb-1">
                                        <span
                                            class="font-bold text-sm">#{{ $pastInvoice->invoice_number ?? $pastInvoice->id }}</span>
                                        <span
                                            class="text-[10px] font-bold uppercase tracking-tighter {{ $pastInvoice->status === 'paid' ? 'text-emerald-500' : 'text-slate-400' }}">
                                            {{ ucfirst($pastInvoice->status) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500">{{ optional($pastInvoice->created_at)->format('M d, Y') }}
                                        — {{ $pastInvoice->currency }} {{ number_format($pastInvoice->total_cents / 100, 2) }}
                                    </p>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </aside>
        </div>

        <footer class="mt-24 pt-12 border-t border-slate-200 dark:border-slate-800 text-center space-y-4">
            <p class="text-slate-400 text-sm">
                Powered by Clientflow Pro. Secure & Professional.
            </p>
            <div class="flex justify-center gap-6">
                <span class="h-2 w-2 rounded-full bg-slate-200 dark:bg-slate-800"></span>
                <span class="h-2 w-2 rounded-full bg-slate-200 dark:bg-slate-800"></span>
                <span class="h-2 w-2 rounded-full bg-slate-200 dark:bg-slate-800"></span>
            </div>
        </footer>
    </div>
</body>

</html>