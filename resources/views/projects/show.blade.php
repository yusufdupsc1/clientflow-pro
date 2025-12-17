<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $project->name }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-2">
                    <div><span class="font-semibold">{{ __('Status:') }}</span> {{ ucfirst($project->status) }}</div>
                    <div><span class="font-semibold">{{ __('Client:') }}</span> {{ $project->client?->name }}</div>
                    <div><span class="font-semibold">{{ __('Due Date:') }}</span> {{ optional($project->due_date)->toFormattedDateString() }}</div>
                    <div><span class="font-semibold">{{ __('Description:') }}</span> {{ $project->description }}</div>
                </div>
                <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                    <a href="{{ route('projects.edit', $project) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                        {{ __('Edit') }}
                    </a>
                    <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>{{ __('Delete') }}</x-danger-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
