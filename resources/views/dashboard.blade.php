<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Clients</div>
                    <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $metrics['clients'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Projects</div>
                    <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $metrics['projects'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Invoices</div>
                    <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $metrics['invoices'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Receivable</div>
                    <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        ${{ number_format($metrics['receivable_cents'] / 100, 2) }}
                    </div>
                    <div class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $metrics['overdue'] }} overdue</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">MRR</div>
                    <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        ${{ number_format($metrics['mrr_cents'] / 100, 2) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">ARR ${{ number_format($metrics['arr_cents'] / 100, 2) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Collection rate</div>
                    <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $metrics['collection_rate'] }}%
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Collected vs billed</div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Health</div>
                    <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <span class="h-2 w-2 rounded-full {{ $metrics['health']['queue'] ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                        Queue {{ $metrics['health']['queue'] ? 'healthy' : 'backlog '.$metrics['health']['queue_backlog'] }}
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200 mt-1">
                        <span class="h-2 w-2 rounded-full {{ $metrics['health']['stripe'] ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                        Stripe {{ $metrics['health']['stripe'] ? 'ready' : 'configure keys' }}
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent invoices</h3>
                        <a href="{{ route('invoices.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm">View all</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm text-left text-gray-700 dark:text-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Invoice</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Client</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($recentInvoices as $invoice)
                                    <tr>
                                        <td class="px-4 py-2">
                                            <a href="{{ route('invoices.show', $invoice) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                                #{{ $invoice->invoice_number ?? $invoice->id }} — {{ $invoice->title }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-2">{{ $invoice->client?->name ?? '—' }}</td>
                                        <td class="px-4 py-2">
                                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-200">
                                                {{ ucfirst($invoice->status ?? 'draft') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-right tabular-nums whitespace-nowrap">{{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->total_cents / 100, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                                            {{ __('No invoices yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
