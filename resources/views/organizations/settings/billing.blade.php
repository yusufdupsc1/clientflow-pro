<x-settings-layout>
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Billing & Stripe Settings</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Configure your Stripe integration and billing
                defaults.</p>
        </div>

        @if (session('status'))
            <div
                class="m-6 p-4 rounded-md bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-200 text-sm">
                {{ session('status') }}
            </div>
        @endif

        <form action="{{ route('settings.billing.update') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Stripe Mode -->
                <div class="md:col-span-2">
                    <label for="stripe_mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stripe
                        Mode</label>
                    <select id="stripe_mode" name="stripe_mode"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="test" @selected(old('stripe_mode', $organization->stripe_mode ?? 'test') === 'test')>{{ __('Test') }}</option>
                        <option value="live" @selected(old('stripe_mode', $organization->stripe_mode ?? 'test') === 'live')>{{ __('Live') }}</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Use test mode until you are ready for
                        production charges.</p>
                    <x-input-error :messages="$errors->get('stripe_mode')" class="mt-2" />
                </div>

                <!-- Test Keys -->
                <div class="md:col-span-2">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Test
                        Credentials</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                        <div>
                            <label for="stripe_test_publishable_key"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Test Publishable
                                Key</label>
                            <input type="text" id="stripe_test_publishable_key" name="stripe_test_publishable_key"
                                value="{{ old('stripe_test_publishable_key', $organization->stripe_test_publishable_key) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <x-input-error :messages="$errors->get('stripe_test_publishable_key')" class="mt-2" />
                        </div>
                        <div>
                            <label for="stripe_test_secret"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Test Secret
                                Key</label>
                            <input type="password" id="stripe_test_secret" name="stripe_test_secret"
                                value="{{ old('stripe_test_secret', $organization->stripe_test_secret) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <x-input-error :messages="$errors->get('stripe_test_secret')" class="mt-2" />
                        </div>
                        <div class="md:col-span-2">
                            <label for="stripe_test_webhook_secret"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Test Webhook
                                Secret</label>
                            <input type="password" id="stripe_test_webhook_secret" name="stripe_test_webhook_secret"
                                value="{{ old('stripe_test_webhook_secret', $organization->stripe_test_webhook_secret) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <x-input-error :messages="$errors->get('stripe_test_webhook_secret')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Live Keys -->
                <div class="md:col-span-2">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Live
                        Credentials</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                        <div>
                            <label for="stripe_live_publishable_key"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Live Publishable
                                Key</label>
                            <input type="text" id="stripe_live_publishable_key" name="stripe_live_publishable_key"
                                value="{{ old('stripe_live_publishable_key', $organization->stripe_live_publishable_key) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <x-input-error :messages="$errors->get('stripe_live_publishable_key')" class="mt-2" />
                        </div>
                        <div>
                            <label for="stripe_live_secret"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Live Secret
                                Key</label>
                            <input type="password" id="stripe_live_secret" name="stripe_live_secret"
                                value="{{ old('stripe_live_secret', $organization->stripe_live_secret) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <x-input-error :messages="$errors->get('stripe_live_secret')" class="mt-2" />
                        </div>
                        <div class="md:col-span-2">
                            <label for="stripe_live_webhook_secret"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Live Webhook
                                Secret</label>
                            <input type="password" id="stripe_live_webhook_secret" name="stripe_live_webhook_secret"
                                value="{{ old('stripe_live_webhook_secret', $organization->stripe_live_webhook_secret) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <x-input-error :messages="$errors->get('stripe_live_webhook_secret')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <hr class="border-gray-200 dark:border-gray-700">
                </div>

                <!-- Business Settings -->
                <div>
                    <label for="tax_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tax ID / VAT
                        Number</label>
                    <input type="text" name="tax_id" id="tax_id" value="{{ old('tax_id', $organization->tax_id) }}"
                        placeholder="e.g., US12-3456789"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Your business tax identification number.
                    </p>
                    <x-input-error :messages="$errors->get('tax_id')" class="mt-2" />
                </div>

                <div>
                    <label for="default_currency"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Currency *</label>
                    <select name="default_currency" id="default_currency" required
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @php
                            $currencies = ['USD' => 'US Dollar (USD)', 'EUR' => 'Euro (EUR)', 'GBP' => 'British Pound (GBP)', 'CAD' => 'Canadian Dollar (CAD)', 'AUD' => 'Australian Dollar (AUD)', 'INR' => 'Indian Rupee (INR)'];
                        @endphp
                        @foreach($currencies as $code => $name)
                            <option value="{{ $code }}" @selected(old('default_currency', $organization->default_currency ?? 'USD') === $code)>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('default_currency')" class="mt-2" />
                </div>

                <div>
                    <label for="default_tax_rate"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Tax Rate (%)</label>
                    <input type="number" name="default_tax_rate" id="default_tax_rate"
                        value="{{ old('default_tax_rate', $organization->default_tax_rate) }}" step="0.01" min="0"
                        max="100" placeholder="0.00"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Applied to new invoices by default.</p>
                    <x-input-error :messages="$errors->get('default_tax_rate')" class="mt-2" />
                </div>

                <div>
                    <label for="payment_terms"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Payment Terms</label>
                    <input type="text" name="payment_terms" id="payment_terms"
                        value="{{ old('payment_terms', $organization->payment_terms) }}" placeholder="e.g., Net 30"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Shown on invoices.</p>
                    <x-input-error :messages="$errors->get('payment_terms')" class="mt-2" />
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <x-primary-button>{{ __('Save billing settings') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-settings-layout>