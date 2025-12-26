<<<<<<< HEAD
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Branding') }}
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

                    <form method="POST" action="{{ route('settings.branding.update') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="branding_logo" :value="__('Logo for invoices')" />
                            <input id="branding_logo" name="branding_logo" type="file" accept="image/*" class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-200" />
                            <x-input-error :messages="$errors->get('branding_logo')" class="mt-2" />
                            @if ($organization->branding_logo_path)
                                <div class="mt-3">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ __('Current logo preview') }}</p>
                                    <img src="{{ Storage::disk('public')->url($organization->branding_logo_path) }}" alt="{{ __('Current logo') }}" class="h-16">
                                </div>
                            @endif
                        </div>

                        <div>
                            <x-input-label for="branding_color" :value="__('Brand color (hex or name)')" />
                            <x-text-input id="branding_color" name="branding_color" type="text" class="mt-1 block w-full" :value="old('branding_color', $organization->branding_color)" />
                            <x-input-error :messages="$errors->get('branding_color')" class="mt-2" />
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button>{{ __('Save branding') }}</x-primary-button>
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
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Branding</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Customize your invoices with your logo and
                branding.</p>
        </div>

        <form action="{{ route('settings.branding.update') }}" method="POST" enctype="multipart/form-data"
            class="p-6 space-y-6">
            @csrf
            @method('PATCH')

            <!-- Logo Upload -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Logo</label>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">This logo will appear on your invoices and
                    emails.</p>

                @if($organization->logo_path)
                    <div class="mb-4 flex items-center gap-4">
                        <img src="{{ Storage::url($organization->logo_path) }}" alt="Current logo"
                            class="h-16 w-auto object-contain bg-gray-100 dark:bg-gray-700 rounded p-2">
                        <label class="flex items-center text-sm">
                            <input type="checkbox" name="remove_logo" value="1"
                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-gray-700 dark:text-gray-300">Remove current logo</span>
                        </label>
                    </div>
                @endif

                <div class="flex items-center justify-center w-full">
                    <label for="logo"
                        class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <svg class="w-8 h-8 mb-3 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Click
                                    to upload</span> or drag and drop</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG, GIF or SVG (max 2MB)</p>
                        </div>
                        <input id="logo" name="logo" type="file" class="hidden" accept="image/*" />
                    </label>
                </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700">

            <!-- Invoice Footer -->
            <div>
                <label for="invoice_footer" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Invoice
                    Footer</label>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">This text will appear at the bottom of all
                    invoices.</p>
                <textarea name="invoice_footer" id="invoice_footer" rows="4"
                    placeholder="e.g., Thank you for your business! Payment is due within 30 days."
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('invoice_footer', $organization->invoice_footer) }}</textarea>
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
