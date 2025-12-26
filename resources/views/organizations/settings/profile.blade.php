<<<<<<< HEAD
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Organization Profile') }}
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

                    <form method="POST" action="{{ route('settings.profile.update') }}" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="name" :value="__('Organization Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $organization->name)" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="billing_email" :value="__('Billing Email')" />
                            <x-text-input id="billing_email" name="billing_email" type="email" class="mt-1 block w-full" :value="old('billing_email', $organization->billing_email)" />
                            <x-input-error :messages="$errors->get('billing_email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="tax_id" :value="__('Tax / VAT ID')" />
                            <x-text-input id="tax_id" name="tax_id" type="text" class="mt-1 block w-full" :value="old('tax_id', $organization->tax_id)" />
                            <x-input-error :messages="$errors->get('tax_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="default_currency" :value="__('Default Currency')" />
                            <x-text-input id="default_currency" name="default_currency" type="text" maxlength="3" class="mt-1 block w-full uppercase" :value="old('default_currency', $organization->default_currency ?? config('stripe.default_currency', 'USD'))" />
                            <x-input-error :messages="$errors->get('default_currency')" class="mt-2" />
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button>{{ __('Save profile') }}</x-primary-button>
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
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Organization Profile</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update your organization's basic information and
                contact details.</p>
        </div>

        <form action="{{ route('settings.profile.update') }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Organization Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Organization
                        Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $organization->name) }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Billing Email -->
                <div>
                    <label for="billing_email"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Billing Email</label>
                    <input type="email" name="billing_email" id="billing_email"
                        value="{{ old('billing_email', $organization->billing_email) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $organization->phone) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Website -->
                <div>
                    <label for="website"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Website</label>
                    <input type="url" name="website" id="website" value="{{ old('website', $organization->website) }}"
                        placeholder="https://"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700">

            <h4 class="text-md font-medium text-gray-900 dark:text-white">Address</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">This address will appear on your invoices.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Address Line 1 -->
                <div class="md:col-span-2">
                    <label for="address_line1"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address Line 1</label>
                    <input type="text" name="address_line1" id="address_line1"
                        value="{{ old('address_line1', $organization->address_line1) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Address Line 2 -->
                <div class="md:col-span-2">
                    <label for="address_line2"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address Line 2</label>
                    <input type="text" name="address_line2" id="address_line2"
                        value="{{ old('address_line2', $organization->address_line2) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- City -->
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300">City</label>
                    <input type="text" name="city" id="city" value="{{ old('city', $organization->city) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- State -->
                <div>
                    <label for="state" class="block text-sm font-medium text-gray-700 dark:text-gray-300">State /
                        Province</label>
                    <input type="text" name="state" id="state" value="{{ old('state', $organization->state) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Postal Code -->
                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Postal
                        Code</label>
                    <input type="text" name="postal_code" id="postal_code"
                        value="{{ old('postal_code', $organization->postal_code) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Country -->
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Country
                        Code</label>
                    <input type="text" name="country" id="country" value="{{ old('country', $organization->country) }}"
                        maxlength="2" placeholder="US"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
