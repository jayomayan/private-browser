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

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <x-banner />


            @livewire('navigation-menu')
            <div class="content-with-sidebar">
            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white  lg:ml-64">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-2">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="bg-white  lg:ml-64">
                {{ $slot }}
            </main>
            </div>

        @stack('modals')

        @livewireScripts
    </body>
</html>
