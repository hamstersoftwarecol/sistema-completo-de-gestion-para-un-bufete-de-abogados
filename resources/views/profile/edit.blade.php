<x-app-layout title="Mi perfil">
    <x-slot name="header">
        <x-page-header title="Mi perfil" :subtitle="auth()->user()->role->label().' · '.auth()->user()->email" />
    </x-slot>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="card p-6">
            @include('profile.partials.update-profile-information-form')
        </div>
        <div class="space-y-6">
            <div class="card p-6">
                @include('profile.partials.update-password-form')
            </div>
            <div class="card p-6">
                <h2 class="text-lg font-medium">Preferencias</h2>
                <p class="mt-1 text-sm text-gray-500">El modo oscuro se guarda en este navegador.</p>
                <div class="mt-4 flex flex-wrap gap-2" x-data>
                    <button type="button" class="btn btn-secondary" @click="$store.theme.dark || $store.theme.toggle()" :class="$store.theme.dark && 'ring-2 ring-primary-500'"><x-icon name="moon" class="h-4 w-4" /> Modo oscuro</button>
                    <button type="button" class="btn btn-secondary" @click="!$store.theme.dark || $store.theme.toggle()" :class="!$store.theme.dark && 'ring-2 ring-primary-500'"><x-icon name="sun" class="h-4 w-4" /> Modo claro</button>
                    <a href="{{ route('ai.index') }}" class="btn btn-secondary"><x-icon name="sparkles" class="h-4 w-4" /> Instrucciones de IA</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
