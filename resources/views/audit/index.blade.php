<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Audit Log') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">Action</th>
                                <th class="px-4 py-2">Actor</th>
                                <th class="px-4 py-2">Subject</th>
                                <th class="px-4 py-2">When</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr class="border-t border-gray-200 dark:border-gray-700">
                                    <td class="px-4 py-2">{{ $log->action }}</td>
                                    <td class="px-4 py-2">{{ $log->actor?->name ?? 'system' }}</td>
                                    <td class="px-4 py-2">{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</td>
                                    <td class="px-4 py-2">{{ $log->created_at?->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">No audit entries.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
