<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $client->name }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-2">
                    <div><span class="font-semibold">{{ __('Email:') }}</span> {{ $client->email }}</div>
                    <div><span class="font-semibold">{{ __('Phone:') }}</span> {{ $client->phone }}</div>
                    <div><span class="font-semibold">{{ __('Company:') }}</span> {{ $client->company }}</div>
                    <div><span class="font-semibold">{{ __('Notes:') }}</span> {{ $client->notes }}</div>
                </div>
                <div class="p-6 border-t flex justify-between">
                    <a href="{{ route('clients.edit', $client) }}" class="text-indigo-600 hover:underline">
                        {{ __('Edit') }}
                    </a>
                    <form method="POST" action="{{ route('clients.destroy', $client) }}" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>{{ __('Delete') }}</x-danger-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
