<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Invoices') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-end mb-4">
                <a href="{{ route('invoices.create') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                    {{ __('New Invoice') }}
                </a>
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Title</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Client</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Due</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Paid</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Balance</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($invoices as $invoice)
                                <tr>
                                    <td class="px-4 py-2">
                                        <a href="{{ route('invoices.show', $invoice) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                            {{ $invoice->title }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-200">
                                            {{ ucfirst($invoice->status ?? 'draft') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">{{ $invoice->client?->name }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        {{ optional($invoice->due_date)->toFormattedDateString() ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2 tabular-nums whitespace-nowrap">{{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->amount_paid_cents / 100, 2) }}</td>
                                    <td class="px-4 py-2 tabular-nums whitespace-nowrap">{{ $invoice->currency ?? 'USD' }} {{ number_format(($invoice->total_cents - $invoice->amount_paid_cents) / 100, 2) }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <a href="{{ route('invoices.edit', $invoice) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                            {{ __('Edit') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('No invoices yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">
                        {{ $invoices->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
