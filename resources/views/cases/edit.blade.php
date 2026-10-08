<x-app-layout title="Editar caso">
    <x-slot name="header">
        <x-page-header :title="'Editar caso '.$case->case_number" :back="route('cases.show', $case)" />
    </x-slot>

    <form method="POST" action="{{ route('cases.update', $case) }}">
        @csrf
        @method('PUT')
        @include('cases._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('cases.show', $case) }}" class="btn btn-secondary">Cancelar</a>
            <button class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Guardar cambios</button>
        </div>
    </form>
</x-app-layout>
