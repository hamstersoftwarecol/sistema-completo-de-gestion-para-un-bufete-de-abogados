<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Imprimir' }} · {{ setting('firm_name', config('app.name')) }}</title>
    <style>{!! theme_css_variables() !!}</style>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-200 font-sans text-gray-900 antialiased print:bg-white">
    <div class="no-print sticky top-0 z-10 flex items-center justify-center gap-2 bg-gray-900 px-4 py-3">
        <button onclick="window.print()" class="btn btn-primary">🖨 Imprimir / Guardar PDF</button>
        <button onclick="history.length > 1 ? history.back() : window.close()" class="btn btn-secondary">Volver</button>
    </div>
    <div class="print-sheet mx-auto my-6 max-w-4xl bg-white p-10 shadow-lg">
        {{-- Encabezado del bufete --}}
        <div class="flex items-start justify-between gap-6 border-b-2 border-primary-600 pb-4">
            <div class="flex items-center gap-4">
                <img src="{{ route('branding.logo') }}" alt="Logo" class="h-16 w-16 object-contain">
                <div>
                    <p class="text-xl font-bold text-gray-900">{{ setting('firm_name', config('app.name')) }}</p>
                    @if (setting('firm_tax_id'))<p class="text-xs text-gray-600">NIT / ID fiscal: {{ setting('firm_tax_id') }}</p>@endif
                    <p class="text-xs text-gray-600">{{ setting('firm_address') }} {{ setting('firm_city') }}</p>
                    <p class="text-xs text-gray-600">{{ setting('firm_phone') }} · {{ setting('firm_email') }}</p>
                </div>
            </div>
            <div class="text-right">{{ $headerRight ?? '' }}</div>
        </div>

        {{ $slot }}

        <p class="mt-10 border-t border-gray-200 pt-3 text-center text-[11px] text-gray-500">
            Generado el {{ now()->format('d/m/Y h:i a') }} por {{ auth()->user()->name }} · {{ config('app.name') }}
        </p>
    </div>
</body>
</html>
