<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Dashboard') }}
            </h2>
            <div class="flex items-center gap-4">
                {{-- Quick Actions --}}
                <div class="flex items-center gap-2 mr-4 border-r border-gray-200 dark:border-gray-700 pr-4">
                    <a href="{{ route('clients.create') }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Client
                    </a>
                    <a href="{{ route('invoices.create') }}" class="text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Invoice
                    </a>
                </div>

                {{-- Health Indicator --}}
                @if(isset($health))
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400">System:</span>
                    @if($health['overall'] === 'ok')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="5"/></svg>
                            Healthy
                        </span>
                    @elseif($health['overall'] === 'warning')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200" title="{{ implode(', ', $health['issues']) }}">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="5"/></svg>
                            Warning
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200" title="{{ implode(', ', $health['issues']) }}">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="5"/></svg>
                            Error
                        </span>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Health Issues Alert --}}
            @if(isset($health) && count($health['issues']) > 0)
            <div class="mb-8 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl overflow-hidden shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-bold text-amber-800 dark:text-amber-200">System Attention Required</h3>
                        <ul class="mt-1 text-sm text-amber-700 dark:text-amber-300 list-disc list-inside">
                            @foreach($health['issues'] as $issue)
                            <li>{{ $issue }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            @endif

            {{-- Metrics Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Revenue Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden group hover:shadow-md transition-shadow">
                    <div class="absolute top-0 right-0 p-3 opacity-5 group-hover:opacity-10 transition-opacity">
                        <svg class="w-16 h-16 text-indigo-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 14h-2v-2h2v2zm0-4h-2V7h2v5z"/></svg>
                    </div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Monthly Revenue (MRR)</div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100 tabular-nums">
                            ${{ number_format($metrics['mrr_cents'] / 100, 2) }}
                        </div>
                    </div>
                    <div class="mt-4 text-xs font-semibold text-indigo-600 dark:text-indigo-400 tracking-wider uppercase">
                        ARR: ${{ number_format($metrics['arr_cents'] / 100, 2) }}
                    </div>
                </div>

                <!-- Collection Rate Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Collection Rate</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $metrics['collection_rate'] }}%</div>
                    <div class="mt-4 w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full transition-all duration-1000" style="width: {{ $metrics['collection_rate'] }}%"></div>
                    </div>
                </div>

                <!-- Receivable Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Receivable</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100 tabular-nums">
                        ${{ number_format($metrics['receivable_cents'] / 100, 2) }}
                    </div>
                    <div class="mt-4 text-xs font-semibold text-rose-600 dark:text-rose-400 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/></svg>
                        {{ $metrics['overdue'] }} Overdue
                    </div>
                </div>

                <!-- System Health Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">Operations</div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Queue</span>
                            <span class="flex items-center gap-1 text-xs font-bold {{ $metrics['health']['queue'] ? 'text-emerald-600' : 'text-amber-600' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $metrics['health']['queue'] ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                {{ $metrics['health']['queue'] ? 'Optimal' : 'Backlog' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Stripe</span>
                            <span class="flex items-center gap-1 text-xs font-bold {{ $metrics['health']['stripe'] ? 'text-emerald-600' : 'text-rose-600' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $metrics['health']['stripe'] ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                {{ $metrics['health']['stripe'] ? 'Connected' : 'Offline' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Recent Invoices</h3>
                    <a href="{{ route('invoices.index') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 transition-colors">
                        View All Activity &rarr;
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-semibold">
                                <th class="px-8 py-4">Invoice</th>
                                <th class="px-8 py-4">Client</th>
                                <th class="px-8 py-4">Status</th>
                                <th class="px-8 py-4 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                            @forelse ($recentInvoices as $invoice)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors group">
                                    <td class="px-8 py-5">
                                        <a href="{{ route('invoices.show', $invoice) }}" class="font-bold text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                            #{{ $invoice->invoice_number ?? $invoice->id }}
                                        </a>
                                        <div class="text-xs text-gray-400 mt-0.5">{{ $invoice->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-8 py-5">
                                        <div class="font-medium text-gray-800 dark:text-gray-200">{{ $invoice->client?->name ?? '—' }}</div>
                                        <div class="text-xs text-gray-400">{{ $invoice->client?->company }}</div>
                                    </td>
                                    <td class="px-8 py-5">
                                        @php
                                            $statusColors = [
                                                'draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                                'sent' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                                                'paid' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300',
                                                'overdue' => 'bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-300',
                                                'void' => 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500',
                                            ];
                                        @endphp
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold tracking-tight {{ $statusColors[$invoice->status] ?? $statusColors['draft'] }}">
                                            {{ ucfirst($invoice->status) }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-5 text-right font-bold text-gray-900 dark:text-gray-100 tabular-nums">
                                        {{ $invoice->currency }} {{ number_format($invoice->total_cents / 100, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-8 py-16 text-center">
                                        <x-empty-state 
                                            title="No activity yet" 
                                            description="Ready to send your first professional invoice?"
                                            icon="document"
                                            :action-url="route('invoices.create')"
                                            action-text="Create First Invoice"
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
