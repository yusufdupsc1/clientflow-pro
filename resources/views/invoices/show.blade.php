<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('invoices.index') }}" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Invoice #{{ $invoice->invoice_number ?? $invoice->id }}
                </h2>
                @php
                    $statusColors = [
                        'draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                        'sent' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                        'paid' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300',
                        'overdue' => 'bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-300',
                        'void' => 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500',
                    ];
                @endphp
                <span
                    class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$invoice->status] ?? $statusColors['draft'] }}">
                    {{ ucfirst($invoice->status) }}
                </span>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    PDF
                </a>
                @if($invoice->status !== 'paid' && $invoice->status !== 'void')
                    <form action="{{ route('invoices.send', $invoice) }}" method="POST">
                        @csrf
                        <x-primary-button>
                            {{ $invoice->status === 'draft' ? 'Send Invoice' : 'Resend Invoice' }}
                        </x-primary-button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Invoice Content -->
                <div class="lg:col-span-2 space-y-6">
                    <div
                        class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 dark:border-gray-700">
                        <div class="p-8">
                            <div class="flex justify-between items-start mb-12">
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                        {{ $invoice->title }}</h3>
                                    <p class="text-gray-500 dark:text-gray-400 mt-1">Ref:
                                        #{{ $invoice->invoice_number ?? $invoice->id }}</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Amount Due</div>
                                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100 tabular-nums">
                                        {{ $invoice->currency }}
                                        {{ number_format(max(0, $invoice->total_cents - $invoice->amount_paid_cents) / 100, 2) }}
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-12 mb-12">
                                <div>
                                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Client
                                    </h4>
                                    <div class="space-y-1">
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $invoice->client?->name }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $invoice->client?->email }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $invoice->client?->company }}</p>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">
                                        Invoice Details</h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 dark:text-gray-400">Date Issued</span>
                                            <span
                                                class="text-gray-900 dark:text-gray-100">{{ $invoice->created_at->format('M d, Y') }}</span>
                                        </div>
                                        <div class="flex justify-between font-semibold">
                                            <span class="text-gray-500 dark:text-gray-400">Due Date</span>
                                            <span
                                                class="{{ optional($invoice->due_date)->isPast() && $invoice->status !== 'paid' ? 'text-rose-600' : 'text-gray-900 dark:text-gray-100' }}">
                                                {{ optional($invoice->due_date)->format('M d, Y') ?? 'Not specified' }}
                                            </span>
                                        </div>
                                        @if($invoice->project)
                                            <div
                                                class="flex justify-between border-t border-gray-100 dark:border-gray-700 pt-2">
                                                <span class="text-gray-500 dark:text-gray-400">Project</span>
                                                <span
                                                    class="text-gray-900 dark:text-gray-100">{{ $invoice->project->name }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <table class="w-full text-left mb-8">
                                <thead>
                                    <tr class="border-b border-gray-100 dark:border-gray-700">
                                        <th class="py-4 font-semibold text-gray-900 dark:text-gray-100">Description</th>
                                        <th class="py-4 px-4 text-right font-semibold text-gray-900 dark:text-gray-100">
                                            Qty</th>
                                        <th class="py-4 px-4 text-right font-semibold text-gray-900 dark:text-gray-100">
                                            Price</th>
                                        <th class="py-4 text-right font-semibold text-gray-900 dark:text-gray-100">
                                            Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                    @foreach($invoice->items as $item)
                                        <tr>
                                            <td class="py-4 text-gray-800 dark:text-gray-200">{{ $item->description }}</td>
                                            <td class="py-4 px-4 text-right text-gray-600 dark:text-gray-400 tabular-nums">
                                                {{ $item->quantity }}</td>
                                            <td class="py-4 px-4 text-right text-gray-600 dark:text-gray-400 tabular-nums">
                                                {{ number_format($item->unit_price_cents / 100, 2) }}</td>
                                            <td
                                                class="py-4 text-right font-medium text-gray-900 dark:text-gray-100 tabular-nums">
                                                {{ number_format($item->line_total_cents / 100, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="flex justify-end">
                                <div class="w-full max-w-xs space-y-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                                        <span
                                            class="text-gray-900 dark:text-gray-100 tabular-nums">{{ $invoice->currency }}
                                            {{ number_format($invoice->subtotal_cents / 100, 2) }}</span>
                                    </div>
                                    @if($invoice->discount_cents > 0)
                                        <div class="flex justify-between text-sm text-rose-600">
                                            <span>Discount</span>
                                            <span class="tabular-nums">- {{ $invoice->currency }}
                                                {{ number_format($invoice->discount_cents / 100, 2) }}</span>
                                        </div>
                                    @endif
                                    @if($invoice->tax_cents > 0)
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500 dark:text-gray-400">Tax
                                                ({{ $invoice->tax_rate_percent }}%)</span>
                                            <span
                                                class="text-gray-900 dark:text-gray-100 tabular-nums">{{ $invoice->currency }}
                                                {{ number_format($invoice->tax_cents / 100, 2) }}</span>
                                        </div>
                                    @endif
                                    <div
                                        class="flex justify-between border-t border-gray-100 dark:border-gray-700 pt-3 text-lg font-bold">
                                        <span class="text-gray-900 dark:text-gray-100">Total</span>
                                        <span
                                            class="text-gray-900 dark:text-gray-100 tabular-nums">{{ $invoice->currency }}
                                            {{ number_format($invoice->total_cents / 100, 2) }}</span>
                                    </div>
                                    @if($invoice->amount_paid_cents > 0)
                                        <div class="flex justify-between text-sm font-medium text-emerald-600">
                                            <span>Amount Paid</span>
                                            <span class="tabular-nums">{{ $invoice->currency }}
                                                {{ number_format($invoice->amount_paid_cents / 100, 2) }}</span>
                                        </div>
                                        <div
                                            class="flex justify-between pt-1 text-sm font-bold text-gray-900 dark:text-gray-100">
                                            <span>Balance Due</span>
                                            <span class="tabular-nums">{{ $invoice->currency }}
                                                {{ number_format(max(0, $invoice->total_cents - $invoice->amount_paid_cents) / 100, 2) }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if($invoice->notes)
                                <div class="mt-12 pt-8 border-t border-gray-100 dark:border-gray-700">
                                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Notes</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $invoice->notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Payment History -->
                    <div
                        class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Payment History</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="bg-gray-50/50 dark:bg-gray-700/50">
                                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Date</th>
                                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Reference
                                        </th>
                                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400 text-right">
                                            Amount</th>
                                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400 text-right">
                                            Refunded</th>
                                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400 text-center">
                                            Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($invoice->payments as $payment)
                                        <tr>
                                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                                                {{ $payment->paid_at->format('M d, Y') }}</td>
                                            <td class="px-6 py-4">
                                                <div class="text-gray-900 dark:text-gray-100 font-medium">
                                                    {{ $payment->method ?? 'Payment' }}</div>
                                                <div class="text-xs text-gray-400">
                                                    {{ $payment->reference ?? $payment->stripe_payment_intent_id }}</div>
                                            </td>
                                            <td class="px-6 py-4 text-right text-gray-900 dark:text-gray-100 tabular-nums">
                                                {{ number_format($payment->amount_cents / 100, 2) }}</td>
                                            <td class="px-6 py-4 text-right text-rose-500 tabular-nums">
                                                @if($payment->refunded_cents > 0)
                                                    -{{ number_format($payment->refunded_cents / 100, 2) }}
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                @if($payment->amount_cents > $payment->refunded_cents)
                                                    <form action="{{ route('payments.refund', $payment) }}" method="POST"
                                                        onsubmit="return confirm('Refund this payment?')">
                                                        @csrf
                                                        <button type="submit"
                                                            class="text-rose-600 hover:text-rose-900 dark:text-rose-400 dark:hover:text-rose-300 text-xs font-semibold">
                                                            Refund
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-gray-400 text-xs italic">Full Refund</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5"
                                                class="px-6 py-12 text-center text-gray-500 dark:text-gray-400 italic">No
                                                payments recorded yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Actions -->
                <div class="space-y-6">
                    <div
                        class="bg-white dark:bg-gray-800 p-6 shadow-sm sm:rounded-xl border border-gray-100 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Sharing</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs text-gray-400 uppercase tracking-widest block mb-2">Public Payment
                                    Link</label>
                                @if($invoice->public_hash)
                                    <div class="flex gap-2">
                                        <input type="text" readonly
                                            value="{{ route('pay.invoices.show', $invoice->public_hash) }}"
                                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 bg-gray-50 dark:bg-gray-900">
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500">Draft invoices don't have public links.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white dark:bg-gray-800 p-6 shadow-sm sm:rounded-xl border border-gray-100 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Management</h4>
                        <div class="space-y-3">
                            <a href="{{ route('invoices.edit', $invoice) }}"
                                class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Edit Invoice
                            </a>

                            @if($invoice->status === 'sent' || $invoice->status === 'overdue')
                                <form action="{{ route('invoices.void', $invoice) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="w-full px-4 py-2 text-sm font-medium text-amber-600 bg-white dark:bg-gray-800 border border-amber-200 dark:border-amber-900/50 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-900/10 transition-colors"
                                        onclick="return confirm('Mark this invoice as void?')">
                                        Void Invoice
                                    </button>
                                </form>
                            @endif

                            <form action="{{ route('invoices.destroy', $invoice) }}" method="POST"
                                onsubmit="return confirm('Delete this invoice permanently? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="w-full px-4 py-2 text-sm font-medium text-rose-600 hover:text-rose-700 transition-colors text-center">
                                    Delete Invoice
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>