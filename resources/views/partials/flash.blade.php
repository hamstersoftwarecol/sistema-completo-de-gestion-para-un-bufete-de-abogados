@php
    $statusMessages = [
        'profile-updated' => 'Perfil actualizado.',
        'password-updated' => 'Contraseña actualizada.',
        'verification-link-sent' => 'Enlace de verificación enviado.',
    ];
    $status = session('status');
    $flash = collect([
        'success' => session('success') ?? ($status ? ($statusMessages[$status] ?? $status) : null),
        'error' => session('error'),
        'warning' => $errors->any() && ! session('error') ? 'Revise los campos marcados en el formulario.' : null,
    ])->filter();
    $styles = [
        'success' => ['check-circle', 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/30'],
        'error' => ['x-circle', 'bg-red-50 text-red-800 ring-red-200 dark:bg-red-500/10 dark:text-red-200 dark:ring-red-500/30'],
        'warning' => ['warning', 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-500/30'],
    ];
@endphp
@foreach ($flash as $type => $message)
    @if (is_string($message) && $message !== '')
        <div x-data="{ show: true }" x-show="show" x-transition
             @if($type === 'success') x-init="setTimeout(() => show = false, 6000)" @endif
             class="no-print mb-5 flex items-start gap-3 rounded-lg px-4 py-3 text-sm ring-1 {{ $styles[$type][1] }}" role="alert">
            <x-icon :name="$styles[$type][0]" class="mt-0.5 h-5 w-5 shrink-0" />
            <p class="flex-1">{{ $message }}</p>
            <button type="button" @click="show = false" class="opacity-60 hover:opacity-100" aria-label="Cerrar"><x-icon name="x" class="h-4 w-4" /></button>
        </div>
    @endif
@endforeach
