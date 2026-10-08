<x-app-layout title="Audiencia">
    <x-slot name="header">
        <x-page-header :title="$hearing->title" :subtitle="'Caso '.$hearing->legalCase?->case_number.' · '.$hearing->legalCase?->client?->name" :back="route('hearings.index')">
            <a href="{{ route('cases.show', $hearing->legal_case_id) }}#audiencias" class="btn btn-secondary"><x-icon name="folder" class="h-4 w-4" /> Ver expediente</a>
            @can('delete', $hearing)<x-delete-button :action="route('hearings.destroy', $hearing)" label="Eliminar" size="" confirm="¿Eliminar esta audiencia?" />@endcan
        </x-page-header>
    </x-slot>
    <form method="POST" action="{{ route('hearings.update', $hearing) }}">
        @csrf
        @method('PUT')
        @include('hearings._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('hearings.index') }}" class="btn btn-secondary">Cancelar</a>
            <button class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Guardar</button>
        </div>
    </form>
</x-app-layout>
