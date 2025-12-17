<x-guest-layout>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex items-center py-10 px-4">
        <div class="max-w-4xl w-full mx-auto">
            <div class="bg-white/95 shadow-xl rounded-2xl overflow-hidden ring-1 ring-slate-200 backdrop-blur">
                <div class="px-8 py-6 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            @if ($logoUrl ?? false)
                                <img src="{{ $logoUrl }}" alt="{{ $organization?->name }}" class="h-10 w-auto rounded">
                            @endif
                            <div>
                                <p class="text-xs text-slate-500 uppercase tracking-wide">{{ __('From') }}</p>
                                <p class="text-sm font-semibold text-slate-900">{{ $organization?->name ?? __('Clientflow Pro') }}</p>
                            </div>
                        </div>
                        <p class="text-sm text-slate-500 uppercase tracking-wide">Invoice</p>
                        <h1 class="text-2xl font-bold text-slate-900">{{ $invoice->title }}</h1>
                        <p class="text-slate-500 mt-1">Invoice #{{ $invoice->invoice_number ?? 'N/A' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-slate-500">Amount due</p>
                        <p class="text-3xl font-semibold text-slate-900">
                            {{ $invoice->currency ?? 'USD' }} {{ number_format(($remainingCents ?? $invoice->total_cents) / 100, 2) }}
                        </p>
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold
                            {{ $paid ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                            <span class="h-2 w-2 rounded-full {{ $paid ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse' }}"></span>
                            {{ $paid ? __('Paid') : __('Awaiting payment') }}
                        </span>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="bg-rose-50 text-rose-800 px-8 py-4 border-b border-rose-100">
                        <ul class="list-disc list-inside space-y-1 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($paymentConfirmed)
                    <div class="bg-emerald-50 text-emerald-800 px-8 py-4 border-b border-emerald-100 flex items-start gap-3">
                        <svg class="h-5 w-5 text-emerald-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 00-1.414 0L8 12.586 4.707 9.293a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l8-8a1 1 0 000-1.414z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="font-semibold">{{ __('Payment initiated') }}</p>
                            <p class="text-sm text-emerald-700">{{ __('Thanks! If you just paid, we\'ll confirm as soon as Stripe notifies us.') }}</p>
                        </div>
                    </div>
                @endif

                <div class="px-8 py-6 grid grid-cols-1 md:grid-cols-3 gap-6 border-b border-slate-200">
                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-slate-700">{{ __('Client') }}</p>
                        <p class="text-slate-900">{{ $invoice->client?->name ?? __('No client specified') }}</p>
                        <p class="text-sm text-slate-500">{{ $invoice->client?->email }}</p>
                    </div>
                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-slate-700">{{ __('Due date') }}</p>
                        <p class="text-slate-900">{{ optional($invoice->due_date)->toFormattedDateString() ?? __('Not set') }}</p>
                        <p class="text-sm text-slate-500">{{ __('Status:') }} {{ ucfirst($invoice->status) }}</p>
                    </div>
                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-slate-700">{{ __('Totals') }}</p>
                        <p class="text-slate-900">{{ __('Subtotal:') }} {{ number_format($invoice->subtotal_cents / 100, 2) }}</p>
                        <p class="text-slate-900">{{ __('Discounts:') }} -{{ number_format($invoice->discount_cents / 100, 2) }}</p>
                        <p class="text-slate-900">{{ __('Tax:') }} {{ number_format($invoice->tax_cents / 100, 2) }}</p>
                        <p class="text-slate-900 font-semibold">{{ __('Balance due:') }} {{ number_format(($remainingCents ?? 0) / 100, 2) }}</p>
                    </div>
                </div>

                <div class="px-8 py-6">
                    <p class="text-sm font-semibold text-slate-700 mb-3">{{ __('Line items') }}</p>
                    <div class="overflow-hidden border border-slate-200 rounded-xl">
                        <table class="min-w-full">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-3">{{ __('Description') }}</th>
                                    <th class="text-right text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-3">{{ __('Qty') }}</th>
                                    <th class="text-right text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-3">{{ __('Unit') }}</th>
                                    <th class="text-right text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-3">{{ __('Line total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse ($invoice->items as $item)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-800">{{ $item->description }}</td>
                                        <td class="px-4 py-3 text-right text-slate-700">{{ $item->quantity }}</td>
                                        <td class="px-4 py-3 text-right text-slate-700">{{ number_format($item->unit_price_cents / 100, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-slate-900">{{ number_format($item->line_total_cents / 100, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-4 py-3 text-slate-500" colspan="4">{{ __('No items listed for this invoice.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="px-8 py-6 bg-slate-50 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-600">{{ __('Secure payments processed by Stripe.') }}</p>
                        <p class="text-xs text-slate-500">{{ __('You will be redirected to complete the payment.') }}</p>
                    </div>

                    @if (! $paid)
                        <form method="POST" action="{{ route('pay.invoices.checkout', $invoice->public_hash) }}">
                            @csrf
                            <x-primary-button>
                                {{ __('Pay with card') }}
                            </x-primary-button>
                        </form>
                    @else
                        <span class="text-sm font-semibold text-emerald-700">{{ __('Payment complete') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
