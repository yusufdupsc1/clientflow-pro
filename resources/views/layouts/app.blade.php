<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                @if (session('status'))
                    <div
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition
                        class="fixed top-4 right-4 z-50 rounded-lg bg-white shadow-lg border border-gray-200 px-4 py-3 text-sm text-gray-800"
                    >
                        <div class="flex items-start gap-2">
                            <div class="h-2 w-2 mt-1 rounded-full bg-emerald-500"></div>
                            <div class="flex-1">
                                {{ session('status') }}
                            </div>
                            <button type="button" class="text-gray-400 hover:text-gray-600" @click="show = false">&times;</button>
                        </div>
                    </div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
