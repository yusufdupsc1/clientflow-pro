<<<<<<< HEAD
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Billing & Stripe') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-4">
                    @include('organizations.settings._nav')

                    @if (session('status'))
                        <div class="rounded-md bg-emerald-50 dark:bg-emerald-900/20 p-4 text-emerald-800 dark:text-emerald-200 text-sm">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('settings.billing.update') }}" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="stripe_mode" :value="__('Stripe Mode')" />
                            <select id="stripe_mode" name="stripe_mode" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 shadow-sm">
                                <option value="test" @selected(old('stripe_mode', $organization->stripe_mode ?? 'test') === 'test')>{{ __('Test') }}</option>
                                <option value="live" @selected(old('stripe_mode', $organization->stripe_mode ?? 'test') === 'live')>{{ __('Live') }}</option>
                            </select>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Use test mode until you are ready for production charges.') }}</p>
                            <x-input-error :messages="$errors->get('stripe_mode')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="stripe_test_publishable_key" :value="__('Test Publishable Key')" />
                                <x-text-input id="stripe_test_publishable_key" name="stripe_test_publishable_key" type="text" class="mt-1 block w-full" :value="old('stripe_test_publishable_key', $organization->stripe_test_publishable_key)" />
                                <x-input-error :messages="$errors->get('stripe_test_publishable_key')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="stripe_test_secret" :value="__('Test Secret Key')" />
                                <x-text-input id="stripe_test_secret" name="stripe_test_secret" type="text" class="mt-1 block w-full" :value="old('stripe_test_secret', $organization->stripe_test_secret)" />
                                <x-input-error :messages="$errors->get('stripe_test_secret')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="stripe_test_webhook_secret" :value="__('Test Webhook Secret')" />
                                <x-text-input id="stripe_test_webhook_secret" name="stripe_test_webhook_secret" type="text" class="mt-1 block w-full" :value="old('stripe_test_webhook_secret', $organization->stripe_test_webhook_secret)" />
                                <x-input-error :messages="$errors->get('stripe_test_webhook_secret')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="stripe_live_publishable_key" :value="__('Live Publishable Key')" />
                                <x-text-input id="stripe_live_publishable_key" name="stripe_live_publishable_key" type="text" class="mt-1 block w-full" :value="old('stripe_live_publishable_key', $organization->stripe_live_publishable_key)" />
                                <x-input-error :messages="$errors->get('stripe_live_publishable_key')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="stripe_live_secret" :value="__('Live Secret Key')" />
                                <x-text-input id="stripe_live_secret" name="stripe_live_secret" type="text" class="mt-1 block w-full" :value="old('stripe_live_secret', $organization->stripe_live_secret)" />
                                <x-input-error :messages="$errors->get('stripe_live_secret')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="stripe_live_webhook_secret" :value="__('Live Webhook Secret')" />
                                <x-text-input id="stripe_live_webhook_secret" name="stripe_live_webhook_secret" type="text" class="mt-1 block w-full" :value="old('stripe_live_webhook_secret', $organization->stripe_live_webhook_secret)" />
                                <x-input-error :messages="$errors->get('stripe_live_webhook_secret')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button>{{ __('Save billing settings') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
=======
<x-settings-layout>
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Billing & Tax Settings</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Configure your tax and billing defaults.</p>
        </div>

        <form action="{{ route('settings.billing.update') }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Tax ID -->
                <div>
                    <label for="tax_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tax ID / VAT
                        Number</label>
                    <input type="text" name="tax_id" id="tax_id" value="{{ old('tax_id', $organization->tax_id) }}"
                        placeholder="e.g., US12-3456789"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Your business tax identification number.
                    </p>
                </div>

                <!-- Default Currency -->
                <div>
                    <label for="default_currency"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Currency *</label>
                    <select name="default_currency" id="default_currency" required
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @php
                            $currencies = ['USD' => 'US Dollar (USD)', 'EUR' => 'Euro (EUR)', 'GBP' => 'British Pound (GBP)', 'CAD' => 'Canadian Dollar (CAD)', 'AUD' => 'Australian Dollar (AUD)', 'INR' => 'Indian Rupee (INR)'];
                        @endphp
                        @foreach($currencies as $code => $name)
                            <option value="{{ $code }}" {{ old('default_currency', $organization->default_currency ?? 'USD') === $code ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Default Tax Rate -->
                <div>
                    <label for="default_tax_rate"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Tax Rate (%)</label>
                    <input type="number" name="default_tax_rate" id="default_tax_rate"
                        value="{{ old('default_tax_rate', $organization->default_tax_rate) }}" step="0.01" min="0"
                        max="100" placeholder="0.00"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Applied to new invoices by default.</p>
                </div>

                <!-- Payment Terms -->
                <div>
                    <label for="payment_terms"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Payment Terms</label>
                    <input type="text" name="payment_terms" id="payment_terms"
                        value="{{ old('payment_terms', $organization->payment_terms) }}" placeholder="e.g., Net 30"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Shown on invoices.</p>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</x-settings-layout>
>>>>>>> 6337e80 (feat: Implement comprehensive billing and payment functionality with Stripe integration, invoice management, refunds, and organization-specific settings.)
