<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $invoice->title }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-2">
                    <div><span class="font-semibold">{{ __('Client:') }}</span> {{ $invoice->client?->name }}</div>
                    <div><span class="font-semibold">{{ __('Project:') }}</span> {{ $invoice->project?->name }}</div>
                    <div class="tabular-nums whitespace-nowrap"><span class="font-semibold">{{ __('Subtotal:') }}</span> {{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->subtotal_cents / 100, 2) }}</div>
                    <div class="tabular-nums whitespace-nowrap"><span class="font-semibold">{{ __('Discount:') }}</span> {{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->discount_cents / 100, 2) }}</div>
                    <div class="tabular-nums whitespace-nowrap"><span class="font-semibold">{{ __('Tax:') }}</span> {{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->tax_cents / 100, 2) }} ({{ $invoice->tax_rate_percent }}%)</div>
                    <div class="tabular-nums whitespace-nowrap"><span class="font-semibold">{{ __('Total:') }}</span> {{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->total_cents / 100, 2) }}</div>
                    <div><span class="font-semibold">{{ __('Currency:') }}</span> {{ $invoice->currency ?? 'USD' }}</div>
                    <div><span class="font-semibold">{{ __('Due Date:') }}</span> {{ optional($invoice->due_date)->toFormattedDateString() ?? __('Not set') }}</div>
                    <div><span class="font-semibold">{{ __('Notes:') }}</span> <span class="text-gray-700 dark:text-gray-200">{{ $invoice->notes }}</span></div>
                </div>
                <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold mb-2 text-gray-900 dark:text-gray-100">{{ __('Items') }}</h3>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm text-left text-gray-700 dark:text-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Qty</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Unit</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Line Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($invoice->items as $item)
                                <tr>
                                    <td class="px-4 py-2">{{ $item->description }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">{{ $item->quantity }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums whitespace-nowrap">{{ $invoice->currency ?? 'USD' }} {{ number_format($item->unit_price_cents / 100, 2) }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums whitespace-nowrap">{{ $invoice->currency ?? 'USD' }} {{ number_format($item->line_total_cents / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-6 border-t border-gray-200 dark:border-gray-700 space-y-2">
                    <div class="font-semibold">{{ __('Public payment link') }}</div>
                    @if($invoice->public_hash)
                        <a class="text-indigo-600 dark:text-indigo-400 hover:underline break-all" href="{{ route('pay.invoices.show', $invoice->public_hash) }}">
                            {{ route('pay.invoices.show', $invoice->public_hash) }}
                        </a>
                    @else
                        <p class="text-gray-600 dark:text-gray-400 text-sm">{{ __('Generate a payment link after saving the invoice.') }}</p>
                    @endif
                </div>
                <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                    <a href="{{ route('invoices.edit', $invoice) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                        {{ __('Edit') }}
                    </a>
                    <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>{{ __('Delete') }}</x-danger-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
