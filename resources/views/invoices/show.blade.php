<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $invoice->title }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-2">
                    <div><span class="font-semibold">{{ __('Client:') }}</span> {{ $invoice->client?->name }}</div>
                    <div><span class="font-semibold">{{ __('Project:') }}</span> {{ $invoice->project?->name }}</div>
                    <div><span class="font-semibold">{{ __('Subtotal:') }}</span> ${{ number_format($invoice->subtotal_cents / 100, 2) }}</div>
                    <div><span class="font-semibold">{{ __('Total:') }}</span> ${{ number_format($invoice->total_cents / 100, 2) }}</div>
                    <div><span class="font-semibold">{{ __('Notes:') }}</span> {{ $invoice->notes }}</div>
                </div>
                <div class="p-6 border-t">
                    <h3 class="font-semibold mb-2">{{ __('Items') }}</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Line Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($invoice->items as $item)
                                <tr>
                                    <td class="px-4 py-2">{{ $item->description }}</td>
                                    <td class="px-4 py-2 text-right">{{ $item->quantity }}</td>
                                    <td class="px-4 py-2 text-right">${{ number_format($item->unit_price_cents / 100, 2) }}</td>
                                    <td class="px-4 py-2 text-right">${{ number_format($item->line_total_cents / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-6 border-t flex justify-between">
                    <a href="{{ route('invoices.edit', $invoice) }}" class="text-indigo-600 hover:underline">
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
