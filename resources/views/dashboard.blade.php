<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Dashboard') }}
            </h2>
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
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Health Issues Alert --}}
            @if(isset($health) && count($health['issues']) > 0)
            <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">System Alerts</h3>
                        <ul class="mt-1 text-sm text-yellow-700 dark:text-yellow-300 list-disc list-inside">
                            @foreach($health['issues'] as $issue)
                            <li>{{ $issue }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            @endif

            {{-- Primary Metrics Row --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
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

            {{-- Revenue & Collection Metrics --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                {{-- MRR --}}
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 overflow-hidden shadow-sm sm:rounded-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-indigo-100 opacity-80">Monthly Revenue (MRR)</div>
                            <div class="text-3xl font-bold mt-1">${{ number_format(($metrics['mrr_cents'] ?? 0) / 100, 0) }}</div>
                        </div>
                        <div class="p-3 bg-white/20 rounded-full">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 text-sm text-indigo-100 opacity-70">Last 30 days</div>
                </div>

                {{-- ARR --}}
                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 overflow-hidden shadow-sm sm:rounded-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-emerald-100 opacity-80">Annual Revenue (ARR)</div>
                            <div class="text-3xl font-bold mt-1">${{ number_format(($metrics['arr_cents'] ?? 0) / 100, 0) }}</div>
                        </div>
                        <div class="p-3 bg-white/20 rounded-full">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 text-sm text-emerald-100 opacity-70">Projected annually</div>
                </div>

                {{-- Collection Rate --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Collection Rate</div>
                            <div class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $metrics['collection_rate'] ?? 0 }}%</div>
                        </div>
                        <div class="relative h-16 w-16">
                            <svg class="transform -rotate-90 w-16 h-16">
                                <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="6" fill="transparent" class="text-gray-200 dark:text-gray-700"/>
                                <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="6" fill="transparent"
                                    class="{{ ($metrics['collection_rate'] ?? 0) >= 80 ? 'text-green-500' : (($metrics['collection_rate'] ?? 0) >= 50 ? 'text-yellow-500' : 'text-red-500') }}"
                                    stroke-dasharray="{{ 2 * 3.14159 * 28 }}"
                                    stroke-dashoffset="{{ 2 * 3.14159 * 28 * (1 - (($metrics['collection_rate'] ?? 0) / 100)) }}"
                                    stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">Last 90 days</div>
                </div>
            </div>

            {{-- Recent Invoices --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent invoices</h3>
                        <a href="{{ route('invoices.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm">View all</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
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
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('invoices.show', $invoice) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                                #{{ $invoice->invoice_number ?? $invoice->id }} — {{ $invoice->title }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $invoice->client?->name ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            @php
                                                $statusColors = [
                                                    'draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                                    'sent' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                                                    'paid' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                                                    'overdue' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
                                                    'void' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                                                ];
                                            @endphp
                                            <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$invoice->status ?? 'draft'] ?? $statusColors['draft'] }}">
                                                {{ ucfirst($invoice->status ?? 'draft') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium tabular-nums whitespace-nowrap">
                                            {{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->total_cents / 100, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                            <x-empty-state 
                                                title="No invoices yet" 
                                                description="Create your first invoice to start tracking payments."
                                                icon="document"
                                                :action-url="route('invoices.create')"
                                                action-text="Create Invoice"
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
    </div>
</x-app-layout>
