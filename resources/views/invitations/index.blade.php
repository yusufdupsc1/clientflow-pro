<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Invitations') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Send Invitation</h3>
                <form method="POST" action="{{ route('organizations.invites.store', $organization) }}" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="invite_email" :value="__('Email')" />
                        <x-text-input id="invite_email" name="email" type="email" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="invite_role" :value="__('Role')" />
                        <select id="invite_role" name="role" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 shadow-sm">
                            <option value="member">{{ __('Member') }}</option>
                            <option value="admin">{{ __('Admin') }}</option>
                            <option value="owner">{{ __('Owner') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>
                    <div>
                        <x-primary-button>{{ __('Send invite') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Pending Invitations</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-700 dark:text-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">Email</th>
                                <th class="px-4 py-2">Role</th>
                                <th class="px-4 py-2">Sent</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invitations as $invite)
                                <tr class="border-t border-gray-200 dark:border-gray-700">
                                    <td class="px-4 py-2">{{ $invite->email }}</td>
                                    <td class="px-4 py-2 capitalize">{{ $invite->role }}</td>
                                    <td class="px-4 py-2">{{ $invite->created_at?->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No pending invitations.
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
