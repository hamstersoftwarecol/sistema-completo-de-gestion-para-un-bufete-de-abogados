<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ setting('firm_name', config('app.name')) }} · Acceso</title>
    <link rel="icon" href="{{ route('branding.logo') }}">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
    <style>{!! theme_css_variables() !!}</style>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-900 antialiased dark:text-gray-100">
<div class="flex min-h-screen">
    {{-- Panel de marca --}}
    <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-gradient-to-br from-primary-700 via-primary-800 to-primary-950 p-12 text-white lg:flex">
        <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white/5"></div>
        <div class="absolute -bottom-32 -left-16 h-[28rem] w-[28rem] rounded-full bg-white/5"></div>

        <div class="relative flex items-center gap-3">
            <img src="{{ route('branding.logo') }}" alt="Logo" class="h-12 w-12 rounded-xl bg-white/10 object-contain p-1">
            <div>
                <p class="text-lg font-bold">{{ setting('firm_name', config('app.name')) }}</p>
                <p class="text-sm text-primary-200">{{ setting('firm_slogan', 'Gestión jurídica') }}</p>
            </div>
        </div>

        <div class="relative">
            <h1 class="text-4xl font-bold leading-tight">Gestión integral<br>para su bufete de abogados</h1>
            <p class="mt-4 max-w-md text-primary-100">Clientes, expedientes, audiencias, citas, honorarios, gastos y un asistente legal con inteligencia artificial, en un solo lugar.</p>
            <ul class="mt-8 grid max-w-md grid-cols-2 gap-3 text-sm text-primary-100">
                @foreach (['Vista 360 del cliente', 'Calendario judicial', 'Recibos imprimibles', 'Aprobación de gastos', 'Chat interno', 'Asistente IA'] as $feature)
                    <li class="flex items-center gap-2"><x-icon name="check-circle" class="h-5 w-5 text-primary-300" />{{ $feature }}</li>
                @endforeach
            </ul>
        </div>

        <p class="relative text-xs text-primary-300">{{ config('app.name') }} · Laravel + Breeze + SQLite</p>
    </div>

    {{-- Formulario --}}
    <div class="flex w-full flex-col items-center justify-center bg-gray-50 px-6 py-12 dark:bg-gray-950 lg:w-1/2">
        <div class="mb-8 flex items-center gap-3 lg:hidden">
            <img src="{{ route('branding.logo') }}" alt="Logo" class="h-12 w-12 rounded-xl object-contain">
            <p class="text-lg font-bold">{{ setting('firm_name', config('app.name')) }}</p>
        </div>
        <div class="w-full max-w-md">
            {{ $slot }}
        </div>
    </div>
</div>
</body>
</html>
