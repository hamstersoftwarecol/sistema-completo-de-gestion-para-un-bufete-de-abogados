<x-app-layout title="Programar audiencia">
    <x-slot name="header"><x-page-header title="Programar audiencia" :back="url()->previous()" /></x-slot>
    <form method="POST" action="{{ route('hearings.store') }}">
        @csrf
        @include('hearings._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancelar</a>
            <button class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Programar</button>
        </div>
    </form>
</x-app-layout>
