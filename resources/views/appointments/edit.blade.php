<x-app-layout title="Cita">
    <x-slot name="header">
        <x-page-header :title="$appointment->title" :subtitle="$appointment->starts_at->translatedFormat('l d \d\e F \d\e Y, h:i a').' · '.$appointment->who" :back="route('appointments.index')">
            @can('delete', $appointment)<x-delete-button :action="route('appointments.destroy', $appointment)" label="Eliminar" size="" confirm="¿Eliminar esta cita?" />@endcan
        </x-page-header>
    </x-slot>
    <form method="POST" action="{{ route('appointments.update', $appointment) }}">
        @csrf
        @method('PUT')
        @include('appointments._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('appointments.index') }}" class="btn btn-secondary">Cancelar</a>
            <button class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Guardar</button>
        </div>
    </form>
</x-app-layout>
