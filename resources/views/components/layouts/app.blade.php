<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? 'X store' }}</title>

        <!-- Google Fonts - Manrope -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200;300;400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles

    </head>
    <body class="font-sans bg-slate-200 dark:bg-slate-700">

        @livewire('partials.navbar')

        <main>{{ $slot }}</main>

        @livewire('partials.footer')

        @livewireScripts

        <script src="//cdn.jsdelivr.net/npm/sweetalert2@11" data-navigate-once></script>

        <x-livewire-alert::scripts />

    </body>
</html>
