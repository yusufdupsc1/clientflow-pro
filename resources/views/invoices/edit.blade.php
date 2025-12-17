<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Invoice') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('invoices.update', $invoice) }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="title" :value="__('Title')" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $invoice->title)" required />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="client_id" :value="__('Client')" />
                            <select id="client_id" name="client_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 shadow-sm">
                                <option value="">{{ __('None') }}</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}" @selected(old('client_id', $invoice->client_id) == $client->id)>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="project_id" :value="__('Project')" />
                            <select id="project_id" name="project_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 shadow-sm">
                                <option value="">{{ __('None') }}</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}" @selected(old('project_id', $invoice->project_id) == $project->id)>
                                        {{ $project->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('project_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea id="notes" name="notes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('notes', $invoice->notes) }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="due_date" :value="__('Due Date')" />
                                <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full" :value="old('due_date', optional($invoice->due_date)->format('Y-m-d'))" />
                                <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="currency" :value="__('Currency (ISO)')" />
                                <x-text-input id="currency" name="currency" type="text" maxlength="3" class="mt-1 block w-full uppercase" :value="old('currency', $invoice->currency ?? $defaultCurrency)" />
                                <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="tax_rate_percent" :value="__('Tax / VAT %')" />
                                <x-text-input id="tax_rate_percent" name="tax_rate_percent" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('tax_rate_percent', $invoice->tax_rate_percent)" />
                                <x-input-error :messages="$errors->get('tax_rate_percent')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="discount_cents" :value="__('Discount (cents)')" />
                                <x-text-input id="discount_cents" name="discount_cents" type="number" min="0" class="mt-1 block w-full" :value="old('discount_cents', $invoice->discount_cents)" />
                                <x-input-error :messages="$errors->get('discount_cents')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold">{{ __('Items') }}</h3>
                            @include('invoices.partials.items', ['invoice' => $invoice])
                            <x-input-error :messages="$errors->get('items')" class="mt-2" />
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button>
                                {{ __('Update') }}
                            </x-primary-button>
                        </div>
                    </form>
                    <div class="mt-4">
                        <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                            @csrf
                            @method('DELETE')
                            <x-danger-button>{{ __('Delete') }}</x-danger-button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
