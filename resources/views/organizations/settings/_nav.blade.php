@php
    $items = [
        ['label' => __('Profile'), 'route' => 'settings.profile'],
        ['label' => __('Billing'), 'route' => 'settings.billing'],
        ['label' => __('Branding'), 'route' => 'settings.branding'],
    ];
@endphp

<div class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700 pb-4">
    @foreach ($items as $item)
        <a
            href="{{ route($item['route']) }}"
            class="px-3 py-1.5 rounded-md text-sm font-medium border
                {{ request()->routeIs($item['route']) ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800' }}"
        >
            {{ $item['label'] }}
        </a>
    @endforeach
</div>
