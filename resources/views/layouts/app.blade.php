<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ setting('firm_name', config('app.name')) }}</title>
    <link rel="icon" href="{{ route('branding.logo') }}">

    <script>
        // Aplica el modo oscuro antes de pintar la página (evita parpadeo).
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
    <script>window.__counters = { chat: {{ (int) $unreadChatCount }}, notifications: {{ (int) $unreadNotificationsCount }} };</script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-gray-100 font-sans text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
@php
    $me = auth()->user();
    $impersonating = session()->has('impersonator_id');
@endphp
<div x-data="{ sidebar: false }" @keydown.escape.window="sidebar = false" class="min-h-screen">

    {{-- Fondo del menú en móviles --}}
    <div x-cloak x-show="sidebar" x-transition.opacity class="fixed inset-0 z-30 bg-gray-900/60 lg:hidden" @click="sidebar = false"></div>

    {{-- Menú lateral --}}
    <aside class="no-print fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-gray-900 transition-transform duration-200 lg:translate-x-0 dark:bg-gray-900 dark:ring-1 dark:ring-white/5"
           :class="{ 'translate-x-0': sidebar, '-translate-x-full': !sidebar }">
        <a href="{{ route('dashboard') }}" class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-5">
            <img src="{{ route('branding.logo') }}" alt="Logo" class="h-9 w-9 rounded-lg bg-white/5 object-contain">
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-white">{{ setting('firm_name', config('app.name')) }}</p>
                <p class="truncate text-[11px] text-gray-400">{{ setting('firm_slogan', 'Gestión jurídica') }}</p>
            </div>
        </a>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
            <div class="space-y-1">
                <x-nav-item :href="route('dashboard')" icon="home" :active="request()->routeIs('dashboard')">Panel de control</x-nav-item>
                <x-nav-item :href="route('clients.index')" icon="users" :active="request()->routeIs('clients.*')">Clientes</x-nav-item>
                <x-nav-item :href="route('cases.index')" icon="briefcase" :active="request()->routeIs('cases.*')">Casos</x-nav-item>
            </div>

            <div>
                <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Agenda</p>
                <div class="space-y-1">
                    <x-nav-item :href="route('calendar.index')" icon="calendar" :active="request()->routeIs('calendar.*')">Calendario judicial</x-nav-item>
                    <x-nav-item :href="route('hearings.index')" icon="scale" :active="request()->routeIs('hearings.*')">Audiencias</x-nav-item>
                    <x-nav-item :href="route('appointments.index')" icon="clock" :active="request()->routeIs('appointments.*')">Citas</x-nav-item>
                </div>
            </div>

            <div>
                <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Finanzas</p>
                <div class="space-y-1">
                    <x-nav-item :href="route('payments.index')" icon="banknotes" :active="request()->routeIs('payments.*')">Pagos y recibos</x-nav-item>
                    <x-nav-item :href="route('expenses.index')" icon="receipt" :active="request()->routeIs('expenses.*')" :badge="$pendingApprovals ?: null" badge-color="bg-amber-500">Gastos</x-nav-item>
                </div>
            </div>

            <div>
                <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Comunicación</p>
                <div class="space-y-1">
                    <a href="{{ route('chat.index') }}" @class(['nav-item', 'nav-item-active' => request()->routeIs('chat.*')])>
                        <x-icon name="chat" class="h-5 w-5 shrink-0 opacity-90" />
                        <span class="flex-1">Chat interno</span>
                        <span x-cloak x-show="$store.counters.chat > 0" x-text="$store.counters.chat" class="ml-auto inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold text-white"></span>
                    </a>
                    <x-nav-item :href="route('ai.index')" icon="sparkles" :active="request()->routeIs('ai.*')">Asistente IA</x-nav-item>
                </div>
            </div>

            @can('admin')
                <div>
                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Administración</p>
                    <div class="space-y-1">
                        <x-nav-item :href="route('admin.users.index')" icon="user-group" :active="request()->routeIs('admin.users.*')">Usuarios</x-nav-item>
                        <x-nav-item :href="route('admin.master-data.index')" icon="stack" :active="request()->routeIs('admin.master-data.*')">Datos maestros</x-nav-item>
                        <x-nav-item :href="route('admin.settings.edit')" icon="cog" :active="request()->routeIs('admin.settings.*')">Configuración</x-nav-item>
                    </div>
                </div>
            @endcan
        </nav>

        <div class="border-t border-white/10 p-3">
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-lg p-2 hover:bg-white/5">
                <x-avatar :name="$me->name" />
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-white">{{ $me->name }}</p>
                    <p class="truncate text-xs text-gray-400">{{ $me->role->label() }}</p>
                </div>
            </a>
        </div>
    </aside>

    <div class="flex min-h-screen flex-col lg:pl-64">
        @if ($impersonating)
            <div class="no-print flex flex-wrap items-center justify-center gap-3 bg-amber-500 px-4 py-2 text-sm font-medium text-white">
                <x-icon name="switch" class="h-5 w-5" />
                Está viendo el sistema como <strong>{{ $me->name }}</strong> ({{ $me->role->label() }}).
                <form method="POST" action="{{ route('impersonate.leave') }}">
                    @csrf
                    <button class="rounded-md bg-white/20 px-2.5 py-1 text-xs font-semibold hover:bg-white/30">Volver a mi cuenta</button>
                </form>
            </div>
        @endif

        {{-- Barra superior --}}
        <header class="no-print sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-gray-200 bg-white/90 px-4 backdrop-blur dark:border-gray-800 dark:bg-gray-900/90 sm:px-6">
            <button type="button" class="btn btn-ghost -ml-2 p-2 lg:hidden" @click="sidebar = true" aria-label="Abrir menú">
                <x-icon name="menu" class="h-6 w-6" />
            </button>

            <form action="{{ route('search') }}" method="GET" class="relative max-w-md flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <input type="search" name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="Buscar clientes, casos, documentos…"
                       class="form-control !rounded-full !pl-9 !py-1.5 bg-gray-50 dark:bg-gray-800">
            </form>

            <div class="ml-auto flex items-center gap-1">
                <button type="button" class="btn btn-ghost p-2" @click="$store.theme.toggle()" title="Modo claro / oscuro">
                    <x-icon name="moon" class="h-5 w-5" x-show="!$store.theme.dark" />
                    <x-icon name="sun" class="h-5 w-5" x-cloak x-show="$store.theme.dark" />
                </button>

                <a href="{{ route('chat.index') }}" class="btn btn-ghost relative p-2" title="Chat interno">
                    <x-icon name="chat" class="h-5 w-5" />
                    <span x-cloak x-show="$store.counters.chat > 0" x-text="$store.counters.chat" class="absolute -right-0.5 -top-0.5 inline-flex min-w-[1.1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"></span>
                </a>

                {{-- Notificaciones --}}
                <x-dropdown align="right" width="80" contentClasses="py-0 bg-white dark:bg-gray-800">
                    <x-slot name="trigger">
                        <button type="button" class="btn btn-ghost relative p-2" title="Notificaciones">
                            <x-icon name="bell" class="h-5 w-5" />
                            <span x-cloak x-show="$store.counters.notifications > 0" x-text="$store.counters.notifications" class="absolute -right-0.5 -top-0.5 inline-flex min-w-[1.1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"></span>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5 dark:border-gray-700">
                            <p class="text-sm font-semibold">Notificaciones</p>
                            @if ($unreadNotificationsCount)
                                <form method="POST" action="{{ route('notifications.read-all') }}">
                                    @csrf
                                    <button class="text-xs link">Marcar todas como leídas</button>
                                </form>
                            @endif
                        </div>
                        <div class="max-h-96 overflow-y-auto">
                            @forelse ($unreadNotifications as $n)
                                <a href="{{ route('notifications.open', $n->id) }}" class="flex gap-3 border-b border-gray-50 px-4 py-3 hover:bg-gray-50 dark:border-gray-700/50 dark:hover:bg-gray-700/40">
                                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ \App\Support\Badge::classes($n->data['color'] ?? 'indigo') }}">
                                        <x-icon :name="$n->data['icon'] ?? 'bell'" class="h-4 w-4" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ $n->data['title'] ?? 'Notificación' }}</span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $n->data['message'] ?? '' }}</span>
                                        <span class="mt-0.5 block text-[11px] text-gray-400">{{ $n->created_at->diffForHumans() }}</span>
                                    </span>
                                </a>
                            @empty
                                <x-empty icon="bell" title="Sin notificaciones nuevas" />
                            @endforelse
                        </div>
                        <a href="{{ route('notifications.index') }}" class="block border-t border-gray-100 px-4 py-2.5 text-center text-xs font-semibold link dark:border-gray-700">Ver todas</a>
                    </x-slot>
                </x-dropdown>

                {{-- Usuario --}}
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button type="button" class="flex items-center gap-2 rounded-full p-1 hover:bg-gray-100 dark:hover:bg-gray-800">
                            <x-avatar :name="$me->name" size="h-8 w-8 text-xs" />
                            <x-icon name="chevron-down" class="hidden h-4 w-4 text-gray-400 sm:block" />
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="border-b border-gray-100 px-4 py-2.5 dark:border-gray-700">
                            <p class="truncate text-sm font-semibold">{{ $me->name }}</p>
                            <p class="truncate text-xs text-gray-500">{{ $me->email }}</p>
                        </div>
                        <x-dropdown-link :href="route('profile.edit')">Mi perfil</x-dropdown-link>
                        <x-dropdown-link :href="route('notifications.index')">Notificaciones</x-dropdown-link>
                        @can('admin')
                            <x-dropdown-link :href="route('admin.settings.edit')">Configuración</x-dropdown-link>
                        @endcan
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar sesión</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </header>

        <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
            @include('partials.flash')

            @isset($header)
                <div class="mb-6">{{ $header }}</div>
            @endisset

            {{ $slot }}
        </main>

        <footer class="no-print border-t border-gray-200 px-6 py-4 text-center text-xs text-gray-500 dark:border-gray-800">
            {{ setting('firm_name', config('app.name')) }} · {{ config('app.name') }} — Sistema de gestión de casos legales · {{ now()->year }}
        </footer>
    </div>
</div>

@stack('scripts')
</body>
</html>
