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
