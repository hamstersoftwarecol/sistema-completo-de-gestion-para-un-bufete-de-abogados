<x-app-layout title="Editar cliente">
    <x-slot name="header">
        <x-page-header :title="'Editar: '.$client->name" :back="route('clients.show', $client)" />
    </x-slot>

    <form method="POST" action="{{ route('clients.update', $client) }}">
        @csrf
        @method('PUT')
        @include('clients._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('clients.show', $client) }}" class="btn btn-secondary">Cancelar</a>
            <button class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Guardar cambios</button>
        </div>
    </form>
</x-app-layout>
