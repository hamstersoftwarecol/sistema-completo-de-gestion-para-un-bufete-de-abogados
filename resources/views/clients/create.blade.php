<x-app-layout title="Nuevo cliente">
    <x-slot name="header">
        <x-page-header title="Nuevo cliente" :back="route('clients.index')" />
    </x-slot>

    <form method="POST" action="{{ route('clients.store') }}">
        @csrf
        @include('clients._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('clients.index') }}" class="btn btn-secondary">Cancelar</a>
            <button class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Guardar cliente</button>
        </div>
    </form>
</x-app-layout>
