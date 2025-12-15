<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('API Tokens') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-4">Issue New Token</h3>
                    @if($hasCurrentOrg)
                        <form method="POST" action="/api/tokens" class="mb-6">
                            @csrf
                            <x-primary-button type="submit">Create Token</x-primary-button>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Token is tied to your current organization and shown once in the API response.</p>
                        </form>
                    @else
                        <p class="text-sm text-red-500 mb-6">Select a current organization before issuing tokens.</p>
                    @endif

                    <h3 class="text-lg font-medium mb-4">Existing Tokens</h3>
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">ID</th>
                                <th class="px-4 py-2">Name</th>
                                <th class="px-4 py-2">Created</th>
                                <th class="px-4 py-2">Last Used</th>
                                <th class="px-4 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tokens as $token)
                                <tr class="border-t border-gray-200 dark:border-gray-700">
                                    <td class="px-4 py-2">{{ $token->id }}</td>
                                    <td class="px-4 py-2">{{ $token->name ?? 'api' }}</td>
                                    <td class="px-4 py-2">{{ $token->created_at?->diffForHumans() }}</td>
                                    <td class="px-4 py-2">{{ $token->last_used_at?->diffForHumans() ?? 'never' }}</td>
                                    <td class="px-4 py-2">
                                        <form method="POST" action="{{ route('tokens.destroy', $token->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-danger-button>Revoke</x-danger-button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No tokens found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
