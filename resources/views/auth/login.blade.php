<x-guest-layout>
    <div class="card p-8">
        <h2 class="text-2xl font-bold">Iniciar sesión</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ingrese con su cuenta del bufete.</p>

        <x-auth-session-status class="mt-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4" x-data>
            @csrf

            <x-field label="Correo electrónico" name="email">
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="form-control" x-ref="email">
            </x-field>

            <x-field label="Contraseña" name="password">
                <input id="password" type="password" name="password" required autocomplete="current-password" class="form-control" x-ref="password">
            </x-field>

            <div class="flex items-center justify-between">
                <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <input type="checkbox" name="remember" class="form-check"> Recordarme
                </label>
                @if (Route::has('password.request'))
                    <a class="text-sm link" href="{{ route('password.request') }}">¿Olvidó su contraseña?</a>
                @endif
            </div>

            <button type="submit" class="btn btn-primary w-full py-2.5">Ingresar</button>

            @if (config('bufete.demo_mode'))
                <div class="mt-6 rounded-lg border border-dashed border-primary-300 bg-primary-50/60 p-4 dark:border-primary-700 dark:bg-primary-500/5">
                    <p class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">
                        <x-icon name="key" class="h-4 w-4" /> Cuentas de demostración (contraseña: password)
                    </p>
                    <div class="grid gap-1.5">
                        @foreach ([
                            ['admin@bufete.test', 'Superadministrador', 'rose'],
                            ['senior@bufete.test', 'Abogado Senior', 'indigo'],
                            ['junior@bufete.test', 'Abogado Junior', 'sky'],
                        ] as [$email, $role, $color])
                            <button type="button" class="flex items-center justify-between rounded-md bg-white px-3 py-2 text-left text-sm ring-1 ring-gray-200 hover:ring-primary-400 dark:bg-gray-800 dark:ring-gray-700"
                                    @click="$refs.email.value = '{{ $email }}'; $refs.password.value = 'password'; $el.closest('form').submit()">
                                <span class="font-medium">{{ $email }}</span>
                                <x-badge :color="$color">{{ $role }}</x-badge>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </form>
    </div>
</x-guest-layout>
