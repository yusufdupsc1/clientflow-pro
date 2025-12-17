<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
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
