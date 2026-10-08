<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · @yield('title')</title>
    <style>
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; background: #f3f4f6; color: #111827; display: flex; min-height: 100vh; align-items: center; justify-content: center; }
        .box { text-align: center; padding: 2rem; max-width: 480px; }
        .code { font-size: 4.5rem; font-weight: 800; color: #4f46e5; margin: 0; }
        h1 { font-size: 1.4rem; margin: .5rem 0; }
        p { color: #6b7280; }
        a { display: inline-block; margin-top: 1rem; background: #4f46e5; color: #fff; padding: .6rem 1.2rem; border-radius: .5rem; text-decoration: none; font-weight: 600; }
        @media (prefers-color-scheme: dark) { body { background: #030712; color: #f3f4f6; } p { color: #9ca3af; } }
    </style>
</head>
<body>
<div class="box">
    <p class="code">@yield('code')</p>
    <h1>@yield('title')</h1>
    <p>@yield('message')</p>
    <a href="{{ url('/dashboard') }}">Volver al panel</a>
</div>
</body>
</html>
