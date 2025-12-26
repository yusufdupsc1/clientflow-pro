<x-settings-layout>
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Organization Profile</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update your organization's basic information and
                contact details.</p>
        </div>

        @if (session('status'))
            <div
                class="m-6 p-4 rounded-md bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-200 text-sm">
                {{ session('status') }}
            </div>
        @endif

        <form action="{{ route('settings.profile.update') }}" method="POST" class="p-6 space-y-6">
            @csrf
            {{-- Using POST as defined in the consolidated controller, but PATCH is also fine if the route allows it.
            The consolidated controller updateProfile method handles either if the route is set up correctly. --}}

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Organization Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Organization
                        Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $organization->name) }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <!-- Billing Email -->
                <div>
                    <label for="billing_email"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Billing Email</label>
                    <input type="email" name="billing_email" id="billing_email"
                        value="{{ old('billing_email', $organization->billing_email) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('billing_email')" class="mt-2" />
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $organization->phone) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <!-- Website -->
                <div>
                    <label for="website"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Website</label>
                    <input type="url" name="website" id="website" value="{{ old('website', $organization->website) }}"
                        placeholder="https://"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('website')" class="mt-2" />
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
                    <x-input-error :messages="$errors->get('address_line1')" class="mt-2" />
                </div>

                <!-- Address Line 2 -->
                <div class="md:col-span-2">
                    <label for="address_line2"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address Line 2</label>
                    <input type="text" name="address_line2" id="address_line2"
                        value="{{ old('address_line2', $organization->address_line2) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('address_line2')" class="mt-2" />
                </div>

                <!-- City -->
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300">City</label>
                    <input type="text" name="city" id="city" value="{{ old('city', $organization->city) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('city')" class="mt-2" />
                </div>

                <!-- State -->
                <div>
                    <label for="state" class="block text-sm font-medium text-gray-700 dark:text-gray-300">State /
                        Province</label>
                    <input type="text" name="state" id="state" value="{{ old('state', $organization->state) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('state')" class="mt-2" />
                </div>

                <!-- Postal Code -->
                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Postal
                        Code</label>
                    <input type="text" name="postal_code" id="postal_code"
                        value="{{ old('postal_code', $organization->postal_code) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('postal_code')" class="mt-2" />
                </div>

                <!-- Country -->
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Country
                        Code</label>
                    <input type="text" name="country" id="country" value="{{ old('country', $organization->country) }}"
                        maxlength="2" placeholder="US"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('country')" class="mt-2" />
                </div>
            </div>

            <div class="flex justify-end">
                <x-primary-button>{{ __('Save profile') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-settings-layout>