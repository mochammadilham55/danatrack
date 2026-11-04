<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center p-6 bg-gradient-to-br from-indigo-50 via-slate-50 to-rose-50">
            <div class="w-full sm:max-w-md">
                <div class="text-center mb-6">
                    <a href="/" class="inline-block">
                        <x-application-logo class="w-16 h-16 mx-auto" />
                    </a>
                </div>

                <div class="bg-white rounded-3xl shadow-xl shadow-slate-200 p-8">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
